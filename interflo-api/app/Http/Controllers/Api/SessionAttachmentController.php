<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sessions\AttachSessionRequest;
use App\Http\Resources\PairingResolutionResource;
use App\Models\GameSession;
use App\Models\Player;
use App\Services\SessionAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Attachement / détachement du joueur à une session de jeu (I-16, I-17).
 */
class SessionAttachmentController extends Controller
{
    public function __construct(
        private readonly SessionAttachmentService $service,
    ) {}

    public function store(AttachSessionRequest $request): PairingResolutionResource
    {
        /** @var Player $player */
        $player = $request->user();
        $session = GameSession::query()->findOrFail($request->validated('session_id'));

        $this->service->attach($player, $session);

        // Le mobile reçoit le même pointeur qu'à la résolution du code.
        return new PairingResolutionResource($session->load('emission.tenant'));
    }

    public function destroy(Request $request): Response
    {
        /** @var Player $player */
        $player = $request->user();

        $this->service->detach($player);

        return response()->noContent();
    }
}
