<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\UserIngredient;
use App\Support\UnitConverter;
use Illuminate\Database\Seeder;

class DemoKitchenSeeder extends Seeder
{
    public function run(): void
    {
        $userEmail = (string) env('SEED_USER_EMAIL', 'user@tuklasfood.test');
        $user = User::where('email', $userEmail)->firstOrFail();

        $pantry = [
            'Chicken' => [500, 'g', 'Freezer', 7],
            'Garlic' => [12, 'pc', 'Cupboard', null],
            'Onion' => [3, 'pc', 'Cupboard', null],
            'Tomato' => [4, 'pc', 'Counter', 5],
            'Eggplant' => [2, 'pc', 'Counter', 4],
            'Soy Sauce' => [250, 'ml', 'Cupboard', null],
            'Vinegar' => [250, 'ml', 'Cupboard', null],
            'Cooking Oil' => [500, 'ml', 'Cupboard', null],
            'Salt' => [250, 'g', 'Cupboard', null],
            'Black Peppercorn' => [30, 'g', 'Cupboard', null],
            'Bay Leaf' => [10, 'pc', 'Cupboard', null],
            'Rice' => [1000, 'g', 'Cupboard', null],
            'Egg' => [6, 'pc', 'Fridge', 14],
            'Ginger' => [100, 'g', 'Fridge', 10],
            'Fish Sauce' => [150, 'ml', 'Cupboard', null],
            'Pork' => [500, 'g', 'Freezer', 14],
            'Bangus' => [500, 'g', 'Freezer', 10],
            'Beef Sirloin' => [350, 'g', 'Freezer', 14],
            'Carrot' => [3, 'pc', 'Fridge', 12],
            'Potato' => [4, 'pc', 'Cupboard', 21],
            'Cabbage' => [500, 'g', 'Fridge', 7],
            'Bell Pepper' => [2, 'pc', 'Fridge', 7],
            'Coconut Milk' => [400, 'ml', 'Cupboard', 180],
            'Calamansi' => [8, 'pc', 'Fridge', 10],
            'Sugar' => [250, 'g', 'Cupboard', null],
            'Oyster Sauce' => [150, 'ml', 'Cupboard', null],
        ];

        foreach ($pantry as $ingredientName => [$quantity, $unit, $location, $expiryDays]) {
            $ingredient = Ingredient::where('name', $ingredientName)->firstOrFail();

            UserIngredient::updateOrCreate(
                ['user_id' => $user->id, 'ingredient_id' => $ingredient->id],
                [
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'quantity_base' => UnitConverter::toBase($ingredient, (float) $quantity, $unit),
                    'purchase_date' => today()->subDays(2)->toDateString(),
                    'expiration_date' => $expiryDays === null ? null : today()->addDays($expiryDays)->toDateString(),
                    'storage_location' => $location,
                ],
            );
        }

        $equipmentIds = Equipment::whereIn('name', [
            'Stove',
            'Frying Pan',
            'Cooking Pot',
            'Rice Cooker',
        ])->pluck('id');

        $user->equipment()->syncWithoutDetaching($equipmentIds);
    }
}
