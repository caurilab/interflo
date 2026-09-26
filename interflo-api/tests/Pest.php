<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configuration Pest
|--------------------------------------------------------------------------
|
| Les tests Feature héritent du TestCase Laravel et utilisent
| RefreshDatabase (PostgreSQL sur la base dédiée interflo_test,
| cf. phpunit.xml).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers — format élimination
|--------------------------------------------------------------------------
|
| Fonctions partagées par les tests du moteur élimination.
|
*/

use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\Player;
use App\Models\PlayerSession;
use App\Models\Question;
use Laravel\Sanctum\Sanctum;

/** En-têtes du pilotage animateur (⚠️ auth PROVISOIRE — X-Pilot-Token). */
function pilotHeaders(GameSession $session): array
{
    return ['X-Pilot-Token' => $session->pilot_token];
}

/** Attache le joueur à la session (I-17) sans passer par l'endpoint. */
function attachPlayer(Player $player, GameSession $session): void
{
    PlayerSession::factory()->for($player)->for($session, 'gameSession')->create();
}

/** Authentifie le joueur (Sanctum) et l'attache à la session. */
function actingAttachedPlayer(Player $player, GameSession $session): Player
{
    attachPlayer($player, $session);
    Sanctum::actingAs($player);

    return $player;
}

/**
 * Crée un thème d'élimination prêt à jouer : 5 questions validées (EX-40),
 * une par manche (1..5, scellé I-27), bonne réponse toujours à l'index 0.
 *
 * @return array{0: GameTheme, 1: array<int, Question>}
 */
function playableTheme(GameSession $session): array
{
    $theme = GameTheme::factory()->for($session)->create();
    $questions = [];

    foreach (range(1, (int) config('interflo.sealed.elimination_rounds')) as $roundNumber) {
        $questions[$roundNumber] = Question::factory()->validated()->forRound($roundNumber)->create([
            'theme_id' => $theme->id,
            'correct_index' => 0,
        ]);
    }

    return [$theme, $questions];
}

/**
 * Assertion CA-07 : la bonne réponse ne fuit JAMAIS. Parcourt récursivement
 * la charge utile décodée et échoue si la clé correct_index y apparaît.
 *
 * @param  array<array-key, mixed>  $payload
 */
function assertNoCorrectIndexLeak(array $payload, string $path = 'data'): void
{
    foreach ($payload as $key => $value) {
        expect($key)->not->toBe('correct_index', "Fuite de la bonne réponse à {$path}.{$key} (CA-07)");

        if (is_array($value)) {
            assertNoCorrectIndexLeak($value, "{$path}.{$key}");
        }
    }
}
