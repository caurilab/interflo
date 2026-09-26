<?php

declare(strict_types=1);

namespace App\Http\Resources\Pilot;

use App\Models\GameRound;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Manche d'un thème, côté pilotage animateur.
 *
 * ⚠️ La console animateur ne reçoit PAS correct_index non plus : la bonne
 * réponse n'apparaît que dans le back-office Filament (banque de questions).
 *
 * @mixin GameRound
 */
class RoundResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var GameRound $round */
        $round = $this->resource;

        return [
            'id' => $round->id,
            'theme_id' => $round->theme_id,
            'round_number' => $round->round_number,
            'question_id' => $round->question_id,
            'status' => $round->status,
            'window_opened_at' => $round->window_opened_at?->toISOString(),
            'window_closed_at' => $round->window_closed_at?->toISOString(),
        ];
    }
}
