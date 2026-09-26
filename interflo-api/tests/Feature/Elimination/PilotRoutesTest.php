<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\Player;
use App\Models\Question;

/*
|--------------------------------------------------------------------------
| Pilotage animateur (console tablette) — ⚠️ auth PROVISOIRE par token
|--------------------------------------------------------------------------
|
| Ouverture / fermeture des fenêtres (I-2 / EX-10), état du thème avec
| compteur de survivants (EX-33), création de thème et de manche — EX-40 :
| une question NON validée humainement ne peut pas être diffusée (contrôle
| côté service, pas juste UI).
|
*/

it('rejette tout appel de pilotage sans token ou avec un token inconnu', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);

    $this->postJson('/api/v1/pilot/themes', [])->assertUnauthorized();
    $this->getJson("/api/v1/pilot/themes/{$theme->id}/state")->assertUnauthorized();
    $this->getJson("/api/v1/pilot/themes/{$theme->id}/state", ['X-Pilot-Token' => 'faux-token'])
        ->assertUnauthorized();
});

it('crée un thème avec sa première manche depuis une question validée (EX-40)', function (): void {
    $session = GameSession::factory()->create();
    $question = Question::factory()->validated()->forRound(1)->create();

    $response = $this->postJson('/api/v1/pilot/themes', [
        'title' => 'Quiz du soir',
        'population' => Player::POPULATION_STUDIO,
        'first_question_id' => $question->id,
    ], pilotHeaders($session));

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Quiz du soir')
        ->assertJsonPath('data.population', Player::POPULATION_STUDIO)
        ->assertJsonPath('data.current_round.round_number', 1)
        ->assertJsonPath('data.current_round.status', GameRound::STATUS_PENDING);

    $theme = GameTheme::query()->sole();

    // La question quitte la banque : rattachée au thème.
    expect($question->refresh()->theme_id)->toBe($theme->id);
});

it('refuse de créer un thème depuis une question NON validée (EX-40)', function (): void {
    $session = GameSession::factory()->create();
    $question = Question::factory()->forRound(1)->create(); // non validée

    $this->postJson('/api/v1/pilot/themes', [
        'title' => 'Quiz du soir',
        'population' => Player::POPULATION_HOME,
        'first_question_id' => $question->id,
    ], pilotHeaders($session))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('first_question_id');

    expect(GameTheme::query()->count())->toBe(0)
        ->and(GameRound::query()->count())->toBe(0);
});

it('refuse de programmer une manche sur une question NON validée (EX-40)', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    GameRound::factory()->for($theme, 'theme')->closed()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);
    $unvalidated = Question::factory()->forRound(2)->create(); // non validée

    $this->postJson('/api/v1/pilot/rounds', [
        'theme_id' => $theme->id,
        'question_id' => $unvalidated->id,
    ], pilotHeaders($session))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('question_id');
});

it('ouvre et ferme une fenêtre — l’autorisation est un état serveur (I-2 / EX-10)', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $round = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonPath('data.status', GameRound::STATUS_OPEN);

    expect($round->refresh()->window_opened_at)->not->toBeNull();

    // Une manche déjà ouverte ne se rouvre pas.
    $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
        ->assertUnprocessable();

    $this->postJson("/api/v1/pilot/rounds/{$round->id}/close", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonPath('data.status', GameRound::STATUS_CLOSED);

    expect($round->refresh()->window_closed_at)->not->toBeNull();

    // Une manche clôturée ne se referme pas.
    $this->postJson("/api/v1/pilot/rounds/{$round->id}/close", [], pilotHeaders($session))
        ->assertUnprocessable();
});

it('refuse d’ouvrir la manche 2 tant que la manche 1 n’est pas clôturée (I-2)', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    GameRound::factory()->for($theme, 'theme')->open()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);
    $round2 = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 2,
        'question_id' => $theme->questions()->where('round_number', 2)->sole()->id,
    ]);

    $this->postJson("/api/v1/pilot/rounds/{$round2->id}/open", [], pilotHeaders($session))
        ->assertUnprocessable();
});

it('refuse une question visant une autre manche, ou déjà utilisée', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    GameRound::factory()->for($theme, 'theme')->closed()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    // Question visant la manche 3 alors que la suivante est la 2.
    $misnumbered = Question::factory()->validated()->forRound(3)->create();
    $this->postJson('/api/v1/pilot/rounds', [
        'theme_id' => $theme->id,
        'question_id' => $misnumbered->id,
    ], pilotHeaders($session))->assertUnprocessable()->assertJsonValidationErrors('question_id');

    // Question déjà programmée sur la manche 1.
    $used = $theme->questions()->where('round_number', 1)->sole();
    $this->postJson('/api/v1/pilot/rounds', [
        'theme_id' => $theme->id,
        'question_id' => $used->id,
    ], pilotHeaders($session))->assertUnprocessable()->assertJsonValidationErrors('question_id');
});

it('expose l’état du thème au pilote : manche courante, fenêtre, survivants, participation (EX-33)', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $round = GameRound::factory()->for($theme, 'theme')->open()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    $this->getJson("/api/v1/pilot/themes/{$theme->id}/state", pilotHeaders($session))
        ->assertOk()
        ->assertJsonPath('data.theme.id', $theme->id)
        ->assertJsonPath('data.current_round.id', $round->id)
        ->assertJsonPath('data.current_round.status', GameRound::STATUS_OPEN)
        ->assertJsonPath('data.survivors_count', 0)
        ->assertJsonPath('data.participants_count', 0)
        ->assertJsonStructure(['data' => ['server_time']]);
});
