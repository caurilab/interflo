<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameTheme;
use App\Models\GameThemeWinner;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameThemeWinner>
 */
class GameThemeWinnerFactory extends Factory
{
    protected $model = GameThemeWinner::class;

    public function definition(): array
    {
        return [
            'theme_id' => GameTheme::factory()->finished(),
            'player_id' => Player::factory(),
            // Règle « tous les survivants » : pas de rang. Le tirage au sort
            // attribue un rang (I-28).
            'rank' => null,
        ];
    }
}
