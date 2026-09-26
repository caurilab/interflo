<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Emission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder Emission — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class EmissionSeeder extends Seeder
{
    public function run(?Tenant $tenant = null): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (Emission::query()->exists()) {
            return;
        }

        // Une émission de démonstration rattachée à la chaîne semée.
        $tenant ??= Tenant::query()->firstOrFail();

        $emission = Emission::factory()->for($tenant)->create([
            'title' => 'Émission de démonstration',
        ]);

        $this->callWith(GameSessionSeeder::class, ['emission' => $emission]);
    }
}
