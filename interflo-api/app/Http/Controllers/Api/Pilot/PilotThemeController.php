<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pilot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pilot\CreatePilotThemeRequest;
use App\Http\Resources\Pilot\ThemeResource;
use App\Http\Resources\Pilot\ThemeStateResource;
use App\Http\Resources\Pilot\WinnerResource;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\Question;
use App\Services\EliminationThemeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Pilotage animateur des thèmes d'élimination (console tablette).
 *
 * ⚠️ Auth PROVISOIRE par token de session (middleware pilot.token) — voir
 * EnsurePilotToken. Chaque route vérifie que la ressource appartient à la
 * session pilotée (404 sinon — isolation tenant, R-10).
 */
class PilotThemeController extends Controller
{
    public function __construct(
        private readonly EliminationThemeService $service,
    ) {}

    /** Crée un thème + sa première manche depuis une question validée (EX-40). */
    public function store(CreatePilotThemeRequest $request): ThemeResource
    {
        /** @var GameSession $session */
        $session = $request->attributes->get('pilot_session');

        $theme = $this->service->createTheme(
            $session,
            $request->validated('title'),
            $request->validated('population'),
            Question::query()->findOrFail($request->validated('first_question_id')),
        );

        return new ThemeResource($theme->load('latestRound'));
    }

    /** État du thème : manche courante, fenêtre, compteur survivants (EX-33). */
    public function state(Request $request, GameTheme $theme): ThemeStateResource
    {
        $this->abortUnlessOwned($request, $theme);

        return new ThemeStateResource($this->service->pilotState($theme));
    }

    /** Déclenche la fin de partie (I-28 / EX-35) — idempotent. */
    public function finish(Request $request, GameTheme $theme): AnonymousResourceCollection
    {
        $this->abortUnlessOwned($request, $theme);

        $winners = $this->service->finishTheme($theme);

        return WinnerResource::collection($winners);
    }

    /** La ressource doit appartenir à la session pilotée — 404 sinon (R-10). */
    private function abortUnlessOwned(Request $request, GameTheme $theme): void
    {
        /** @var GameSession $session */
        $session = $request->attributes->get('pilot_session');

        abort_unless($theme->game_session_id === $session->id, 404);
    }
}
