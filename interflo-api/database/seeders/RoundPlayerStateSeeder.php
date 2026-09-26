<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder RoundPlayerState — idempotent, avec garde-fou production.
 *
 * ⚠️ Volontairement vide : les états joueur/manche sont des données
 * d'EXÉCUTION du direct (service de la question, réponse, verdict serveur),
 * pas des données de démonstration. Le seeder existe pour la convention
 * « un seeder par modèle » et reste un no-op documenté.
 *
 * PROVISOIRE — docs/07 §5.
 */
class RoundPlayerStateSeeder extends Seeder
{
    public function run(): void
    {
        // Données d'exécution du direct : rien à semer (voir docblock).
    }
}
