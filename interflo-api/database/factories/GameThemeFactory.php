<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameTheme>
 */
class GameThemeFactory extends Factory
{
    protected $model = GameTheme::class;

    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'title' => 'Thème '.$this->faker->unique()->numerify('##'),
            // Par défaut, la partie concerne les téléspectateurs à domicile.
            'population' => Player::POPULATION_HOME,
            'status' => GameTheme::STATUS_ACTIVE,
        ];
    }

    /** Partie pour le public en studio (I-1 : population séparée). */
    public function studio(): static
    {
        return $this->state(fn (): array => ['population' => Player::POPULATION_STUDIO]);
    }

    /** Partie terminée (I-28). */
    public function finished(): static
    {
        return $this->state(fn (): array => ['status' => GameTheme::STATUS_FINISHED]);
    }
}
