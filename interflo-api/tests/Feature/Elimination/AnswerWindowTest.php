<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Player;
use App\Models\Question;
use App\Models\RoundPlayerState;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Fenêtre de réponse — refus serveur (CA-02), plancher anti-automatisation
| (I-30 / EX-18), fenêtre personnelle (EX-20), gardes d'accès
|--------------------------------------------------------------------------
|
| Hors fenêtre, LE SERVEUR REFUSE (I-2 / EX-11 / INV-8) — jamais un bouton
| grisé côté client. Chaque rejet porte un code 'error' stable + server_time
| (EX-16).
|
*/

/** Crée une manche prête : thème + question validée + manche (pending). */
function pendingRound(GameSession $session): GameRound
{
    [$theme] = playableTheme($session);

    return GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);
}

/** Ouvre la manche et sert la question au joueur (fenêtre personnelle, EX-20). */
function serve(Player $player, GameRound $round): RoundPlayerState
{
    $round->update([
        'status' => GameRound::STATUS_OPEN,
        'window_opened_at' => now(),
    ]);

    return RoundPlayerState::factory()->for($round, 'round')->for($player)->create();
}

// --- CA-02 : refus hors fenêtre, y compris client « modifié » ---------------

it('refuse une réponse sur une manche jamais ouverte (CA-02)', function (): void {
    $session = GameSession::factory()->create();
    $round = pendingRound($session); // pending : fenêtre jamais ouverte
    $player = actingAttachedPlayer(Player::factory()->create(), $session);

    $response = $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ]);

    $response->assertForbidden()->assertJsonPath('error', 'WINDOW_CLOSED');

    assertNoCorrectIndexLeak($response->json());
});

it('refuse une réponse après fermeture de la fenêtre, requête brute d’un client modifié (CA-02)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    // L'animateur ferme la fenêtre (I-2).
    $round->update([
        'status' => GameRound::STATUS_CLOSED,
        'window_closed_at' => now(),
    ]);

    // Client « modifié » : requête brute, sans passer par le client officiel —
    // le serveur refuse quand même (INV-8), et rien n'est enregistré.
    Sanctum::actingAs($player);
    $response = $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 1000,
    ]);

    $response->assertForbidden()->assertJsonPath('error', 'WINDOW_CLOSED');

    expect($state->refresh()->answered_at)->toBeNull()
        ->and($state->is_correct)->toBeNull();

    assertNoCorrectIndexLeak($response->json());
});

it('rejette une answer_index hors des 4 propositions (scellé I-4)', function (): void {
    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    actingAttachedPlayer(Player::factory()->create(), $session);

    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 4,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('answer_index');
});

// --- Plancher anti-automatisation (I-30 / EX-18) -----------------------------

it('rejette une réponse sous le plancher anti-automatisation (I-30)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    // Réponse 100 ms après le service : aucun humain ne répond aussi vite.
    $this->travel(100)->milliseconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 100,
    ])->assertUnprocessable()->assertJsonPath('error', 'TOO_FAST');

    // Le rejet ne consomme pas le droit de répondre.
    expect($state->refresh()->answered_at)->toBeNull()
        ->and($state->rejected_reason)->toBe(RoundPlayerState::REJECTED_TOO_FAST);
});

it('accepte une réponse au-dessus du plancher, avec marge (I-30)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    $floor = (int) config('interflo.anti_automation_floor_ms');
    $this->travel($floor + 200)->milliseconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + $floor + 200,
    ])->assertOk()->assertJsonPath('data.correct', true);

    expect($state->refresh()->is_correct)->toBeTrue();
});

// --- Fenêtre personnelle (EX-20) ----------------------------------------------

