<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AnswerRejectedException;
use App\Jobs\PersistAnswer;
use App\Models\GameRound;
use App\Models\Player;
use App\Models\PlayerSession;
use App\Models\RoundPlayerState;

/**
 * Soumission d'une réponse au format élimination.
 *
 * Contrôles serveur, DANS L'ORDRE DOCUMENTÉ (mission §3) :
 *  1. fenêtre ouverte — état serveur (I-2 / EX-11 / INV-8 / CA-02) ;
 *  2. session vivante (I-16) ;
 *  3. joueur attaché à la session du thème ;
 *  4. population du joueur = population du thème (I-1 / EX-23) ;
 *  5. joueur non verrouillé (EX-32 — déduit, voir EliminationSurvivorService) ;
 *  6. question déjà servie à ce joueur (served_at existe — EX-20) ;
 *  7. pas déjà répondu à cette manche ;
 *  8. horodatage borné (I-6 / EX-17 / INV-3 / CA-03) : futur au-delà de
 *     clock_tolerance_ms, ou antérieur à served_at → rejet ;
 *  9. plancher anti-automatisation (I-30 / EX-18) :
 *     client_timestamp − served_at < anti_automation_floor_ms → rejet ;
 * 10. fenêtre personnelle dépassée (EX-20) → rejet.
 *
 * Fenêtre PERSONNELLE (EX-20) : elle court depuis le served_at de CE joueur,
 * mesurée sur l'horodatage du geste (I-6 : on ne pénalise pas les mauvaises
 * connexions), avec la tolérance d'horloge (EX-16). L'enveloppe serveur
 * couvre fenêtre + server_envelope_seconds depuis l'ouverture de la manche :
 * au-delà, le serveur refuse même un horodatage nominalement dans la fenêtre.
 *
 * Le verdict juste/faux est calculé SERVEUR (INV-2 / R-4) : correct_index ne
 * quitte jamais la base. La réponse HTTP ne porte qu'UN BIT (R-5).
 *
 * ⚠️ PERSISTANCE PROVISOIRE (docs/07 §5) : une ligne mise à jour par geste —
 * ne prétend pas tenir le pic d'écriture, en attente de l'ADR temps réel.
 */
class AnswerService
{
    public function __construct(
        private readonly EliminationSurvivorService $survivors,
        private readonly GameStateRedis $redis,
    ) {}

