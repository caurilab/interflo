<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canaux de diffusion Interflo (D-002 §4)
|--------------------------------------------------------------------------
|
| - Joueur (public, sans auth) : `session.{sessionId}.{population}`
|   Charge utile identique pour tous, jamais de correct_index (CA-07).
|   Ce canal n'est PAS déclaré ici : un canal public n'exige aucune
|   autorisation. Voir App\Events\RoundOpened.
|
| - Pilotage (privé, auth par jeton pilote) : `pilot.{sessionId}`
|   À ajouter quand la console animateur consommera le push (D-002 §4.2).
|   Le polling actuel de la console reste le transport en vigueur.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
