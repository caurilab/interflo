<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pilot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pilot\CreatePilotRoundRequest;
use App\Http\Resources\Pilot\RoundResource;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\Question;
use App\Services\EliminationThemeService;
use Illuminate\Http\Request;

/**
 * Pilotage animateur des manches (console tablette) : programmation de la
 * manche suivante, ouverture et fermeture des fenêtres (I-2 / EX-10).
 *
 * ⚠️ Auth PROVISOIRE par token de session (middleware pilot.token) — voir
 * EnsurePilotToken. Chaque route vérifie que la ressource appartient à la
 * session pilotée (404 sinon — isolation tenant, R-10).
 */
class PilotRoundController extends Controller
{
    public function __construct(
        private readonly EliminationThemeService $service,
    ) {}

    /** Programme la manche suivante depuis une question validée (EX-40). */
    public function store(CreatePilotRoundRequest $request): RoundResource
    {
        $theme = GameTheme::query()->findOrFail($request->validated('theme_id'));
        $this->abortUnlessOwned($request, $theme);

        $round = $this->service->createNextRound(
            $theme,
            Question::query()->findOrFail($request->validated('question_id')),
        );

        return new RoundResource($round);
    }

    /** Ouvre la fenêtre de la manche (I-2) — autorisation = état serveur. */
    public function open(Request $request, GameRound $round): RoundResource
    {
        $this->abortUnlessOwned($request, $round->theme);

        return new RoundResource($this->service->openRound($round));
    }

    /** Ferme la fenêtre de la manche (I-2). */
    public function close(Request $request, GameRound $round): RoundResource
    {
        $this->abortUnlessOwned($request, $round->theme);

        return new RoundResource($this->service->closeRound($round));
    }

    /** La ressource doit appartenir à la session pilotée — 404 sinon (R-10). */
    private function abortUnlessOwned(Request $request, GameTheme $theme): void
    {
        /** @var GameSession $session */
        $session = $request->attributes->get('pilot_session');

        abort_unless($theme->game_session_id === $session->id, 404);
    }
}
