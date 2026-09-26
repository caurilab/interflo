<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GameRound;
use App\Models\GameTheme;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * Seeder GameRound — idempotent, avec garde-fou production.
 *
 * Programme la première manche du thème de démonstration depuis sa question
 * validée (EX-40). Les manches suivantes se créent en direct par l'animateur
 * — rien d'autre à semer.
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameRoundSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (GameRound::query()->exists()) {
            return;
        }

        $theme = GameTheme::query()->first();
        if ($theme === null) {
            return;
        }

        $question = Question::query()
            ->where('theme_id', $theme->id)
            ->where('round_number', 1)
            ->whereNotNull('validated_at')
            ->first();
        if ($question === null) {
            return;
        }

        GameRound::factory()->for($theme, 'theme')->for($question)->create(['round_number' => 1]);
    }
}
