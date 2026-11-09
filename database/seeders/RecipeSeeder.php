<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\UnitConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $r) {
            $recipe = Recipe::updateOrCreate(
                ['slug' => Str::slug($r['title'])],
                [
                    'title' => $r['title'],
                    'description' => $r['description'],
                    'servings' => $r['servings'],
                    'prep_minutes' => $r['prep'],
                    'cook_minutes' => $r['cook'],
                    'difficulty' => $r['difficulty'],
                ]
            );

            $recipe->ingredients()->delete();
            $recipe->steps()->delete();
            $recipe->equipment()->detach();

            foreach ($r['ingredients'] as $row) {
                [$name, $qty, $unit] = $row;
                $optional = $row[3] ?? false;
                $ingredient = Ingredient::where('name', $name)->firstOrFail();

                $recipe->ingredients()->create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'quantity_base' => UnitConverter::toBase($ingredient, $qty, $unit),
                    'is_optional' => $optional,
                ]);
            }

            foreach ($r['equipment'] as $i => $group) {
                foreach ($group as $name) {
                    $recipe->equipment()->attach(
                        Equipment::where('name', $name)->firstOrFail()->id,
                        ['group_no' => $i + 1]
                    );
                }
            }

            foreach ($r['steps'] as $i => $step) {
                $recipe->steps()->create([
                    'position' => $i + 1,
                    'instruction' => $step[0],
                    'timer_seconds' => $step[1] ?? null,
                ]);
            }
        }
    }

    private function data(): array
    {
        return [
            [
                'title' => 'Chicken Adobo',
                'description' => 'Chicken simmered in soy sauce, vinegar, and garlic.',
                'servings' => 4, 'prep' => 10, 'cook' => 40, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Chicken', 500, 'g'], ['Soy Sauce', 60, 'ml'], ['Vinegar', 60, 'ml'],
                    ['Garlic', 5, 'pc'], ['Black Peppercorn', 2, 'g'], ['Bay Leaf', 2, 'pc'],
                    ['Cooking Oil', 15, 'ml'],
                ],
                'equipment' => [['Stove'], ['Cooking Pot', 'Frying Pan']],
                'steps' => [
                    ['Combine chicken, soy sauce, vinegar, crushed garlic, peppercorns, and bay leaves. Marinate 15 minutes.', 900],
                    ['Bring the mixture to a boil without stirring for the first few minutes, then lower the heat.'],
                    ['Simmer covered until the chicken is tender, about 25 minutes.', 1500],
                    ['Heat oil in a pan and brown the chicken lightly, then return it to the sauce and reduce to your preferred thickness.', 300],
                    ['Make sure the chicken is cooked through with no pink and juices running clear before serving.'],
                ],
            ],
            [
                'title' => 'Pork Adobo',
                'description' => 'Pork braised in soy sauce, vinegar, and garlic.',
                'servings' => 4, 'prep' => 10, 'cook' => 60, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Pork', 500, 'g'], ['Soy Sauce', 60, 'ml'], ['Vinegar', 60, 'ml'],
                    ['Garlic', 5, 'pc'], ['Black Peppercorn', 2, 'g'], ['Bay Leaf', 2, 'pc'],
                ],
                'equipment' => [['Stove'], ['Cooking Pot']],
                'steps' => [
                    ['Place pork, soy sauce, vinegar, crushed garlic, peppercorns, and bay leaves in a pot.'],
                    ['Bring to a boil, then lower the heat and simmer covered until tender, about 45 minutes.', 2700],
                    ['Uncover and simmer until the sauce reduces and the pork begins to brown in its own fat.', 600],
                    ['Cook the pork thoroughly before serving.'],
                ],
            ],
            [
                'title' => 'Lechon Kawali',
                'description' => 'Boiled, then deep-fried crispy pork belly.',
                'servings' => 4, 'prep' => 15, 'cook' => 90, 'difficulty' => 'hard',
                'ingredients' => [
                    ['Pork Belly', 600, 'g'], ['Salt', 10, 'g'], ['Black Peppercorn', 3, 'g'],
                    ['Bay Leaf', 3, 'pc'], ['Garlic', 5, 'pc'], ['Cooking Oil', 500, 'ml'],
                ],
                'equipment' => [['Stove'], ['Cooking Pot'], ['Frying Pan']],
                'steps' => [
                    ['Boil pork belly with salt, peppercorns, bay leaves, and garlic until tender, about 50 minutes.', 3000],
                    ['Remove the pork and let it dry completely, uncovered, for at least 30 minutes. Pat dry before frying.', 1800],
                    ['Heat oil to frying temperature. Keep your face away and lower the pork in gently, since wet skin makes oil spatter.'],
                    ['Fry until deep golden and crisp, then drain on paper.', 600],
                ],
            ],
            [
                'title' => 'Tortang Talong',
                'description' => 'Grilled eggplant dipped in beaten egg and pan-fried.',
                'servings' => 2, 'prep' => 10, 'cook' => 15, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Eggplant', 2, 'pc'], ['Egg', 2, 'pc'], ['Onion', 0.5, 'pc'],
                    ['Salt', 2, 'g'], ['Cooking Oil', 30, 'ml'],
                ],
                'equipment' => [['Stove'], ['Frying Pan']],
                'steps' => [
                    ['Char the eggplants over the flame or in a hot pan until the skin blackens and the flesh is soft.', 480],
                    ['Let cool, peel, and flatten gently with a fork, keeping the stem.'],
                    ['Beat eggs with salt and chopped onion.'],
                    ['Dip the eggplant in the egg mixture, then fry in hot oil until golden on both sides.', 240],
                ],
            ],
            [
                'title' => 'Garlic Fried Rice',
                'description' => 'Rice stir-fried with plenty of toasted garlic.',
                'servings' => 2, 'prep' => 5, 'cook' => 25, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Rice', 200, 'g'], ['Garlic', 6, 'pc'], ['Cooking Oil', 30, 'ml'],
                    ['Salt', 3, 'g'], ['Egg', 1, 'pc', true],
                ],
                'equipment' => [['Rice Cooker', 'Cooking Pot'], ['Stove'], ['Frying Pan']],
                'steps' => [
                    ['Cook the rice and let it cool completely, ideally a day ahead in the fridge.', 1200],
                    ['Fry minced garlic in oil over medium heat until light golden. Do not let it burn.', 90],
                    ['Add the rice, break up clumps, and stir-fry with salt until heated through.', 240],
                    ['Fry an egg on the side if desired.'],
                ],
            ],
            [
                'title' => 'Ginisang Kamatis at Itlog',
                'description' => 'Eggs scrambled with sautéed tomato and onion.',
                'servings' => 2, 'prep' => 5, 'cook' => 10, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Tomato', 3, 'pc'], ['Egg', 3, 'pc'], ['Onion', 1, 'pc'],
                    ['Garlic', 2, 'pc'], ['Cooking Oil', 15, 'ml'], ['Salt', 2, 'g'],
                ],
                'equipment' => [['Stove'], ['Frying Pan']],
                'steps' => [
                    ['Sauté garlic and onion in oil until soft.', 120],
                    ['Add chopped tomatoes and cook until they break down.', 240],
                    ['Pour in beaten eggs with salt and stir gently until set.', 120],
                ],
            ],
            [
                'title' => 'Arroz Caldo',
                'description' => 'Chicken and ginger rice porridge.',
                'servings' => 4, 'prep' => 10, 'cook' => 45, 'difficulty' => 'medium',
                'ingredients' => [
                    ['Rice', 150, 'g'], ['Chicken', 400, 'g'], ['Ginger', 30, 'g'],
                    ['Garlic', 4, 'pc'], ['Onion', 1, 'pc'], ['Fish Sauce', 15, 'ml'],
                    ['Cooking Oil', 15, 'ml'], ['Egg', 2, 'pc', true],
                ],
                'equipment' => [['Stove'], ['Cooking Pot']],
                'steps' => [
                    ['Sauté ginger, garlic, and onion in oil.', 180],
                    ['Add the chicken and cook until the outside is no longer pink.', 300],
                    ['Add rice, fish sauce, and enough water to cover generously. Simmer, stirring occasionally, until the rice is soft and porridge-like.', 1800],
                    ['Make sure the chicken is cooked through before serving. Top with sliced boiled egg if desired.'],
                ],
            ],
            [
                'title' => 'Soy-Garlic Chicken Wings',
                'description' => 'Wings seasoned with soy sauce and garlic, cooked hot and dry.',
                'servings' => 3, 'prep' => 10, 'cook' => 25, 'difficulty' => 'easy',
                'ingredients' => [
                    ['Chicken Wings', 500, 'g'], ['Soy Sauce', 30, 'ml'], ['Garlic', 3, 'pc'],
                    ['Salt', 2, 'g'], ['Black Peppercorn', 1, 'g'], ['Cooking Oil', 10, 'ml'],
                ],
                'equipment' => [['Air Fryer', 'Oven']],
                'steps' => [
                    ['Toss wings with soy sauce, minced garlic, salt, pepper, and oil. Rest 15 minutes.', 900],
                    ['Arrange in a single layer in the air fryer basket or on an oven tray.'],
                    ['Cook, turning once halfway, until golden and cooked through with juices running clear.', 1200],
                ],
            ],
        ];
    }
}
