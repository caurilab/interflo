<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GameRound;
use App\Models\GameTheme;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Ouverture d'une fenêtre de manche — poussée à la population de la session
 * (D-002 §4.1 : push Reverb pour l'ouverture et la question, I-3).
 *
 * ⚠️ CA-07 / INV-2 : la charge utile ne porte JAMAIS correct_index — la
 * question servie est body + 4 propositions (scellé I-4), rien d'autre.
 * ⚠️ EX-20 : `served_at` n'est PAS dans la charge utile — la fenêtre est
 * PERSONNELLE, elle naît côté client à la réception effective (le décalage
 * de diffusion est ainsi respecté). `window_seconds`, lui, est partagé.
 *
 * Transport : le polling construit en session 4 reste le transport dégradé
 * (D-1) — ce push n'est qu'un accélérateur de latence, pas un remplacement.
 */
class RoundOpened implements ShouldBroadcast
{
    public function __construct(
        public readonly GameTheme $theme,
        public readonly GameRound $round,
    ) {}

    /** Canal public : une population d'une session (D-002 §4.1). */
    public function broadcastOn(): array
    {
        $sessionId = $this->theme->game_session_id;
        $population = $this->theme->population;

        return [new Channel("session.{$sessionId}.{$population}")];
    }

    /** Nom d'événement stable côté client : Echo `.listen('.round.opened')`. */
    public function broadcastAs(): string
    {
        return 'round.opened';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $windowSeconds = $this->theme->gameSession->emission->tenant->gameConfig?->effectiveAnswerWindowSeconds()
            ?? (int) config('interflo.answer_window_seconds');

        return [
            'server_time' => now()->toISOString(),
            'theme' => ['id' => $this->theme->id, 'title' => $this->theme->title],
            'round' => [
                'id' => $this->round->id,
                'round_number' => $this->round->round_number,
                'window_seconds' => $windowSeconds,
                // ⚠️ INV-2 : body + 4 propositions SEULEMENT (CA-07).
                'question' => [
                    'body' => $this->round->question->body,
                    'propositions' => array_values($this->round->question->propositions),
                ],
            ],
        ];
    }
}
