<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameRound;
use App\Models\Player;
use App\Models\RoundPlayerState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoundPlayerState>
 */
class RoundPlayerStateFactory extends Factory
{
    protected $model = RoundPlayerState::class;

    public function definition(): array
    {
        return [
            'round_id' => GameRound::factory(),
            'player_id' => Player::factory(),
            // Question servie à ce joueur (EX-20) — base de sa fenêtre
            // personnelle. Précision ms (plancher anti-automatisation, I-30).
            'served_at' => now(),
            'answered_at' => null,
            'answer_index' => null,
            'is_correct' => null,
            'rejected_reason' => null,
        ];
    }

    /** Le joueur a répondu ; le verdict est calculé serveur (R-4). */
    public function answered(bool $correct = true, int $answerIndex = 0): static
    {
        return $this->state(fn (): array => [
            'answered_at' => now(),
            'answer_index' => $answerIndex,
            'is_correct' => $correct,
        ]);
    }
}
