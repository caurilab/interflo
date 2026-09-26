<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder Tenant — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (Tenant::query()->exists()) {
            return;
        }

        // Une chaîne de démonstration.
        $tenant = Tenant::factory()->create([
            'name' => 'Chaîne Démo',
            'slug' => 'demo',
        ]);

        $this->callWith(EmissionSeeder::class, ['tenant' => $tenant]);
    }
}
