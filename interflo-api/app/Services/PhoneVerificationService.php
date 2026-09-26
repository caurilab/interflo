<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SmsSender;
use App\Models\PhoneVerificationChallenge;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Vérification du numéro de téléphone par code OTP (I-8).
 *
 * Le code n'est JAMAIS stocké en clair (hash uniquement). L'envoi passe par
 * le contrat SmsSender — aucun provider n'est choisi, l'implémentation par
 * défaut écrit dans le log (LogSmsSender).
 */
class PhoneVerificationService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Crée un challenge et envoie le code par SMS.
     *
     * Réponse identique que le numéro soit connu ou non (non-énumération) :
     * la méthode ne dit jamais si un joueur existe pour ce numéro.
     */
    public function requestCode(string $phone): void
    {
        $length = (int) config('interflo.otp_length', 6);
        $ttl = (int) config('interflo.otp_ttl_seconds', 300);

        // Code numérique aléatoire cryptographiquement sûr, de `otp_length` chiffres.
        $code = str_pad(
            (string) random_int(0, (10 ** $length) - 1),
            $length,
            '0',
            STR_PAD_LEFT,
        );

        PhoneVerificationChallenge::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds($ttl),
            'attempts' => 0,
        ]);

        $this->smsSender->send($phone, __('interflo.otp_sms', [
            'code' => $code,
            'minutes' => intdiv($ttl, 60),
        ]));
    }

    /**
     * Vérifie un code et renvoie le joueur (créé au besoin, numéro vérifié),
     * ou null si la vérification échoue — quelle que soit la cause (code faux,
     * expiré, tentatives dépassées, challenge inexistant). Pas d'oracle.
     */
    public function verify(string $phone, string $code): ?Player
    {
        $maxAttempts = (int) config('interflo.otp_max_attempts', 5);

        /** @var PhoneVerificationChallenge|null $challenge */
        $challenge = PhoneVerificationChallenge::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        // Une seule réponse d'échec pour toutes les causes (non-énumération).
        if ($challenge === null || ! $challenge->isPending()) {
            return null;
        }

        if ($challenge->attempts >= $maxAttempts) {
            return null;
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            // Chaque tentative ratée rapproche le challenge de sa mort.
            $challenge->increment('attempts');

            return null;
        }

        return DB::transaction(function () use ($challenge, $phone): Player {
            $challenge->forceFill(['consumed_at' => now()])->save();

            // Crée le joueur s'il n'existe pas, marque le numéro vérifié (I-8).
            $player = Player::query()->firstOrCreate(['phone' => $phone]);
            if (! $player->hasVerifiedPhone()) {
                $player->forceFill(['phone_verified_at' => now()])->save();
            }

            return $player;
        });
    }
}
