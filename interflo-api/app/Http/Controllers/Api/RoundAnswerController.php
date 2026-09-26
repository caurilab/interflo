<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\AnswerRequest;
use App\Models\GameRound;
use App\Models\Player;
use App\Services\AnswerService;
use Illuminate\Http\JsonResponse;

/**
 * Réponse d'un joueur à une manche (format élimination).
 *
 * Tous les contrôles sont côté AnswerService, dans l'ordre documenté. Le
 * verdict est calculé serveur (INV-2 / R-4) ; la réponse ne porte qu'UN BIT
 * (R-5) plus server_time pour la synchronisation d'horloge (EX-16).
 */
class RoundAnswerController extends Controller
{
    public function __construct(
        private readonly AnswerService $service,
    ) {}

    public function store(AnswerRequest $request, GameRound $round): JsonResponse
    {
        /** @var Player $player */
        $player = $request->user();

        $verdict = $this->service->submit(
            $player,
            $round,
            (int) $request->validated('answer_index'),
            (int) $request->validated('client_timestamp'),
        );

        // R-5 : un bit, jamais la bonne réponse. EX-16 : server_time.
        return response()->json([
            'data' => [
                'correct' => $verdict['correct'],
                'server_time' => now()->toISOString(),
            ],
        ]);
    }
}
