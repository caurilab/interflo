<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\GameThemeWinner;
use App\Models\Player;
use App\Models\RoundPlayerState;
use App\Models\TenantGameConfig;
use Illuminate\Database\QueryException;

/*
|--------------------------------------------------------------------------
| Fin de partie (I-28 / EX-35) : tous les survivants, ou tirage au sort
| serveur de 1, 3 ou 5 gagnants (options scellées) — une seule fois
|--------------------------------------------------------------------------
|
| ⚠️ Le tirage au sort fait basculer le jeu de l'adresse vers le hasard —
| conséquence réglementaire (cadrage §10.4).
|
| Helper partagé : playableTheme() (Pest.php).
|
*/

/**
 * Joue les 5 manches (clôturées) avec $survivorCount survivants et
 * $loserCount éliminés à la manche 1.
 *
 * @return array{0: GameTheme, 1: array<int, Player>}
 */
function finishedThemeWithSurvivors(GameSession $session, int $survivorCount, int $loserCount = 0): array
{
    [$theme] = playableTheme($session);

    $rounds = [];
    foreach (range(1, 5) as $n) {
        $rounds[$n] = GameRound::factory()->for($theme, 'theme')->create([
            'round_number' => $n,
            'question_id' => $theme->questions()->where('round_number', $n)->sole()->id,
        ]);
    }

    $survivors = [];
    foreach (range(1, $survivorCount) as $i) {
        $survivors[] = Player::factory()->create();
    }
    $losers = [];
    foreach (range(1, $loserCount) as $i) {
        $losers[] = Player::factory()->create();
    }

    foreach ($rounds as $n => $round) {
        // Survivants : verdict correct sur chaque manche.
        foreach ($survivors as $player) {
            RoundPlayerState::factory()->for($round, 'round')->for($player)->answered(true)->create();
        }
        // Éliminés : erreur à la manche 1, puis plus rien (verrouillés, EX-32).
        if ($n === 1) {
            foreach ($losers as $player) {
                RoundPlayerState::factory()->for($round, 'round')->for($player)->answered(false, 1)->create();
            }
        }
        $round->update([
            'status' => GameRound::STATUS_CLOSED,
            'window_opened_at' => now()->subMinutes(2),
            'window_closed_at' => now()->subMinute(),
        ]);
    }

    return [$theme, $survivors];
}

it('fait gagner tous les survivants en règle all_survivors (I-28)', function (): void {
    $session = GameSession::factory()->create();
    [$theme, $survivors] = finishedThemeWithSurvivors($session, 4, loserCount: 3);

    $winners = $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount(4, 'data');

    expect(GameThemeWinner::query()->where('theme_id', $theme->id)->pluck('player_id')->sort()->values()->all())
        ->toBe(collect($survivors)->pluck('id')->sort()->values()->all())
        ->and($theme->refresh()->status)->toBe(GameTheme::STATUS_FINISHED);
});

it('tire au sort 1, 3 ou 5 gagnants parmi les survivants (I-28, options scellées)', function (int $winnersCount): void {
    $session = GameSession::factory()->create();
    TenantGameConfig::factory()->for($session->emission->tenant)->drawEndgame($winnersCount)->create();
    [$theme, $survivors] = finishedThemeWithSurvivors($session, 5);

    $response = $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount($winnersCount, 'data');

    $drawn = collect($response->json('data'));

    // Tous tirés parmi les survivants, rangs 1..n distincts, un seul rang par joueur.
    $survivorIds = collect($survivors)->pluck('id');
    expect($drawn->pluck('player_id')->every(fn ($id) => $survivorIds->contains($id)))->toBeTrue()
        ->and($drawn->pluck('rank')->sort()->values()->all())->toBe(range(1, $winnersCount))
        ->and($drawn->pluck('player_id')->unique())->toHaveCount($winnersCount);
})->with([1, 3, 5]);

it('est idempotent : le tirage au sort ne se rejoue jamais (I-28)', function (): void {
    $session = GameSession::factory()->create();
    TenantGameConfig::factory()->for($session->emission->tenant)->drawEndgame(3)->create();
    [$theme] = finishedThemeWithSurvivors($session, 5);

    $first = $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()->json('data');
    $second = $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()->json('data');

    expect(collect($second)->pluck('player_id')->sort()->values()->all())
        ->toBe(collect($first)->pluck('player_id')->sort()->values()->all())
        ->and(GameThemeWinner::query()->where('theme_id', $theme->id)->count())->toBe(3);
});

it('retient tous les survivants s’ils sont moins nombreux que winners_count (I-28)', function (): void {
    $session = GameSession::factory()->create();
    TenantGameConfig::factory()->for($session->emission->tenant)->drawEndgame(5)->create();
    [$theme] = finishedThemeWithSurvivors($session, 2);

    $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rend impossible un winners_count hors {1,3,5} en base (I-28, options scellées)', function (): void {
    TenantGameConfig::factory()->drawEndgame(4)->create();
})->throws(QueryException::class);

it('refuse la fin de partie tant que les 5 manches ne sont pas clôturées (I-28)', function (): void {
    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    GameRound::factory()->for($theme, 'theme')->closed()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    $this->postJson("/api/v1/pilot/themes/{$theme->id}/finish", [], pilotHeaders($session))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('theme');

    expect(GameThemeWinner::query()->count())->toBe(0);
});
