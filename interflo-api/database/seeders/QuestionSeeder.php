<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GameTheme;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder Question — idempotent, avec garde-fou production.
 *
 * Banque de démonstration : une question validée par manche visée (1..5,
 * scellé I-27), majoritairement « plateau » avec une part de culture
 * générale (I-32 — répartition exacte non arrêtée par le PO).
 *
 * ⚠️ La validation de démonstration utilise le premier utilisateur du panel
 * (l'agent humain, I-33 / EX-40). En production : rien n'est semé.
 *
 * PROVISOIRE — docs/07 §5.
 */
class QuestionSeeder extends Seeder
{
    public function run(?GameTheme $theme = null): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (Question::query()->exists()) {
            return;
        }

        $validator = User::query()->first();
        if ($validator === null) {
            return;
        }

        $theme ??= GameTheme::query()->first();

        $rounds = (int) config('interflo.sealed.elimination_rounds');
        for ($round = 1; $round <= $rounds; $round++) {
            Question::factory()
                ->forRound($round)
                ->create([
                    'theme_id' => $theme?->id,
                    'body' => "Question de démonstration — manche {$round} ?",
                    'propositions' => ['Alpha', 'Bravo', 'Charlie', 'Delta'],
                    'correct_index' => 0,
                    // Une part de culture générale (I-32 — proportion non arrêtée).
                    'source' => $round === $rounds
                        ? Question::SOURCE_GENERAL_CULTURE
                        : Question::SOURCE_PLATEAU,
                    'validated_by' => $validator->id,
                    'validated_at' => now(),
                ]);
        }
    }
}
