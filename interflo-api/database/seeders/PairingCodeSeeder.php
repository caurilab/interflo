<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\PairingCode;
use Illuminate\Database\Seeder;

/**
 * Seeder PairingCode — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PairingCodeSeeder extends Seeder
{
    public function run(?GameSession $session = null): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (PairingCode::query()->exists()) {
            return;
        }

        // Un code d'appairage valide pour la session semée.
        $session ??= GameSession::query()->firstOrFail();

        PairingCode::factory()->for($session, 'gameSession')->create();
    }
}
