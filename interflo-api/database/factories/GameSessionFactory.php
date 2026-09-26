<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Emission;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    protected $model = GameSession::class;

    public function definition(): array
    {
        return [
            'emission_id' => Emission::factory(),
            'status' => GameSession::STATUS_LIVE,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    /** Session programmée, pas encore commencée. */
    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => GameSession::STATUS_SCHEDULED,
            'started_at' => null,
        ]);
    }

    /** Session terminée : l'accès est mort (I-16). */
    public function ended(): static
    {
        return $this->state(fn (): array => [
            'status' => GameSession::STATUS_ENDED,
            'ended_at' => now(),
        ]);
    }
}
