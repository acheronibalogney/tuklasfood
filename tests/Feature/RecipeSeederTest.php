<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Database\Seeders\RecipeSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(RecipeSeeder::class);
    }

    public function test_eight_recipes_are_seeded(): void
    {
        $this->assertSame(8, Recipe::count());
    }

    public function test_every_recipe_is_complete(): void
    {
        foreach (Recipe::with(['ingredients', 'steps', 'equipment'])->get() as $recipe) {
            $this->assertGreaterThan(0, $recipe->ingredients->count(), $recipe->title);
            $this->assertGreaterThan(0, $recipe->steps->count(), $recipe->title);
            $this->assertGreaterThan(0, $recipe->equipment->count(), $recipe->title);
            $this->assertTrue($recipe->ingredients->every(fn ($ingredient) => $ingredient->quantity_base > 0), $recipe->title);
        }
    }

    public function test_wings_recipe_requires_a_specific_cut_and_accepts_two_appliances(): void
    {
        $recipe = Recipe::where('title', 'Soy-Garlic Chicken Wings')->firstOrFail();
        $this->assertTrue($recipe->ingredients->contains(fn ($ingredient) => $ingredient->ingredient->name === 'Chicken Wings' && $ingredient->ingredient->parent_id !== null));
        $this->assertSame(2, $recipe->equipment->where('pivot.group_no', 1)->count());
    }

    public function test_reseeding_does_not_duplicate(): void
    {
        $this->seed(RecipeSeeder::class);
        $this->assertSame(8, Recipe::count());
    }
}
