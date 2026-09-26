<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\GameSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️ AUTHENTIFICATION ANIMATEUR PROVISOIRE — non spécifiée par les documents.
 *
 * Choix assumé : token opaque par session de jeu (game_sessions.pilot_token),
 * porté par l'en-tête X-Pilot-Token. Le middleware résout la session pilotée
 * et la dépose dans les attributs de la requête ('pilot_session') ; les
 * contrôleurs vérifient ensuite que la ressource visée appartient à CETTE
 * session (404 sinon — isolation tenant incluse, R-10).
 *
 * Limites assumées : token en clair en base, aucune rotation, aucune
 * révocation, aucun lien avec l'auth BOS (I-37). À remplacer dès arbitrage —
 * SIGNALÉ AU PO dans le rapport de mission.
 */
class EnsurePilotToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Pilot-Token');

        if (! is_string($token) || $token === '') {
            abort(401, __('interflo.pilot.unauthorized'));
        }

        /** @var GameSession|null $session */
        $session = GameSession::query()->where('pilot_token', $token)->first();

        // Comparaison à temps constant malgré l'index unique (défense en
        // profondeur — ne pas transformer la base en oracle de timing).
        if ($session === null || ! hash_equals($session->pilot_token ?? '', $token)) {
            abort(401, __('interflo.pilot.unauthorized'));
        }

        $request->attributes->set('pilot_session', $session);

        return $next($request);
    }
}
