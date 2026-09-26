<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\GameTheme;
use Illuminate\Database\Seeder;

/**
 * Seeder GameTheme — idempotent, avec garde-fou production.
 *
 * Un thème de démonstration (partie d'élimination, I-27) sur la première
 * session de jeu, pour la population à domicile (I-1).
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameThemeSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (GameTheme::query()->exists()) {
            return;
        }

        $session = GameSession::query()->first();
        if ($session === null) {
            return;
        }

        $theme = GameTheme::factory()->for($session)->create(['title' => 'Thème Démo']);

        $this->callWith(QuestionSeeder::class, ['theme' => $theme]);
    }
}
