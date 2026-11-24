<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoUserEmail = (string) env('SEED_USER_EMAIL', 'user@tuklasfood.test');

        $this->call([
            ReferenceDataSeeder::class,
            RecipeSeeder::class,
        ]);

        $accounts = [
            ['name' => 'TuklasFood Admin', 'email' => 'admin@tuklasfood.test', 'role' => 'admin'],
            ['name' => 'Sample User',      'email' => $demoUserEmail,           'role' => 'user'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);
            $user->forceFill([
                'name' => $account['name'],
                'role' => $account['role'],
                'password' => Hash::make(env('SEED_PASSWORD', 'change-me-locally')),
                'email_verified_at' => now(),
            ])->save();
        }

        $this->call([
            DemoKitchenSeeder::class,
            DemoRecommendationSeeder::class,
        ]);
    }
}
