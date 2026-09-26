<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlayerSession;
use Illuminate\Database\Seeder;

/**
 * Seeder PlayerSession — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PlayerSessionSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (PlayerSession::query()->exists()) {
            return;
        }

        // Aucun attachement de démonstration : il naît du flux d'appairage réel.
    }
}
