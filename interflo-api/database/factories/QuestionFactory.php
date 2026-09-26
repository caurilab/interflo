<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            // Banque : non rattachée à un thème tant que non programmée.
            'theme_id' => null,
            'round_number' => 1,
            'body' => $this->faker->sentence().' ?',
            // Exactement 4 propositions (scellé I-4).
            'propositions' => [
                $this->faker->word(),
                $this->faker->word(),
                $this->faker->word(),
                $this->faker->word(),
            ],
            'correct_index' => 0,
            // I-32 : majoritairement tirée de l'émission par défaut.
            'source' => Question::SOURCE_PLATEAU,
            // EX-40 : NON validée par défaut — pas de validation humaine,
            // pas de diffusion.
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    /** Question validée par un humain (EX-40) : diffusable. */
    public function validated(): static
    {
        return $this->state(fn (): array => [
            'validated_by' => User::factory(),
            'validated_at' => now(),
        ]);
    }

    /** Question de culture générale (I-32 : petit pourcentage). */
    public function generalCulture(): static
    {
        return $this->state(fn (): array => ['source' => Question::SOURCE_GENERAL_CULTURE]);
    }

    /** Question visant une manche donnée (1..5, scellé I-27). */
    public function forRound(int $roundNumber): static
    {
        return $this->state(fn (): array => ['round_number' => $roundNumber]);
    }
}