    /**
     * Enregistre la réponse du joueur et renvoie son verdict (un bit, R-5).
     *
     * @param  int  $clientTimestampMs  horodatage du geste sur l'appareil,
     *                                  en millisecondes epoch (I-6) — borné
     *                                  et validé, jamais cru (INV-3 / R-6).
     * @return array{correct: bool}
     *
     * @throws AnswerRejectedException sur chaque refus, dans l'ordre ci-dessus.
     */
    public function submit(Player $player, GameRound $round, int $answerIndex, int $clientTimestampMs): array
    {
        $theme = $round->theme;

        // 1. Fenêtre ouverte ? Hors fenêtre, le serveur refuse (I-2 / INV-8).
        if (! $round->isOpen()) {
            throw AnswerRejectedException::windowClosed();
        }

        // 2. Émission terminée, accès mort (I-16).
        $session = $theme->gameSession;
        if ($session->hasEnded()) {
            throw AnswerRejectedException::sessionEnded();
        }

        // 3. Le joueur est-il attaché à la session du thème ? (I-17)
        $attached = PlayerSession::query()
            ->where('player_id', $player->id)
            ->where('game_session_id', $session->id)
            ->active()
            ->exists();
        if (! $attached) {
            throw AnswerRejectedException::notAttached();
        }

        // 4. Les deux populations ne concourent jamais ensemble (I-1 / EX-23).
        if (! $player->belongsToPopulation($theme)) {
            throw AnswerRejectedException::populationMismatch();
        }

        // 5. Verrouillé jusqu'à la fin du thème (EX-32) — déduit.
        if ($this->survivors->isLocked($player, $theme)) {
            throw AnswerRejectedException::playerLocked();
        }

        // 6. La question a-t-elle été servie à ce joueur ? (EX-20)
        /** @var RoundPlayerState|null $state */
        $state = RoundPlayerState::query()
            ->where('round_id', $round->id)
            ->where('player_id', $player->id)
            ->first();
        if ($state === null) {
            throw AnswerRejectedException::notServed();
        }

        // 7. Une seule réponse par joueur et par manche. (Un rejet antérieur
        //    ne consomme pas le droit de répondre : answered_at reste null.)
        if ($state->hasAnswered()) {
            throw AnswerRejectedException::alreadyAnswered();
        }

        $servedAtMs = $state->served_at->getTimestampMs();
        $nowMs = now()->getTimestampMs();
        $toleranceMs = (int) config('interflo.clock_tolerance_ms');

        // 8. Horodatage borné (I-6 / EX-17 / INV-3 / CA-03) : rejet de tout
        //    temps physiquement impossible — futur au-delà de la tolérance
        //    d'horloge, ou antérieur au service de la question.
        if ($clientTimestampMs > $nowMs + $toleranceMs || $clientTimestampMs < $servedAtMs) {
            $this->markRejected($state, RoundPlayerState::REJECTED_IMPOSSIBLE_TIMESTAMP);

            throw AnswerRejectedException::impossibleTimestamp();
        }

        // 9. Plancher anti-automatisation (I-30 / EX-18) : aucun humain ne
        //    répond aussi vite. ⚠️ À ne pas confondre avec la durée de
        //    fenêtre (I-31) — deux paramètres distincts.
        $floorMs = (int) config('interflo.anti_automation_floor_ms');
        if ($clientTimestampMs - $servedAtMs < $floorMs) {
            $this->markRejected($state, RoundPlayerState::REJECTED_TOO_FAST);

            throw AnswerRejectedException::tooFast();
        }

        // 10. Fenêtre personnelle dépassée (EX-20) : le geste doit tomber
        //     dans les window_seconds du joueur depuis SON served_at
        //     (tolérance d'horloge incluse, EX-16).
        $windowSeconds = $theme->gameSession->emission->tenant->gameConfig?->effectiveAnswerWindowSeconds()
            ?? (int) config('interflo.answer_window_seconds');
        if ($clientTimestampMs - $servedAtMs > ($windowSeconds * 1000) + $toleranceMs) {
            $this->markRejected($state, RoundPlayerState::REJECTED_WINDOW_EXCEEDED);

            throw AnswerRejectedException::windowExceeded();
        }

        // 10bis. Enveloppe serveur (EX-20) : fenêtre + pire décalage attendu,
        //        comptés depuis l'ouverture de la manche par l'animateur.
        //        Au-delà, le serveur refuse même un horodatage dans la
        //        fenêtre personnelle.
        $envelopeSeconds = $windowSeconds + (int) config('interflo.server_envelope_seconds');
        if ($nowMs > $round->window_opened_at->getTimestampMs() + ($envelopeSeconds * 1000)) {
            $this->markRejected($state, RoundPlayerState::REJECTED_WINDOW_EXCEEDED);

            throw AnswerRejectedException::windowExceeded();
        }

        // Verdict calculé SERVEUR (INV-2 / R-4) : correct_index ne quitte
        // jamais la base.
        $correct = $answerIndex === $round->question->correct_index;

        // État chaud (D-002 §4.3) : la réponse est écrite dans Redis — unicité
        // atomique (HSETNX) + enregistrement — puis flushée vers PostgreSQL par
        // un job en file. PostgreSQL n'est plus dans le chemin critique du pic
        // d'écriture (en test, QUEUE_CONNECTION=sync → job synchrone).
        if (! $this->redis->recordAnswer($round->id, $player->id, $answerIndex, $correct)) {
            throw AnswerRejectedException::alreadyAnswered();
        }

        PersistAnswer::dispatch(
            $round->id,
            $player->id,
            $answerIndex,
            $correct,
            now()->toISOString(),
        );

        // Retour individuel : UN BIT (R-5 / I-29), jamais la bonne réponse.
        return ['correct' => $correct];
    }

    /** Audit provisoire : trace le dernier motif de rejet sur l'état. */
    private function markRejected(RoundPlayerState $state, string $reason): void
    {
        $state->update(['rejected_reason' => $reason]);
    }
}
