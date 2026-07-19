<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed customer zero (gymdog) and a staff login. Idempotent — safe to run
     * on every deploy.
     */
    public function run(): void
    {
        Tenant::updateOrCreate(
            ['slug' => 'gymdog'],
            [
                'name' => 'GymDog Fitness',
                'domain' => 'gymdog.fitness',
                'plan' => 'platform',
                'status' => 'active',
                'settings' => [
                    'theme' => 'gymdog',
                    'currency' => 'EUR',
                    'locale' => 'en',
                    'timezone' => 'Europe/Malta',
                    'vat_number' => null,
                    'contact' => ['email' => 'info@gymdog.fitness'],
                    // Palette lifted from the current Zank theme (spec §4a).
                    'palette' => [
                        'primary' => '#1f3d2b',   // dark forest green
                        'secondary' => '#d8c3a5', // warm tan / beige
                        'accent' => '#c85c6b',    // muted pink/red
                    ],
                    // Free delivery over €50, Malta only (site audit).
                    'shipping' => ['free_over_cents' => 5000, 'country' => 'MT'],
                ],
            ]
        );

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'alex@gymdog.fitness')],
            [
                'name' => env('ADMIN_NAME', 'Alex Ellul'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ]
        );
    }
}
