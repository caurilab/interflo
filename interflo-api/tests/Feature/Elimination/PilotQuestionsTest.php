<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Question;

/*
|--------------------------------------------------------------------------
| Banque de questions diffusable (console animateur, D-002 §4.2)
|--------------------------------------------------------------------------
|
| GET /api/v1/pilot/questions — liste les questions VALIDÉES (EX-40), en
| banque (theme_id nul) et inutilisées. Jamais correct_index (CA-07).
|
*/

it('liste les questions validées et en banque, sans correct_index (CA-07)', function (): void {
    $session = GameSession::factory()->create();

    $q1 = Question::factory()->validated()->forRound(1)->create(); // en banque (theme_id nul)
    $q2 = Question::factory()->validated()->forRound(2)->create();
    Question::factory()->forRound(1)->create(); // NON validée (EX-40) → exclue

    $response = $this->getJson('/api/v1/pilot/questions', pilotHeaders($session))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($q1->id, $q2->id)
        ->and($ids)->not->toContain($q2->id + 1); // la non validée n'est pas listée

    // CA-07 : correct_index ne fuit jamais.
    assertNoCorrectIndexLeak($response->json());
});

it('filtre les questions par manche visée (round_number)', function (): void {
    $session = GameSession::factory()->create();
    $q1 = Question::factory()->validated()->forRound(1)->create();
    Question::factory()->validated()->forRound(2)->create();

    $this->getJson('/api/v1/pilot/questions?round_number=1', pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $q1->id);
});

it('exclut les questions déjà rattachées à un thème', function (): void {
    $session = GameSession::factory()->create();
    $theme = \App\Models\GameTheme::factory()->for($session)->create();
    // Question rattachée à un thème (hors banque) : exclue.
    Question::factory()->validated()->forRound(1)->create(['theme_id' => $theme->id]);

    $this->getJson('/api/v1/pilot/questions?round_number=1', pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
