<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Compte administrateur Filament de démonstration (idempotent).
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')],
        );

        // Données de démonstration Interflo (chaque seeder est idempotent).
        $this->call([
            PlayerSeeder::class,
            TenantSeeder::class, // enchaîne Emission → GameSession → PairingCode
            TenantGameConfigSeeder::class,
            GameThemeSeeder::class, // enchaîne Question
            GameRoundSeeder::class,
            // RoundPlayerStateSeeder et GameThemeWinnerSeeder : données
            // d'exécution du direct, seeders volontairement vides.
            RoundPlayerStateSeeder::class,
            GameThemeWinnerSeeder::class,
        ]);
    }
}
