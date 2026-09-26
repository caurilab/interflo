<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\Player;
use App\Models\RoundPlayerState;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| CA-03 — Horloge falsifiée : rejetée, pas classée (I-6 / EX-17 / INV-3)
|--------------------------------------------------------------------------
|
| Le serveur rejette tout horodatage physiquement impossible : futur
| au-delà de clock_tolerance_ms, ou antérieur à served_at. Un horodatage
| client est borné et validé, JAMAIS cru (R-6).
|
| Helpers partagés : pendingRound() / serve() (AnswerWindowTest.php).
|
*/

it('rejette un horodatage dans le futur lointain (CA-03)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    Sanctum::actingAs($player);
    $response = $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        // Futur lointain : horloge falsifiée.
        'client_timestamp' => now()->getTimestampMs() + 60_000,
    ]);

    $response->assertUnprocessable()->assertJsonPath('error', 'IMPOSSIBLE_TIMESTAMP');

    // Rejeté, pas classé : aucune réponse, aucun verdict.
    expect($state->refresh()->answered_at)->toBeNull()
        ->and($state->is_correct)->toBeNull()
        ->and($state->rejected_reason)->toBe(RoundPlayerState::REJECTED_IMPOSSIBLE_TIMESTAMP);

    assertNoCorrectIndexLeak($response->json());
});

it('rejette un horodatage antérieur au service de la question (CA-03)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    $this->travel(2)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        // Antérieur à served_at : physiquement impossible (EX-17).
        'client_timestamp' => $state->served_at->getTimestampMs() - 1000,
    ])->assertUnprocessable()->assertJsonPath('error', 'IMPOSSIBLE_TIMESTAMP');

    expect($state->refresh()->answered_at)->toBeNull()
        ->and($state->is_correct)->toBeNull();
});

it('accepte un horodatage dans la tolérance d’horloge (EX-16)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = Player::factory()->create();
    $state = serve(actingAttachedPlayer($player, $session), $round);

    // Le client calcule son offset via server_time (EX-16) ; une dérive
    // résiduelle dans la tolérance (2000 ms, point de départ non validé)
    // reste acceptée.
    $tolerance = (int) config('interflo.clock_tolerance_ms');
    $this->travel(3)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs() + $tolerance - 1,
    ])->assertOk();
});
