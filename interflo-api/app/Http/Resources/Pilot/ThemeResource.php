<?php

declare(strict_types=1);

namespace App\Http\Resources\Pilot;

use App\Models\GameTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Thème d'élimination, côté pilotage animateur (création).
 *
 * @mixin GameTheme
 */
class ThemeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var GameTheme $theme */
        $theme = $this->resource;

        return [
            'id' => $theme->id,
            'game_session_id' => $theme->game_session_id,
            'title' => $theme->title,
            'population' => $theme->population,
            'status' => $theme->status,
            // Première manche, programmée à la création (encore « pending »).
            'current_round' => $theme->relationLoaded('latestRound') && $theme->latestRound !== null
                ? (new RoundResource($theme->latestRound))->toArray($request)
                : null,
        ];
    }
}
