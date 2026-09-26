<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder TenantGameConfig — idempotent, avec garde-fou production.
 *
 * Crée une configuration par défaut pour chaque tenant qui n'en a pas.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class TenantGameConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : une configuration par tenant, sans écraser l'existant.
        Tenant::query()
            ->whereDoesntHave('gameConfig')
            ->each(fn (Tenant $tenant): mixed => $tenant->gameConfig()->create());
    }
}
