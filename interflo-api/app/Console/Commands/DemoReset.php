<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Emission;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\GameThemeWinner;
use App\Models\PairingCode;
use App\Models\PhoneVerificationChallenge;
use App\Models\PlayerSession;
use App\Models\Question;
use App\Models\RoundPlayerState;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Régénère une démo Interflo propre : session live + jeton pilote + code
 * d'appairage (fenêtre longue) + 5 questions en banque (validées EX-40,
 * bonne réponse index 0). Imprime les identifiants pour le test de bout en
 * bout.
 *
 * ⚠️ DEV UNIQUEMENT : purge les entités de jeu locales. Garde-fou production.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Régénère la démo locale (session, jeton pilote, code d\'appairage, questions).';

    public function handle(): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('Refusé : ne pas régénérer la démo en production.');

            return self::FAILURE;
        }

        // 1. Nettoyage des entités de jeu (enfants avant parents).
        GameThemeWinner::query()->delete();
        RoundPlayerState::query()->delete();
        GameRound::query()->delete();
        Question::query()->delete();
        GameTheme::query()->delete();
        PlayerSession::query()->delete();
        PairingCode::query()->delete();
        PhoneVerificationChallenge::query()->delete();
        GameSession::query()->delete();

        // 2. Tenant + émission (réutilise l'existant, sinon crée).
        $tenant = Tenant::query()->first()
            ?? Tenant::factory()->create(['name' => 'Chaîne Démo', 'slug' => 'demo']);
        $emission = Emission::query()->first()
            ?? Emission::factory()->for($tenant)->create(['title' => 'Émission de démonstration']);

        // 3. Session live (jeton pilote auto-généré à la création — PROVISOIRE).
        $session = GameSession::factory()->for($emission)->create();

        // 4. Code d'appairage lisible (EX-03) + fenêtre longue pour la démo.
        $alphabet = (string) config('interflo.short_code_alphabet');
        $length = (int) config('interflo.short_code_length');
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        PairingCode::factory()->for($session, 'gameSession')->create([
            'code' => $code,
            'valid_from' => now(),
            'valid_until' => now()->addHours(2),
        ]);

        // 5. 5 questions en banque (validées EX-40, bonne réponse index 0).
        $validator = User::query()->first();
        $ids = [];
        for ($round = 1; $round <= 5; $round++) {
            $question = Question::factory()->forRound($round)->create([
                'theme_id' => null,
                'body' => "Question démo — manche {$round} ?",
                'propositions' => ['Alpha', 'Bravo', 'Charlie', 'Delta'],
                'correct_index' => 0,
                'source' => Question::SOURCE_PLATEAU,
                'validated_by' => $validator?->id,
                'validated_at' => now(),
            ]);
            $ids[] = $question->id;
        }

        // 6. Sortie.
        $this->info("SESSION_ID={$session->id}");
        $this->info("PILOT_TOKEN={$session->pilot_token}");
        $this->info("PAIRING_CODE={$code}");
        $this->info('QUESTION_IDS='.implode(', ', $ids));
        $this->warn('OTP : tail -f storage/logs/laravel.log (LogSmsSender)');

        return self::SUCCESS;
    }
}
