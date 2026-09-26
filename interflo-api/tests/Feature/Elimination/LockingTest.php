<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameThemeWinner;
use App\Models\Player;
use App\Models\RoundPlayerState;
use App\Services\EliminationThemeService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Verrouillage jusqu'à la fin du thème (EX-32)
|--------------------------------------------------------------------------
|
| Le joueur qui se trompe — ou ne répond pas dans sa fenêtre (EX-20) — est
| verrouillé jusqu'à la FIN DU THÈME. Il ne peut plus répondre aux manches
| suivantes, et rien ne le déverrouille (le verrouillage est DÉDUIT des
| états — voir EliminationSurvivorService).
|
| Helpers partagés : pendingRound() / serve() (AnswerWindowTest.php),
| playableTheme() (Pest.php).
|
*/

/** Répond correctement à la manche ouverte pour ce joueur (au-dessus du plancher). */
function answerCorrectly(TestCase $test, Player $player, GameRound $round): void
{
    serve($player, $round);
    $test->travel(1)->seconds();

    Sanctum::actingAs($player);
    $test->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => $round->question->correct_index,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertOk()->assertJsonPath('data.correct', true);
}

/** Fait survivre le joueur aux manches données, toutes ouvertes puis closes. */
function surviveRounds(TestCase $test, Player $player, iterable $rounds): void
{
    foreach ($rounds as $round) {
        $round->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
        answerCorrectly($test, $player, $round);
        $round->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);
    }
}

it('verrouille le joueur qui se trompe pour toutes les manches suivantes du thème (EX-32)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $player = actingAttachedPlayer(Player::factory()->create(), $session);

    // Manche 1 : le joueur se trompe.
    $round1 = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);
    $round1->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
    $state = serve($player, $round1);
    $this->travel(1)->seconds();

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round1->id}/answer", [
        'answer_index' => ($round1->question->correct_index + 1) % 4, // faux
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertOk()->assertJsonPath('data.correct', false); // retour 1 bit (R-5)

    $round1->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);

    // Manche 2 : le joueur est verrouillé — son poll renvoie 'locked' et sa
    // réponse est refusée.
    $round2 = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 2,
        'question_id' => $theme->questions()->where('round_number', 2)->sole()->id,
    ]);
    $round2->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);

    Sanctum::actingAs($player);
    $this->getJson('/api/v1/play/state')->assertOk()->assertJsonPath('data.state', 'locked');

    $this->postJson("/api/v1/rounds/{$round2->id}/answer", [
        'answer_index' => $round2->question->correct_index,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'PLAYER_LOCKED');

    // La question ne lui a même pas été servie sur la manche 2.
    expect(RoundPlayerState::query()
        ->where('round_id', $round2->id)
        ->where('player_id', $player->id)
        ->exists())->toBeFalse();
});

it('verrouille aussi le joueur qui ne répond pas dans sa fenêtre (EX-32 / EX-20)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $player = actingAttachedPlayer(Player::factory()->create(), $session);

    // Manche 1 : servi, mais jamais répondu, puis clôture.
    $round1 = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);
    $round1->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
    serve($player, $round1);
    $round1->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);

    // Manche 2 : verrouillé.
    $round2 = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 2,
        'question_id' => $theme->questions()->where('round_number', 2)->sole()->id,
    ]);
    $round2->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);

    Sanctum::actingAs($player);
    $this->postJson("/api/v1/rounds/{$round2->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => now()->getTimestampMs(),
    ])->assertForbidden()->assertJsonPath('error', 'PLAYER_LOCKED');
});

it('ne déverrouille JAMAIS en cours de thème, même après une fin de partie gagnée par d’autres (EX-32)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $locked = actingAttachedPlayer(Player::factory()->create(), $session);
    $survivor = actingAttachedPlayer(Player::factory()->create(), $session);

    // Le verrouillé échoue à la manche 1 ; le survivant passe les 5 manches.
    $rounds = [];
    foreach (range(1, 5) as $n) {
        $rounds[$n] = GameRound::factory()->for($theme, 'theme')->create([
            'round_number' => $n,
            'question_id' => $theme->questions()->where('round_number', $n)->sole()->id,
        ]);
    }

    // Manche 1 : erreur du verrouillé, succès du survivant.
    $rounds[1]->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
    $lockedState = serve($locked, $rounds[1]);
    $this->travel(1)->seconds();
    Sanctum::actingAs($locked);
    $this->postJson("/api/v1/rounds/{$rounds[1]->id}/answer", [
        'answer_index' => ($rounds[1]->question->correct_index + 1) % 4,
        'client_timestamp' => $lockedState->served_at->getTimestampMs() + 1000,
    ])->assertOk();
    answerCorrectly($this, $survivor, $rounds[1]);
    $rounds[1]->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);

    // Manches 2 à 5 : le survivant continue ; le verrouillé reste verrouillé,
    // y compris s'il « répond juste » par la force — refusé avant tout verdict.
    foreach (range(2, 5) as $n) {
        $rounds[$n]->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);

        Sanctum::actingAs($locked);
        $this->postJson("/api/v1/rounds/{$rounds[$n]->id}/answer", [
            'answer_index' => $rounds[$n]->question->correct_index, // « juste » — refusé quand même
            'client_timestamp' => now()->getTimestampMs(),
        ])->assertForbidden()->assertJsonPath('error', 'PLAYER_LOCKED');

        answerCorrectly($this, $survivor, $rounds[$n]);
        $rounds[$n]->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);
    }

    // Fin de partie : le verrouillé n'est pas gagnant ; le survivant l'est.
    app(EliminationThemeService::class)->finishTheme($theme);

    Sanctum::actingAs($locked);
    $this->getJson('/api/v1/play/state')
        ->assertOk()
        ->assertJsonPath('data.state', 'finished')
        ->assertJsonPath('data.winner', false);

    expect(GameThemeWinner::query()->where('theme_id', $theme->id)->sole()->player_id)
        ->toBe($survivor->id);
});
