<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            // Numéro E.164 factice.
            'phone' => '+33'.$this->faker->unique()->numerify('6########'),
            // Par défaut, le joueur a déjà vérifié son numéro.
            'phone_verified_at' => now(),
            // ⚠️ PROVISOIRE : recouvrement Voxflo non instruit — population
            // 'home' par défaut, attribution 'studio' à concevoir (I-1).
            'population' => Player::POPULATION_HOME,
        ];
    }

    /** Joueur dont le numéro n'est pas encore vérifié. */
    public function unverified(): static
    {
        return $this->state(fn (): array => ['phone_verified_at' => null]);
    }

    /** Joueur du public présent en studio (I-1). */
    public function studio(): static
    {
        return $this->state(fn (): array => ['population' => Player::POPULATION_STUDIO]);
    }
}
