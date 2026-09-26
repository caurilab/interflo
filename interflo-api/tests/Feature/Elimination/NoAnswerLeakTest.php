<?php

declare(strict_types=1);

use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| CA-07 — La bonne réponse ne fuit JAMAIS (INV-2 / R-4 / R-5)
|--------------------------------------------------------------------------
|
| Inspection de TOUTES les charges utiles joueur — état, réponse, erreurs —
| à la recherche de correct_index. Le verdict juste/faux est calculé serveur ;
| le client ne reçoit qu'un bit.
|
| Helpers partagés : pendingRound() / serve() (AnswerWindowTest.php).
|
*/

it('ne fait jamais fuiter correct_index dans aucune charge utile joueur (CA-07)', function (): void {
    $this->freezeTime();

    $session = GameSession::factory()->create();
    $round = pendingRound($session);
    $player = actingAttachedPlayer(Player::factory()->create(), $session);

    // 1. État « idle » / « waiting ».
    Sanctum::actingAs($player);
    assertNoCorrectIndexLeak($this->getJson('/api/v1/play/state')->json());

    // 2. État « question » — la question servie ne porte que body + 4
    //    propositions (scellé I-4).
    $round->update(['status' => GameRound::STATUS_OPEN, 'window_opened_at' => now()]);
    $questionPayload = $this->getJson('/api/v1/play/state')->assertOk()->json('data');
    assertNoCorrectIndexLeak($questionPayload);
    expect(array_keys($questionPayload['round']['question']))->toBe(['body', 'propositions']);

    $servedAt = $questionPayload['round']['served_at'];

    // 3. Réponse acceptée : un bit (R-5), server_time (EX-16), rien d'autre.
    $this->travel(1)->seconds();
    $answerPayload = $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 1, // faux — le client n'apprend pas la bonne réponse
        'client_timestamp' => Carbon::parse($servedAt)->getTimestampMs() + 1000,
    ])->assertOk()->json('data');
    assertNoCorrectIndexLeak($answerPayload);
    expect(array_keys($answerPayload))->toBe(['correct', 'server_time'])
        ->and($answerPayload['correct'])->toBeFalse();

    // 4. Erreurs joueur : seconde réponse refusée, sans fuite.
    $errorPayload = $this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => Carbon::parse($servedAt)->getTimestampMs() + 2000,
    ])->assertConflict()->json();
    assertNoCorrectIndexLeak($errorPayload);

    // 5. État « answered » : le joueur revoit son bit, jamais la clé.
    assertNoCorrectIndexLeak($this->getJson('/api/v1/play/state')->assertOk()->json());

    // 6. Fenêtre fermée : refus serveur sans fuite (INV-8).
    $round->update(['status' => GameRound::STATUS_CLOSED, 'window_closed_at' => now()]);
    assertNoCorrectIndexLeak($this->postJson("/api/v1/rounds/{$round->id}/answer", [
        'answer_index' => 0,
        'client_timestamp' => Carbon::parse($servedAt)->getTimestampMs() + 3000,
    ])->assertForbidden()->json());
});
