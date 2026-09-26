<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Emission;
use App\Models\GameSession;
use Illuminate\Database\Seeder;

/**
 * Seeder GameSession — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class GameSessionSeeder extends Seeder
{
    public function run(?Emission $emission = null): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (GameSession::query()->exists()) {
            return;
        }

        // Une session de jeu en direct.
        $emission ??= Emission::query()->firstOrFail();

        $session = GameSession::factory()->for($emission)->create();

        $this->callWith(PairingCodeSeeder::class, ['session' => $session]);
    }
}
