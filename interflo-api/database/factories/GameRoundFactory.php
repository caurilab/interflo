<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameRound;
use App\Models\GameTheme;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRound>
 */
class GameRoundFactory extends Factory
{
    protected $model = GameRound::class;

    public function definition(): array
    {
        return [
            'theme_id' => GameTheme::factory(),
            'round_number' => 1,
            // Une manche porte une question validée (EX-40).
            'question_id' => Question::factory()->validated(),
            'window_opened_at' => null,
            'window_closed_at' => null,
            'status' => GameRound::STATUS_PENDING,
        ];
    }

    /** Manche ouverte par l'animateur (I-2). */
    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => GameRound::STATUS_OPEN,
            'window_opened_at' => now(),
        ]);
    }

    /** Manche refermée par l'animateur (I-2). */
    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => GameRound::STATUS_CLOSED,
            'window_opened_at' => now()->subMinute(),
            'window_closed_at' => now(),
        ]);
    }
}
