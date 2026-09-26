<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\GameThemeWinner;
use App\Models\Player;
use App\Models\Question;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| CA-01 — Partie d'élimination complète, de bout en bout, mode mesuré
| DÉSACTIVÉ (EX-36 : aucune mesure de décalage, aucun audio)
|--------------------------------------------------------------------------
|
| 4 joueurs, 5 manches (scellé I-27), éliminations progressives, compteur
| de survivants exact après chaque manche (EX-33), fin de partie (I-28).
|
*/

/**
 * Sert la manche courante au joueur via le polling (transport PROVISOIRE)
 * et renvoie la charge utile. Aucune fuite de la bonne réponse (CA-07).
 *
 * @return array<string, mixed>
 */
function pollState(TestCase $test, Player $player): array
{
    Sanctum::actingAs($player);

    $response = $test->getJson('/api/v1/play/state')->assertOk();

    $payload = $response->json('data');
    assertNoCorrectIndexLeak($payload);

    return $payload;
}

/** Répond à la manche 1 seconde après le service (au-dessus du plancher I-30). */
function answerCurrentRound(TestCase $test, Player $player, GameRound $round, int $answerIndex, string $servedAt): void
{
    $test->travel(1)->seconds();

    Sanctum::actingAs($player);
    $test->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => $answerIndex,
        'client_timestamp' => Carbon::parse($servedAt)->getTimestampMs() + 1000,
    ])->assertOk();
}

it('joue une partie d’élimination complète de bout en bout (CA-01)', function (): void {
    $this->freezeTime();

    // Session en direct ; le mode mesuré est DÉSACTIVÉ (EX-36) — défaut.
    $session = GameSession::factory()->create();
    expect($session->emission->tenant->gameConfig?->measured_mode_enabled ?? false)->toBeFalse();

    // 4 joueurs attachés via l'endpoint d'attachement (bout en bout).
    $players = Player::factory()->count(4)->create();
    foreach ($players as $player) {
        Sanctum::actingAs($player);
        $this->postJson('/api/v1/sessions/attach', ['session_id' => $session->id])->assertOk();
    }

    // Banque : 5 questions validées (EX-40), une par manche, réponse juste = 0.
    $questions = [];
    foreach (range(1, 5) as $n) {
        $questions[$n] = Question::factory()->validated()->forRound($n)->create(['correct_index' => 0]);
    }

    // L'animateur crée le thème + sa première manche (pilotage, auth provisoire).
    $themeId = $this->postJson('/api/v1/pilot/themes', [
        'title' => 'Le grand quiz',
        'population' => Player::POPULATION_HOME,
        'first_question_id' => $questions[1]->id,
    ], pilotHeaders($session))->assertCreated()->json('data.id');

    // ... puis programme les manches 2 à 5.
    foreach (range(2, 5) as $n) {
        $this->postJson('/api/v1/pilot/rounds', [
            'theme_id' => $themeId,
            'question_id' => $questions[$n]->id,
        ], pilotHeaders($session))->assertCreated();
    }

    $theme = GameTheme::query()->findOrFail($themeId);

    // Plan d'élimination : P1 faux dès la manche 1, P2 faux à la manche 3,
    // P3 ne répond pas à la manche 4, P0 survit tout.
    [$survivor, $outRound1, $outRound3, $outRound4] = $players;
    $wrongAt = [$outRound1->id => 1, $outRound3->id => 3];
    $silentAt = [$outRound4->id => 4];
    $expectedSurvivors = [1 => 3, 2 => 3, 3 => 2, 4 => 1, 5 => 1];

    foreach (range(1, 5) as $n) {
        $round = $theme->rounds()->where('round_number', $n)->sole();

        // L'animateur ouvre la fenêtre (I-2) — état serveur.
        $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
            ->assertOk()
            ->assertJsonPath('data.status', GameRound::STATUS_OPEN);

        foreach ($players as $player) {
            // Verrouillé (EX-32) = a déjà échoué (erreur ou silence) sur une
            // manche clôturée précédente.
            $locked = ($wrongAt[$player->id] ?? PHP_INT_MAX) < $n
                || ($silentAt[$player->id] ?? PHP_INT_MAX) < $n;

            $state = pollState($this, $player);

            if ($locked) {
                expect($state['state'])->toBe('locked');

                continue;
            }

            // Fenêtre personnelle (EX-20) : question servie à CE joueur.
            expect($state['state'])->toBe('question')
                ->and($state['round']['round_number'])->toBe($n)
                ->and($state['round']['question']['propositions'])->toHaveCount(4) // scellé I-4
                ->and($state['round']['served_at'])->not->toBeNull()
                ->and($state['round']['window_seconds'])->toBeGreaterThan(0)
                ->and($state['server_time'])->not->toBeNull(); // EX-16

            // P3 voit la question de la manche 4 mais ne répond pas (silence).
            if (($silentAt[$player->id] ?? null) === $n) {
                continue;
            }

            $wrong = ($wrongAt[$player->id] ?? null) === $n;
            answerCurrentRound($this, $player, $round, $wrong ? 1 : 0, $state['round']['served_at']);
        }

        // L'animateur ferme la fenêtre (I-2).
        $this->postJson("/api/v1/pilot/rounds/{$round->id}/close", [], pilotHeaders($session))
            ->assertOk()
            ->assertJsonPath('data.status', GameRound::STATUS_CLOSED);

        // EX-33 : compteur de survivants exact après chaque manche.
        $this->getJson("/api/v1/pilot/themes/{$theme->id}/state", pilotHeaders($session))
            ->assertOk()
            ->assertJsonPath('data.survivors_count', $expectedSurvivors[$n]);
    }

    // Fin de partie (I-28) : règle par défaut « tous les survivants ».
    $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.player_id', $survivor->id);

    expect($theme->refresh()->status)->toBe(GameTheme::STATUS_FINISHED)
        ->and(GameThemeWinner::query()->where('theme_id', $theme->id)->sole()->player_id)
        ->toBe($survivor->id);

    // Chaque joueur ne reçoit que SON bit 'winner' — pas la liste.
    expect(pollState($this, $survivor))->toMatchArray(['state' => 'finished', 'winner' => true])
        ->and(pollState($this, $outRound1))->toMatchArray(['state' => 'finished', 'winner' => false]);
});
