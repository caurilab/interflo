<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\PairingCode;
use App\Models\Player;
use App\Services\PairingCodeService;
use App\Services\SessionAttachmentService;

/*
|--------------------------------------------------------------------------
| Appairage : résolution du code et rotation (I-14, I-15, I-18)
|--------------------------------------------------------------------------
|
| Le code est un pointeur public qui DÉSIGNE sans autoriser (R-9 / INV-6).
| Un code expiré ne résout plus (EX-04). ⚠️ EX-05 (proposition non validée) :
| un joueur déjà appairé garde sa session quand le code tourne.
|
*/

it('résout un code valide vers le pointeur public, sans rien de plus', function (): void {
    $session = GameSession::factory()->create();
    $code = PairingCode::factory()->for($session, 'gameSession')->create(['code' => 'ABCDEF']);

    $response = $this->postJson('/api/v1/pairing/resolve', ['code' => 'ABCDEF']);

    // Le pointeur, et SEULEMENT le pointeur (R-9, R-11).
    $response->assertOk()
        ->assertJsonPath('data.tenant.name', $session->emission->tenant->name)
        ->assertJsonPath('data.emission.title', $session->emission->title)
        ->assertJsonPath('data.session.id', $session->id)
        ->assertJsonPath('data.session.status', GameSession::STATUS_LIVE)
        ->assertJsonMissingPath('data.tenant.slug')
        ->assertJsonMissingPath('data.tenant.external_reference')
        ->assertJsonMissingPath('data.emission.external_reference')
        ->assertJsonMissingPath('data.session.emission_id')
        ->assertJsonMissingPath('data.session.started_at');

    expect($response->json('data.tenant'))->toHaveKeys(['name'])
        ->and($response->json('data.emission'))->toHaveKeys(['title'])
        ->and($response->json('data.session'))->toHaveKeys(['id', 'status']);
});

it('est insensible à la casse et aux espaces (saisie manuelle à l’oral)', function (): void {
    PairingCode::factory()->create(['code' => 'ABCDEF']);

    $this->postJson('/api/v1/pairing/resolve', ['code' => ' abcdef '])
        ->assertOk();
});

it('renvoie un 404 générique pour un code inconnu', function (): void {
    $this->postJson('/api/v1/pairing/resolve', ['code' => 'ZZZZZZ'])
        ->assertNotFound()
        ->assertJson(['message' => __('interflo.pairing_code_not_found')]);
});

it('renvoie un 404 générique pour un code expiré (EX-04)', function (): void {
    PairingCode::factory()->expired()->create(['code' => 'ABCDEF']);

    $response = $this->postJson('/api/v1/pairing/resolve', ['code' => 'ABCDEF']);

    // Même 404, même message qu’un code inconnu : pas de distinction.
    $response->assertNotFound()
        ->assertJson(['message' => __('interflo.pairing_code_not_found')]);
});

it('ne résout plus un code dont la fenêtre n’a pas commencé', function (): void {
    PairingCode::factory()->create([
        'code' => 'ABCDEF',
        'valid_from' => now()->addMinute(),
        'valid_until' => now()->addMinutes(2),
    ]);

    $this->postJson('/api/v1/pairing/resolve', ['code' => 'ABCDEF'])->assertNotFound();
});

it('tue l’ancien code à la rotation et garde le joueur déjà appairé (EX-05, non validée)', function (): void {
    $session = GameSession::factory()->create();
    $service = app(PairingCodeService::class);

    $oldCode = $service->createCode($session);

    // Un joueur s'attache AVANT la rotation.
    $player = Player::factory()->create();
    app(SessionAttachmentService::class)->attach($player, $session);

    // Rotation (I-18).
    $newCode = $service->rotate($session);

    // EX-04 : l'ancien code est mort.
    expect($service->resolve($oldCode->code))->toBeNull()
        ->and($oldCode->fresh()->valid_until->isPast())->toBeTrue();

    // Le nouveau code désigne la même session.
    expect($service->resolve($newCode->code)?->id)->toBe($session->id);

    // ⚠️ EX-05 (proposition NON validée) : le joueur déjà appairé garde sa session.
    expect($player->fresh()->activePlayerSession->game_session_id)->toBe($session->id)
        ->and($player->fresh()->activePlayerSession->detached_at)->toBeNull();
});

it('génère des codes dans l’alphabet configuré, sans caractères ambigus (EX-03)', function (): void {
    $service = app(PairingCodeService::class);
    $alphabet = (string) config('interflo.short_code_alphabet');
    $length = (int) config('interflo.short_code_length');

    foreach (range(1, 50) as $ignored) {
        $code = $service->generateCode();
        expect($code)->toHaveLength($length);
        foreach (str_split($code) as $char) {
            expect($alphabet)->toContain($char);
        }
    }
});

it('fait tourner les codes des sessions en direct via la commande planifiée', function (): void {
    $live = GameSession::factory()->create();
    $ended = GameSession::factory()->ended()->create();
    app(PairingCodeService::class)->createCode($live);

    $this->artisan('interflo:rotate-pairing-codes')->assertSuccessful();

    // Un nouveau code valide pour la session live, rien pour la session terminée.
    expect(PairingCode::query()->where('game_session_id', $live->id)->currentlyValid()->count())->toBe(1)
        ->and(PairingCode::query()->where('game_session_id', $ended->id)->count())->toBe(0);
});
