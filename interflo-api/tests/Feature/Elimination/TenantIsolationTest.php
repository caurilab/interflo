<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Player;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| CA-04 — Isolation tenant (INV-1 / R-10 / I-38) sur les nouvelles routes
|--------------------------------------------------------------------------
|
| Deux tenants ne se voient jamais : un joueur d'un autre tenant ne voit
| rien (404/403), et un token de pilotage n'opère que SA session.
|
| Helpers partagés : pendingRound() / serve() (AnswerWindowTest.php).
|
*/

it('un joueur d’un autre tenant ne peut pas répondre aux manches du tenant voisin (CA-04)', function (): void {
    $this->freezeTime();

    // Tenant A : sa session, son thème, sa manche ouverte.
    $sessionA = GameSession::factory()->create();
    $roundA = pendingRound($sessionA);
    $roundA->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);

    // Tenant B : un joueur attaché à SA session, jamais à celle de A.
    $sessionB = GameSession::factory()->create();
    $playerB = actingAttachedPlayer(Player::factory()->create(), $sessionB);

    Sanctum::actingAs($playerB);
    $this->postJson("/api/v1/rounds/{$roundA->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'NOT_ATTACHED');

    // L'état de jeu du joueur B ne révèle rien du tenant A.
    $this->getJson('/api/v1/play/state')->assertOk()->assertJsonPath('data.state', 'idle');
});

it('un token de pilotage n’opère que sa propre session (CA-04 / R-10)', function (): void {
    $sessionA = GameSession::factory()->create();
    $sessionB = GameSession::factory()->create();

    [$themeA] = playableTheme($sessionA);
    [$themeB] = playableTheme($sessionB);

    // Le pilote de B ne voit pas le thème de A : 404, pas 403 (pas d'oracle).
    $this->getJson("/api/v1/pilot/themes/{$themeA->id}/state", pilotHeaders($sessionB))
        ->assertNotFound();
    $this->postJson("/api/v1/pilot/themes/{$themeA->id}/finish", [], pilotHeaders($sessionB))
        ->assertNotFound();
    $this->postJson('/api/v1/pilot/rounds', [
        'theme_id' => $themeA->id,
        'question_id' => $themeB->questions()->where('round_number', 2)->sole()->id,
    ], pilotHeaders($sessionB))->assertNotFound();

    // Le pilote de B opère normalement SON thème.
    $this->getJson("/api/v1/pilot/themes/{$themeB->id}/state", pilotHeaders($sessionB))
        ->assertOk()
        ->assertJsonPath('data.theme.id', $themeB->id);
});

it('un joueur d’une autre population ne reçoit pas le thème de la population voisine (I-1)', function (): void {
    $session = GameSession::factory()->create();
    // Thème pour le public studio uniquement (I-1).
    [$theme] = playableTheme($session);
    $theme->update(['population' => Player::POPULATION_STUDIO]);

    // Un joueur domicile attaché à la même session ne le voit pas.
    $homePlayer = actingAttachedPlayer(Player::factory()->create(), $session);

    Sanctum::actingAs($homePlayer);
    $this->getJson('/api/v1/play/state')->assertOk()->assertJsonPath('data.state', 'idle');
});
