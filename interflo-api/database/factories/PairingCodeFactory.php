<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\PairingCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PairingCode>
 */
class PairingCodeFactory extends Factory
{
    protected $model = PairingCode::class;

    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            // Code tiré dans l'alphabet lisible à l'oral (EX-03).
            'code' => strtoupper($this->faker->bothify('??????')),
            'valid_from' => now(),
            'valid_until' => now()->addSeconds(
                (int) config('interflo.pairing_code_rotation_seconds', 20)
            ),
        ];
    }

    /** Code déjà mort : un code photographié ne résout plus (EX-04). */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'valid_from' => now()->subMinutes(2),
            'valid_until' => now()->subMinute(),
        ]);
    }
}
