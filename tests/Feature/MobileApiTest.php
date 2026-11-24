<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\RecipeSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_issues_a_token_and_logout_revokes_only_that_token(): void
    {
        $user = User::factory()->create();
        $other = $user->createToken('Other device');
        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password']);
        $login->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('user.password');
        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/v1/kitchen')->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
    }

    public function test_invalid_credentials_do_not_issue_tokens(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_confirmed_two_factor_authentication_requires_a_valid_code(): void
    {
        $engine = new Google2FA;
        $secret = $engine->generateSecretKey();
        $user = User::factory()->create(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()]);
        $credentials = ['email' => $user->email, 'password' => 'password'];
        $this->postJson('/api/v1/auth/login', $credentials)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/auth/login', $credentials + ['code' => $engine->getCurrentOtp($secret)])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_registration_uses_shared_validation_and_cannot_set_admin_role(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'Kitchen Cook', 'email' => 'cook@example.test', 'password' => 'password', 'password_confirmation' => 'mismatch'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/auth/register', ['name' => 'Kitchen Cook', 'email' => 'cook@example.test', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'admin'])->assertOk()->assertJsonPath('user.role', 'user');
        $this->assertDatabaseHas('users', ['email' => 'cook@example.test', 'role' => 'user']);
    }

    public function test_private_mobile_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/kitchen')->assertUnauthorized();
        $this->getJson('/api/v1/recommendations')->assertUnauthorized();
        $this->postJson('/api/v1/pantry', [])->assertUnauthorized();
        $this->putJson('/api/v1/equipment', ['equipment' => []])->assertUnauthorized();
        $this->postJson('/api/v1/cooking-chat', ['message' => 'Hello'])->assertUnauthorized();
    }

    public function test_kitchen_returns_only_published_recipes_and_own_pantry(): void
    {
        $this->seed([ReferenceDataSeeder::class, RecipeSeeder::class]);
        $hidden = Recipe::first();
        $hidden->update(['is_published' => false]);
        $other = User::factory()->create();
        $ingredient = Ingredient::first();
        $other->inventory()->create(['ingredient_id' => $ingredient->id, 'quantity' => 1, 'unit' => $ingredient->base_unit, 'quantity_base' => 1]);
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/kitchen')->assertOk()->assertJsonCount(7, 'recipes')->assertJsonCount(0, 'pantry')->assertJsonMissing(['slug' => $hidden->slug])->assertJsonStructure(['recipes' => [['steps', 'ingredients', 'equipment', 'total_minutes']]]);
    }

    public function test_pantry_converts_units_updates_and_deletes_owned_records(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $ingredient = Ingredient::where('base_unit', 'g')->firstOrFail();
        $data = ['ingredient_id' => $ingredient->id, 'quantity' => 2, 'unit' => 'kg', 'expiration_date' => '2030-01-01'];
        $response = $this->postJson('/api/v1/pantry', $data)->assertCreated();
        $id = $response->json('item.id');
        $this->assertDatabaseHas('user_ingredients', ['id' => $id, 'user_id' => $user->id, 'quantity_base' => 2000]);
        $this->putJson('/api/v1/pantry/'.$id, array_replace($data, ['quantity' => 1]))->assertOk();
        $this->assertDatabaseHas('user_ingredients', ['id' => $id, 'quantity_base' => 1000]);
        $this->deleteJson('/api/v1/pantry/'.$id)->assertOk();
        $this->assertDatabaseMissing('user_ingredients', ['id' => $id]);
    }

    public function test_pantry_rejects_incompatible_units_and_invalid_dates(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        Sanctum::actingAs(User::factory()->create());
        $ingredient = Ingredient::where('base_unit', 'g')->firstOrFail();
        $data = ['ingredient_id' => $ingredient->id, 'quantity' => 2, 'unit' => 'ml'];
        $this->postJson('/api/v1/pantry', $data)->assertUnprocessable()->assertJsonValidationErrors('unit');
        $this->postJson('/api/v1/pantry', array_replace($data, ['unit' => 'g', 'purchase_date' => '2030-02-01', 'expiration_date' => '2030-01-01']))->assertUnprocessable()->assertJsonValidationErrors('expiration_date');
        $this->postJson('/api/v1/pantry', array_replace($data, ['unit' => 'g', 'quantity' => -1]))->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('user_ingredients', 0);
    }

    public function test_pantry_cannot_modify_another_users_item(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $owner = User::factory()->create();
        $ingredient = Ingredient::first();
        $item = $owner->inventory()->create(['ingredient_id' => $ingredient->id, 'quantity' => 1, 'unit' => $ingredient->base_unit, 'quantity_base' => 1]);
        Sanctum::actingAs(User::factory()->create());
        $this->putJson('/api/v1/pantry/'.$item->id, [])->assertNotFound();
        $this->deleteJson('/api/v1/pantry/'.$item->id)->assertNotFound();
        $this->assertModelExists($item);
    }

    public function test_equipment_selection_is_validated_and_saved_for_current_user(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $id = Equipment::first()->id;
        $this->putJson('/api/v1/equipment', ['equipment' => [$id]])->assertOk();
        $this->assertDatabaseHas('equipment_user', ['user_id' => $user->id, 'equipment_id' => $id]);
        $this->putJson('/api/v1/equipment', ['equipment' => [999999]])->assertUnprocessable()->assertJsonValidationErrors('equipment.0');
        $this->putJson('/api/v1/equipment', ['equipment' => []])->assertOk();
        $this->assertDatabaseCount('equipment_user', 0);
    }

    public function test_chat_reports_pending_setup_and_returns_provider_replies(): void
    {
        Sanctum::actingAs(User::factory()->create());
        config(['services.cooking_ai.key' => null]);
        $this->postJson('/api/v1/cooking-chat', ['message' => 'How do I cook adobo?'])->assertServiceUnavailable();
        config(['services.cooking_ai.provider' => 'gemini', 'services.cooking_ai.key' => 'fake-test-key', 'services.cooking_ai.model' => 'test-model']);
        Http::preventStrayRequests();
        Http::fake(['https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Simmer until cooked.']]]]]])]);
        $this->postJson('/api/v1/cooking-chat', ['message' => 'How do I cook adobo?'])->assertOk()->assertJsonPath('reply', 'Simmer until cooked.');
        Http::assertSent(fn ($request): bool => str_contains($request['systemInstruction']['parts'][0]['text'], 'at least 3 varied meal ideas')
            && str_contains($request['systemInstruction']['parts'][0]['text'], 'measured ingredient quantities'));
        Http::assertSentCount(1);
    }

    public function test_ingredient_scanner_requires_authentication_and_valid_images(): void
    {
        $image = ['mime_type' => 'image/png', 'data' => 'not-base64'];

        $this->postJson('/api/v1/ingredient-scan', ['images' => [$image]])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/ingredient-scan', ['images' => [$image]])
            ->assertUnprocessable()->assertJsonValidationErrors('images.0.data');
    }

    public function test_ingredient_scanner_sends_multiple_images_to_gemini(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        config(['services.cooking_ai.provider' => 'gemini', 'services.cooking_ai.key' => 'fake-test-key', 'services.cooking_ai.model' => 'test-model']);
        Http::preventStrayRequests();
        $analysis = [
            'summary' => 'Two ingredients are visible.',
            'ingredients' => [['name' => 'Tomato', 'category' => 'vegetable', 'confidence' => 'high', 'visual_notes' => 'Red and round.', 'insights' => 'Sweet and tangy.']],
            'meal_ideas' => [[
                'name' => 'Tomato omelette',
                'reason' => 'A quick Filipino-style lunch.',
                'servings' => 2,
                'prep_minutes' => 5,
                'cook_minutes' => 10,
                'tools' => ['Frying pan', 'Bowl'],
                'ingredients' => [['name' => 'Tomato', 'quantity' => '1 medium', 'optional' => false], ['name' => 'Egg', 'quantity' => '2 large', 'optional' => false]],
                'steps' => ['Beat eggs.', 'Cook tomato until soft.', 'Add eggs and cook until set.'],
            ]],
        ];
        $geminiResponse = ['candidates' => [[
            'content' => ['parts' => [
                ['thought' => true, 'text' => 'Internal reasoning must not be shown to the user.'],
                ['text' => json_encode($analysis)],
            ]],
        ]]];
        Http::fake(['https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent' => Http::sequence()
            ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
            ->push($geminiResponse)]);
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/dWQAAAAASUVORK5CYII=';

        $this->postJson('/api/v1/ingredient-scale', [
            'images' => [
                ['mime_type' => 'image/png', 'data' => $png],
                ['mime_type' => 'image/png', 'data' => $png],
            ],
            'context' => 'I have potatoes, chili, and flour. Suggest a quick Filipino lunch.',
        ])->assertOk()
            ->assertJsonPath('ingredients.0.name', 'Tomato')
            ->assertJsonPath('meal_ideas.0.name', 'Tomato omelette')
            ->assertJsonPath('meal_ideas.0.ingredients.0.quantity', '1 medium')
            ->assertJsonStructure(['saved_recommendation_id']);

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $user->id,
            'context' => 'I have potatoes, chili, and flour. Suggest a quick Filipino lunch.',
        ]);

        Http::assertSent(fn ($request): bool => count($request['contents'][0]['parts']) === 3
            && $request['contents'][0]['parts'][1]['inline_data']['mime_type'] === 'image/png'
            && str_contains($request['contents'][0]['parts'][0]['text'], 'I have potatoes, chili, and flour')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'specific quantity and unit')
            && str_contains($request['contents'][0]['parts'][0]['text'], '5 meal recommendations'));
        Http::assertSentCount(2);
    }

    public function test_saved_ai_recommendations_are_private_to_the_current_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->aiRecommendations()->create([
            'summary' => 'Potato ideas.',
            'ingredients' => [['name' => 'Potato']],
            'meal_ideas' => [['name' => 'Chili potato fritters']],
            'context' => 'Quick dinner',
        ]);
        $otherUser->aiRecommendations()->create([
            'summary' => 'Private idea.',
            'ingredients' => [['name' => 'Rice']],
            'meal_ideas' => [['name' => 'Rice bowl']],
        ]);

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/recommendations')
            ->assertOk()
            ->assertJsonCount(1, 'recommendations')
            ->assertJsonPath('recommendations.0.summary', 'Potato ideas.')
            ->assertJsonMissing(['summary' => 'Private idea.']);
    }
}
