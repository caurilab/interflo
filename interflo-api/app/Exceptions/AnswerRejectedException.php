<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Rejet serveur d'une réponse joueur (format élimination).
 *
 * Porte un code d'erreur stable (consommé par mobile et web), un statut HTTP
 * et une clé de traduction — aucun texte en dur (docs/CONVENTIONS.md §2).
 * Rendu en JSON par bootstrap/app.php.
 */
class AnswerRejectedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        public readonly string $translationKey,
    ) {
        parent::__construct($errorCode);
    }

    public static function windowClosed(): self
    {
        // Hors fenêtre, le serveur refuse (I-2 / EX-11 / INV-8 / CA-02).
        return new self('WINDOW_CLOSED', 403, 'interflo.answer.window_closed');
    }

    public static function sessionEnded(): self
    {
        // Émission terminée, accès mort (I-16).
        return new self('SESSION_ENDED', 403, 'interflo.answer.session_ended');
    }

    public static function notAttached(): self
    {
        // Le joueur n'est pas attaché à la session du thème.
        return new self('NOT_ATTACHED', 403, 'interflo.answer.not_attached');
    }

    public static function populationMismatch(): self
    {
        // Les deux populations ne concourent jamais ensemble (I-1 / EX-23).
        return new self('POPULATION_MISMATCH', 403, 'interflo.answer.population_mismatch');
    }

    public static function playerLocked(): self
    {
        // Verrouillé jusqu'à la fin du thème (EX-32).
        return new self('PLAYER_LOCKED', 403, 'interflo.answer.player_locked');
    }

    public static function notServed(): self
    {
        // Pas de served_at : la question n'a jamais été servie à ce joueur
        // (EX-20) — le client doit d'abord interroger l'état de jeu.
        return new self('NOT_SERVED', 403, 'interflo.answer.not_served');
    }

    public static function alreadyAnswered(): self
    {
        // Une seule réponse par joueur et par manche.
        return new self('ALREADY_ANSWERED', 409, 'interflo.answer.already_answered');
    }

    public static function impossibleTimestamp(): self
    {
        // Horodatage physiquement impossible : futur au-delà de la tolérance
        // d'horloge, ou antérieur à served_at (I-6 / EX-17 / INV-3 / CA-03).
        return new self('IMPOSSIBLE_TIMESTAMP', 422, 'interflo.answer.impossible_timestamp');
    }

    public static function tooFast(): self
    {
        // Plancher anti-automatisation (I-30 / EX-18) : aucun humain ne
        // répond aussi vite.
        return new self('TOO_FAST', 422, 'interflo.answer.too_fast');
    }

    public static function windowExceeded(): self
    {
        // Fenêtre personnelle dépassée (EX-20) ou enveloppe serveur couverte.
        return new self('WINDOW_EXCEEDED', 403, 'interflo.answer.window_exceeded');
    }
}
