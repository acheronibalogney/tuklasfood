<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\RecipeSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['_token' => 'demo-test-token'])
            ->withHeaders(['X-CSRF-TOKEN' => 'demo-test-token']);
    }

    public function test_homepage_shows_published_recipes_and_cooking_steps(): void
    {
        $this->seed([ReferenceDataSeeder::class, RecipeSeeder::class]);
        Recipe::where('slug', 'pork-adobo')->update(['is_published' => false]);

        $this->get('/')->assertOk()
            ->assertSee('Less wondering.')
            ->assertSee('Chicken Adobo')
            ->assertSee('Soy Sauce')
            ->assertSee('Marinate 15 minutes.')
            ->assertDontSee('Pork Adobo')
            ->assertDontSee('Try the demo kitchen');
    }

    public function test_local_demo_logs_in_only_the_sample_user_and_opens_the_kitchen(): void
    {
        $this->app['env'] = 'local';
        $sample = User::factory()->create(['email' => 'user@tuklasfood.test']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get('/')->assertOk()->assertSee('Try the demo kitchen');
        $this->post('/demo-login', ['email' => $admin->email, 'role' => 'admin'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($sample);
        $this->get('/dashboard')->assertOk()->assertSee('Your recipe collection')->assertSee($sample->name)
            ->assertViewHas('pantryCount', 0)->assertViewHas('equipmentCount', 0);
    }

    public function test_production_does_not_allow_demo_login(): void
    {
        $this->app['env'] = 'production';
        User::factory()->create(['email' => 'user@tuklasfood.test']);

        $this->get('/')->assertOk()->assertDontSee('Try the demo kitchen');
        $this->post('/demo-login')->assertNotFound();
        $this->assertGuest();
    }

    public function test_missing_demo_account_returns_a_helpful_error(): void
    {
        $this->app['env'] = 'local';
        $this->post('/demo-login')->assertRedirect('/')
            ->assertSessionHasErrors(['demo' => 'The demo account is unavailable. You can create your own account instead.']);
        $this->assertGuest();
    }

    public function test_demo_does_not_authenticate_an_account_promoted_to_admin(): void
    {
        $this->app['env'] = 'local';
        User::factory()->create(['email' => 'user@tuklasfood.test', 'role' => 'admin']);

        $this->get('/')->assertOk()->assertDontSee('Try the demo kitchen');
        $this->post('/demo-login')->assertSessionHasErrors('demo');
        $this->assertGuest();
    }

    public function test_demo_cannot_bypass_two_factor_authentication(): void
    {
        $this->app['env'] = 'local';
        User::factory()->create(['email' => 'user@tuklasfood.test', 'two_factor_secret' => encrypt('test-secret')]);

        $this->post('/demo-login')->assertSessionHasErrors('demo');
        $this->assertGuest();
    }

    public function test_guests_cannot_open_the_kitchen(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_signed_in_users_are_not_switched_to_the_demo_account(): void
    {
        $this->app['env'] = 'local';
        User::factory()->create(['email' => 'user@tuklasfood.test']);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/demo-login')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_demo_login_is_rate_limited(): void
    {
        $this->app['env'] = 'local';

        foreach (range(1, 6) as $attempt) {
            $this->post('/demo-login')->assertRedirect('/');
        }

        $this->post('/demo-login')->assertStatus(429);
    }

    public function test_login_lists_both_local_accounts_and_admin_demo_opens_admin_dashboard(): void
    {
        $this->app['env'] = 'local';
        $admin = User::factory()->create(['email' => 'admin@tuklasfood.test', 'role' => 'admin']);

        $this->get('/login')->assertOk()->assertSee('user@tuklasfood.test')->assertSee('admin@tuklasfood.test');
        $this->post('/demo-login/admin', ['email' => 'someone@example.test'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
    }

    public function test_admin_demo_is_disabled_in_production(): void
    {
        $this->app['env'] = 'production';
        User::factory()->create(['email' => 'admin@tuklasfood.test', 'role' => 'admin']);

        $this->get('/login')->assertOk()->assertDontSee('Try a testing account');
        $this->post('/demo-login/admin')->assertNotFound();
        $this->assertGuest();
    }

    public function test_admin_demo_requires_an_actual_admin_without_two_factor(): void
    {
        $this->app['env'] = 'local';
        $account = User::factory()->create(['email' => 'admin@tuklasfood.test', 'role' => 'user']);

        $this->post('/demo-login/admin')->assertSessionHasErrors('demo');
        $this->assertGuest();

        $account->forceFill(['role' => 'admin', 'two_factor_secret' => encrypt('test-secret')])->save();
        $this->post('/demo-login/admin')->assertSessionHasErrors('demo');
        $this->assertGuest();
    }
}
