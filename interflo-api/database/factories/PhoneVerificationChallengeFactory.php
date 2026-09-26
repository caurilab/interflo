<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PhoneVerificationChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<PhoneVerificationChallenge>
 */
class PhoneVerificationChallengeFactory extends Factory
{
    protected $model = PhoneVerificationChallenge::class;

    public function definition(): array
    {
        return [
            'phone' => '+33'.$this->faker->numerify('6########'),
            // Hash du code « 123456 » — jamais de code en clair, même en test.
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'consumed_at' => null,
        ];
    }

    /** Challenge expiré. */
    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }

    /** Challenge déjà consommé. */
    public function consumed(): static
    {
        return $this->state(fn (): array => ['consumed_at' => now()]);
    }
}
