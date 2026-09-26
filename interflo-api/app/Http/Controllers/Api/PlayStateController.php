<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayStateResource;
use App\Models\Player;
use App\Services\PlayerGameStateService;
use Illuminate\Http\Request;

/**
 * État de jeu courant pour le joueur (format élimination).
 *
 * Transport PROVISOIRE : polling HTTP sobre — en attente de l'ADR temps
 * réel (04-architecture §5). La mécanique est définitive.
 */
class PlayStateController extends Controller
{
    public function __construct(
        private readonly PlayerGameStateService $service,
    ) {}

    public function show(Request $request): PlayStateResource
    {
        /** @var Player $player */
        $player = $request->user();

        return new PlayStateResource($this->service->stateFor($player));
    }
}
