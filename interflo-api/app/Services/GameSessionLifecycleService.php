<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameSession;
use DomainException;

/**
 * Cycle de vie d'une session de jeu : la session vit et meurt avec l'émission
 * (I-16). Transitions : scheduled → live (démarrer) → ended (clôturer).
 */
class GameSessionLifecycleService
{
    /**
     * Démarre une session programmée : statut live, horodatage de début.
     *
     * @throws DomainException si la session n'est pas programmée.
     */
    public function start(GameSession $session): GameSession
    {
        if ($session->status !== GameSession::STATUS_SCHEDULED) {
            // Seule une session programmée peut démarrer.
            throw new DomainException('only_scheduled_session_can_start');
        }

        $session->forceFill([
            'status' => GameSession::STATUS_LIVE,
            'started_at' => now(),
        ])->save();

        return $session;
    }

    /**
     * Clôture une session en direct : statut ended, horodatage de fin.
     * Session terminée, accès mort (I-16).
     *
     * @throws DomainException si la session n'est pas en direct.
     */
    public function end(GameSession $session): GameSession
    {
        if ($session->status !== GameSession::STATUS_LIVE) {
            // Seule une session en direct peut être clôturée.
            throw new DomainException('only_live_session_can_end');
        }

        $session->forceFill([
            'status' => GameSession::STATUS_ENDED,
            'ended_at' => now(),
        ])->save();

        return $session;
    }
}
