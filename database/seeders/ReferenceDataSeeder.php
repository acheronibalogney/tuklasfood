<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $cat = fn (string $n) => IngredientCategory::firstOrCreate(['name' => $n])->id;

        $meat = $cat('Meat & Poultry');
        $veg = $cat('Vegetables');
        $pantry = $cat('Pantry & Condiments');
        $grains = $cat('Grains & Staples');
        $dairyEgg = $cat('Eggs & Dairy');
        $seafood = $cat('Seafood');

        $make = fn (int $c, string $name, string $unit, ?float $pw = null, ?int $parent = null) => Ingredient::firstOrCreate(
            ['name' => $name],
            ['ingredient_category_id' => $c, 'base_unit' => $unit, 'piece_weight_g' => $pw, 'parent_id' => $parent]
        );

        $chicken = $make($meat, 'Chicken', 'g');
        foreach (['Chicken Breast', 'Chicken Wings', 'Chicken Thigh', 'Chicken Drumstick'] as $cut) {
            $make($meat, $cut, 'g', null, $chicken->id);
        }

        $pork = $make($meat, 'Pork', 'g');
        foreach (['Pork Belly', 'Pork Shoulder', 'Ground Pork'] as $cut) {
            $make($meat, $cut, 'g', null, $pork->id);
        }

        $beef = $make($meat, 'Beef', 'g');
        foreach (['Beef Sirloin', 'Ground Beef'] as $cut) {
            $make($meat, $cut, 'g', null, $beef->id);
        }

        $make($seafood, 'Bangus', 'g');
        $make($seafood, 'Tilapia', 'g');
        $make($seafood, 'Shrimp', 'g');

        $make($veg, 'Garlic', 'pc', 5);
        $make($veg, 'Onion', 'pc', 110);
        $make($veg, 'Tomato', 'pc', 120);
        $make($veg, 'Eggplant', 'pc', 200);
        $make($veg, 'Ginger', 'g');
        $make($veg, 'Cabbage', 'g');
        $make($veg, 'Carrot', 'pc', 60);
        $make($veg, 'Potato', 'pc', 170);
        $make($veg, 'Bell Pepper', 'pc', 120);
        $make($veg, 'Green Onion', 'pc', 15);
        $make($veg, 'Calamansi', 'pc', 10);
        $make($pantry, 'Soy Sauce', 'ml');
        $make($pantry, 'Vinegar', 'ml');
        $make($pantry, 'Cooking Oil', 'ml');
        $make($pantry, 'Salt', 'g');
        $make($pantry, 'Black Peppercorn', 'g');
        $make($pantry, 'Bay Leaf', 'pc', 0.2);
        $make($pantry, 'Fish Sauce', 'ml');
        $make($pantry, 'Oyster Sauce', 'ml');
        $make($pantry, 'Coconut Milk', 'ml');
        $make($pantry, 'Sugar', 'g');
        $make($pantry, 'Cornstarch', 'g');
        $make($grains, 'Rice', 'g');
        $make($grains, 'Rice Noodles', 'g');
        $make($dairyEgg, 'Egg', 'pc', 50);
        $make($dairyEgg, 'Milk', 'ml');

        foreach (['Stove', 'Frying Pan', 'Cooking Pot', 'Rice Cooker', 'Oven', 'Air Fryer', 'Microwave', 'Steamer', 'Knife', 'Cutting Board', 'Blender'] as $e) {
            Equipment::firstOrCreate(['name' => $e]);
        }
    }
}
