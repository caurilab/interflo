<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GameTheme;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Changement d'état du thème, poussé à la console animateur (D-002 §4.2).
 *
 * ⚠️ Écart assumé et documenté : D-002 §4.2 prévoit un canal PRIVÉ. Or un
 * canal privé Reverb exige un utilisateur authentifié (session/Sanctum), ce
 * que l'auth animateur PROVISOIRE (`X-Pilot-Token`) ne fournit pas. Tant que
 * l'auth animateur n'est pas tranchée, le canal est PUBLIC — l'état pilote
 * (compteurs EX-33, manche courante, fenêtre) n'est PAS sensible : jamais
 * `correct_index` (INV-2), et le contrôle (ouvrir/fermer) reste en HTTP
 * protégé par X-Pilot-Token. À passer en privé dès arbitrage de l'auth.
 */
class PilotStateChanged implements ShouldBroadcast
{
    public function __construct(
        public readonly GameTheme $theme,
        /** @var array<string, mixed> État pilote, tel que construit par EliminationThemeService::pilotState(). */
        public readonly array $state,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("pilot.{$this->theme->game_session_id}")];
    }

    /** Nom d'événement stable côté client : Echo `.listen('.pilot.state')`. */
    public function broadcastAs(): string
    {
        return 'pilot.state';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->state;
    }
}
