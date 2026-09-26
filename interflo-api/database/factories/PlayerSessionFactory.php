<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerSession>
 */
class PlayerSessionFactory extends Factory
{
    protected $model = PlayerSession::class;

    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'game_session_id' => GameSession::factory(),
            'attached_at' => now(),
            'detached_at' => null,
        ];
    }

    /** Attachement terminé (détaché). */
    public function detached(): static
    {
        return $this->state(fn (): array => ['detached_at' => now()]);
    }
}
