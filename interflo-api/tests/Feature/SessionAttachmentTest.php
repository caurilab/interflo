<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Attachement / détachement à une session de jeu (I-16, I-17)
|--------------------------------------------------------------------------
|
| Une seule session active par joueur (I-17), garantie en base par un index
| unique partiel. Une session terminée refuse tout attachement (I-16).
|
*/

it('refuse l’attachement sans authentification (401)', function (): void {
    $session = GameSession::factory()->create();

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])
        ->assertUnauthorized();
});

// Régression : sans en-tête Accept: application/json, l'API doit répondre
// 401 en JSON — jamais une 500 « Route [login] not defined ».
it('répond 401 en JSON même sans en-tête Accept', function (): void {
    $session = GameSession::factory()->create();

    $response = $this->post('/api/v1/sessions/attach', ['session_id' => $session->id]);

    $response->assertUnauthorized();
    expect($response->headers->get('content-type'))->toContain('application/json');
});

it('refuse l’attachement sans téléphone vérifié (403)', function (): void {
    $player = Player::factory()->unverified()->create();
    $session = GameSession::factory()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])
        ->assertForbidden()
        ->assertJson(['message' => __('interflo.phone_not_verified')]);
});

it('attache le joueur et renvoie le pointeur de la session', function (): void {
    $player = Player::factory()->create();
    $session = GameSession::factory()->create();

    Sanctum::actingAs($player);

    $response = $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id]);

    $response->assertOk()
        ->assertJsonPath('data.session.id', $session->id)
        ->assertJsonPath('data.tenant.name', $session->emission->tenant->name);

    $attachment = PlayerSession::query()->sole();
    expect($attachment->player_id)->toBe($player->id)
        ->and($attachment->game_session_id)->toBe($session->id)
        ->and($attachment->detached_at)->toBeNull();
});

it('détache toute autre session active à l’attachement (I-17)', function (): void {
    $player = Player::factory()->create();
    $first = GameSession::factory()->create();
    $second = GameSession::factory()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $first->id])->assertOk();
    $this->postJson('/api/v1/sessions/attach', ['session_id' => $second->id])->assertOk();

    // Une seule session active : la seconde.
    $active = PlayerSession::query()->where('player_id', $player->id)->active()->sole();
    expect($active->game_session_id)->toBe($second->id);

    // La première est détachée, pas supprimée.
    expect(PlayerSession::query()->where('player_id', $player->id)->count())->toBe(2)
        ->and(
            PlayerSession::query()
                ->where('player_id', $player->id)
                ->where('game_session_id', $first->id)
                ->sole()
                ->detached_at
        )->not->toBeNull();
});

it('garantit l’unicité de la session active en base (I-17)', function (): void {
    $player = Player::factory()->create();
    PlayerSession::factory()->for($player)->create();

    // Contournement du service : l'index unique partiel doit refuser.
    PlayerSession::factory()->for($player)->create();
})->throws(QueryException::class);

it('est idempotent sur un ré-attachement à la même session', function (): void {
    $player = Player::factory()->create();
    $session = GameSession::factory()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])->assertOk();
    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])->assertOk();

    expect(PlayerSession::query()->count())->toBe(1);
});

it('refuse l’attachement à une session terminée (I-16)', function (): void {
    $player = Player::factory()->create();
    $session = GameSession::factory()->ended()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('session_id');

    expect(PlayerSession::query()->count())->toBe(0);
});

it('rejette une session inexistante', function (): void {
    $player = Player::factory()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('session_id');
});

it('détache la session active', function (): void {
    $player = Player::factory()->create();
    $session = GameSession::factory()->create();

    Sanctum::actingAs($player);

    $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])->assertOk();
    $this->deleteJson('/api/v1/sessions/attach')->assertNoContent();

    expect(PlayerSession::query()->active()->count())->toBe(0)
        ->and(PlayerSession::query()->sole()->detached_at)->not->toBeNull();
});

it('détache sans erreur quand aucune session n’est active', function (): void {
    $player = Player::factory()->create();

    Sanctum::actingAs($player);

    $this->deleteJson('/api/v1/sessions/attach')->assertNoContent();
});

it('exige l’authentification pour se détacher (401)', function (): void {
    $this->deleteJson('/api/v1/sessions/attach')->assertUnauthorized();
});
