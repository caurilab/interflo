<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

/**
 * Seeder Player — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (Player::query()->exists()) {
            return;
        }

        // Un joueur de démonstration au numéro vérifié.
        Player::factory()->create(['phone' => '+33600000000']);
    }
}
