<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RoundPlayerState;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Flush d'une réponse depuis Redis vers PostgreSQL (D-002 §4.3).
 *
 * Détaché du chemin de soumission : en production la file est asynchrone
 * (Horizon), donc l'écriture PostgreSQL n'est pas dans le chemin critique du
 * pic. En test, QUEUE_CONNECTION=sync → le job tourne en synchrone.
 */
class PersistAnswer implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $roundId,
        public readonly int $playerId,
        public readonly int $answerIndex,
        public readonly bool $correct,
        public readonly string $answeredAt,
    ) {}

    public function handle(): void
    {
        RoundPlayerState::query()
            ->where('round_id', $this->roundId)
            ->where('player_id', $this->playerId)
            ->update([
                'answered_at' => $this->answeredAt,
                'answer_index' => $this->answerIndex,
                'is_correct' => $this->correct,
                'rejected_reason' => null,
            ]);
    }
}