it('rejette une réponse au-delà de la fenêtre personnelle (EX-20)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    // La fenêtre du joueur court depuis SON served_at : elle est dépassée,
    // même si l'animateur n'a pas encore fermé la manche.
    $windowMs = (int) config('interflo.answer_window_seconds') * 1000;
    $tolerance = (int) config('interflo.clock_tolerance_ms');
    $this->travel(($windowMs + $tolerance + 1000) / 1000)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + $windowMs + $tolerance + 500,
    ])->assertForbidden()->assertJsonPath('error', 'WINDOW_EXCEEDED');

    expect($state->refresh()->answered_at)->toBeNull()
        ->and($state->rejected_reason)->toBe(RoundPlayerState::REJECTED_WINDOW_EXCEEDED);
});

it('rejette une réponse au-delà de l’enveloppe serveur (EX-20)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);

    // Le joueur est servi tard dans l'enveloppe (gros décalage de diffusion).
    $envelope = ((int) config('interflo.answer_window_seconds')
        + (int) config('interflo.server_envelope_seconds')) * 1000;
    $this->travel(($envelope + 500) / 1000)->seconds();

    $round->update([
        'status' => GameRound::STATUS_OPEN,
        'window_opened_at' => now()->subMilliseconds($envelope + 500),
    ]);
    $player = Player::factory()->create();
    $state = RoundPlayerState::factory()->for($round, 'round')->for($player)->create();
    actingAttachedPlayer($player, $session);

    // Horodatage dans sa fenêtre personnelle, mais enveloppe serveur couverte.
    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 1000,
    ])->assertForbidden()->assertJsonPath('error', 'WINDOW_EXCEEDED');
});

it('utilise la durée de fenêtre du tenant quand elle est configurée (I-31)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $session->emission->tenant->gameConfig()->create(['answer_window_seconds' => 3]);
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    // 6 s après le service : dans le défaut (10 s), hors de la fenêtre
    // tenant (3 s + tolérance d'horloge).
    $this->travel(6)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 6000,
    ])->assertForbidden()->assertJsonPath('error', 'WINDOW_EXCEEDED');
});

// --- Gardes d'accès -------------------------------------------------------------

it('refuse un joueur non attaché à la session du thème', function (): void {
    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    // Fenêtre ouverte : on atteint le contrôle d'attachement (ordre documenté).
    $round->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);

    Sanctum::actingAs(Player::factory()->create());

    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'NOT_ATTACHED');
});

it('refuse un joueur d’une autre population (I-1 / EX-23)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session); // thème 'home' par défaut
    $player = Player::factory()->studio()->create(); // joueur studio (I-1)
    serve(actingAttachedPlayer($player, $session), $round);
    $this->travel(1)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'POPULATION_MISMATCH');
});

it('refuse un joueur à qui la question n’a pas été servie (EX-20)', function (): void {
    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $round->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
    actingAttachedPlayer(Player::factory()->create(), $session);

    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'NOT_SERVED');
});

it('refuse une seconde réponse à la même manche', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    Sanctum::actingAs($player);
    $this->travel(1)->seconds();
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 1, // volontairement faux — la règle testée est l'unicité
        'client_timestamp' => $state->served_at->getTimestampMs() + 1000,
    ])->assertOk()->assertJsonPath('data.correct', false);

    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 2000,
    ])->assertConflict()->assertJsonPath('error', 'ALREADY_ANSWERED');

    // Le premier verdict tient : le second essai ne réécrit rien.
    expect($state->refresh()->is_correct)->toBeFalse();
});

it('refuse le jeu sur une session terminée (I-16)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    $session->update(['status' => GameSession::STATUS_ENDED, 'ended_at' => now()]);

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => $state->served_at->getTimestampMs() + 1000,
    ])->assertForbidden()->assertJsonPath('error', 'SESSION_ENDED');
});

it('exige l’authentification et le numéro vérifié sur les routes de jeu (I-8)', function (): void {
    $session = GameSession::factory()->create();
    $round = pendingRound($session);

    $this->getJson('/api/v1/play/state')->assertUnauthorized();
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [])->assertUnauthorized();

    Sanctum::actingAs(Player::factory()->unverified()->create());
    $this->getJson('/api/v1/play/state')->assertForbidden();
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden();
});
