<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder GameThemeWinner — idempotent, avec garde-fou production.
 *
 * ⚠️ Volontairement vide : les gagnants sont produits UNE SEULE FOIS par la
 * fin de partie (I-28), jamais semés. Le seeder existe pour la convention
 * « un seeder par modèle » et reste un no-op documenté.
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameThemeWinnerSeeder extends Seeder
{
    public function run(): void
    {
        // Preuve de fin de partie : rien à semer (voir docblock).
    }
}
