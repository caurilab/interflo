<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PhoneVerificationChallenge;
use Illuminate\Database\Seeder;

/**
 * Seeder PhoneVerificationChallenge — idempotent, avec garde-fou production.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PhoneVerificationChallengeSeeder extends Seeder
{
    public function run(): void
    {
        // Garde-fou : aucune donnée de démonstration en production.
        if (app()->isProduction()) {
            return;
        }

        // Idempotent : ne rien faire si la table n'est pas vide.
        if (PhoneVerificationChallenge::query()->exists()) {
            return;
        }

        // Aucun challenge de démonstration : les OTP n'ont pas vocation à être semés.
    }
}
