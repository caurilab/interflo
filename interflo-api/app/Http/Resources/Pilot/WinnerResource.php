<?php

declare(strict_types=1);

namespace App\Http\Resources\Pilot;

use App\Models\GameThemeWinner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Gagnant persisté d'un thème (fin de partie, I-28). Côté pilotage
 * uniquement — côté joueur, chacun ne reçoit que SON bit 'winner'.
 *
 * @mixin GameThemeWinner
 */
class WinnerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var GameThemeWinner $winner */
        $winner = $this->resource;

        return [
            'player_id' => $winner->player_id,
            'rank' => $winner->rank,
        ];
    }
}
