<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\Player;

/*
|--------------------------------------------------------------------------
| Champs population / session_id de `GET /api/v1/play/state` (D-002 §4.1)
|--------------------------------------------------------------------------
|
| Exposés pour que le client construise le nom du canal temps réel
| `session.{sessionId}.{population}` et s'y abonne (Echo/Reverb). Le polling
| reste le transport dégradé (D-1).
|
*/

it('expose population et session_id pour construire le canal temps réel', function (): void {
    $session = GameSession::factory()->create();
    $player = Player::factory()->create(['population' => Player::POPULATION_HOME]);
    actingAttachedPlayer($player, $session);

    $this->getJson('/api/v1/play/state')
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.population', Player::POPULATION_HOME)
        ->assertJsonPath('data.session_id', $session->id);
});

it('expose la population studio pour un joueur du public présent (I-1)', function (): void {
    $session = GameSession::factory()->create();
    $player = Player::factory()->studio()->create();
    actingAttachedPlayer($player, $session);

    $this->getJson('/api/v1/play/state')
        ->assertOk()
        ->assertJsonPath('data.population', Player::POPULATION_STUDIO)
        ->assertJsonPath('data.session_id', $session->id);
});
