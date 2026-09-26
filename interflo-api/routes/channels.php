<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canaux de diffusion Interflo (D-002 §4)
|--------------------------------------------------------------------------
|
| - Joueur (public, sans auth) : `session.{sessionId}.{population}`
|   Charge utile identique pour tous, jamais de correct_index (CA-07).
|   Voir App\Events\RoundOpened.
|
| - Pilotage (public, ⚠️ écart D-002 §4.2) : `pilot.{sessionId}`
|   L'état pilote n'est pas sensible (jamais correct_index, pas de contrôle
|   par WebSocket). Le canal PRIVÉ viendra avec l'arbitrage de l'auth
|   animateur (X-Pilot-Token provisoire). Voir App\Events\PilotStateChanged.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
