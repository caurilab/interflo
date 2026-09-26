<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameSession;
use App\Models\PairingCode;

/**
 * Génération, rotation et résolution des codes d'appairage (I-14, I-15, I-18).
 *
 * Le code est ROTATIF : un code expiré ne résout plus (EX-04). ⚠️ EX-05
 * (proposition NON validée) : un joueur déjà appairé garde sa session quand
 * le code tourne — implémenté ainsi ici, à signaler au PO.
 */
class PairingCodeService
{
    /**
     * Tire un code dans l'alphabet lisible à l'oral (EX-03), sans caractères
     * ambigus, à la longueur configurée (points de départ non validés).
     */
    public function generateCode(): string
    {
        $alphabet = (string) config('interflo.short_code_alphabet', 'ABCDEFGHJKMNPQRTUVWXYZ2346789');
        $length = (int) config('interflo.short_code_length', 6);
        $max = strlen($alphabet) - 1;

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Fait tourner le code d'une session : le code courant meurt, un nouveau
     * naît, valable `pairing_code_rotation_seconds` secondes (I-18).
     *
     * Ne touche PAS aux joueurs déjà attachés (EX-05, proposition non validée).
     */
    public function rotate(GameSession $session): PairingCode
    {
        // Le code courant meurt immédiatement.
        PairingCode::query()
            ->where('game_session_id', $session->id)
            ->currentlyValid()
            ->update(['valid_until' => now()]);

        return $this->createCode($session);
    }

    /**
     * Crée un nouveau code valide pour la session.
     */
    public function createCode(GameSession $session): PairingCode
    {
        return PairingCode::query()->create([
            'game_session_id' => $session->id,
            'code' => $this->generateCode(),
            'valid_from' => now(),
            'valid_until' => now()->addSeconds(
                (int) config('interflo.pairing_code_rotation_seconds', 20)
            ),
        ]);
    }

    /**
     * Résout un code vers sa session de jeu, ou null si inconnu ou expiré
     * (EX-04). Le code DÉSIGNE, il n'autorise rien (INV-6 / R-9).
     */
    public function resolve(string $code): ?GameSession
    {
        /** @var PairingCode|null $pairingCode */
        $pairingCode = PairingCode::query()
            ->where('code', strtoupper(trim($code)))
            ->currentlyValid()
            ->latest('id')
            ->first();

        return $pairingCode?->gameSession?->load('emission.tenant');
    }
}
