<?php

namespace Database\Seeders;

use App\Models\AiRecommendation;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userEmail = (string) env('SEED_USER_EMAIL', 'user@tuklasfood.test');
        $user = User::where('email', $userEmail)->firstOrFail();

        AiRecommendation::updateOrCreate(
            ['user_id' => $user->id, 'context' => '__seeded_demo_recommendation__'],
            [
                'summary' => 'Sample scan preview: ingredients for Filipino home cooking',
                'ingredients' => [
                    [
                        'name' => 'Bangus',
                        'confidence' => 'sample',
                        'visual_notes' => 'Example record only; no photo was analyzed.',
                        'insights' => 'A seeded example for previewing saved scan recommendations.',
                    ],
                    [
                        'name' => 'Calamansi',
                        'confidence' => 'sample',
                        'visual_notes' => 'Example record only; no photo was analyzed.',
                        'insights' => 'Often paired with fish and savory dishes.',
                    ],
                ],
                'meal_ideas' => [
                    [
                        'name' => 'Pan-Fried Bangus with Calamansi',
                        'reason' => 'A simple example using the sample ingredients.',
                        'servings' => 2,
                        'prep_minutes' => 10,
                        'cook_minutes' => 15,
                        'ingredients' => [
                            ['quantity' => '500 g', 'name' => 'Bangus'],
                            ['quantity' => '5 g', 'name' => 'Salt'],
                            ['quantity' => '30 ml', 'name' => 'Cooking oil'],
                            ['quantity' => '4 pcs', 'name' => 'Calamansi', 'optional' => true],
                        ],
                        'tools' => ['Stove', 'Frying pan'],
                        'steps' => [
                            'Pat the fish dry and season it with salt.',
                            'Heat oil in a frying pan over medium heat.',
                            'Fry until crisp outside and fully cooked through, turning carefully once.',
                            'Serve with calamansi on the side.',
                        ],
                    ],
                ],
            ],
        );
    }
}
