<?php

declare(strict_types=1);

use App\Events\PilotStateChanged;
use App\Models\GameRound;
use App\Models\GameSession;
use Illuminate\Support\Facades\Event;

/*
|--------------------------------------------------------------------------
| Push temps réel de l'état pilote (D-002 §4.2)
|--------------------------------------------------------------------------
|
| À l'ouverture/fermeture d'une fenêtre et à la fin de partie, l'état pilote
| (compteurs EX-33, manche courante) est diffusé sur le canal `pilot.{sessionId}`.
| ⚠️ Canal PUBLIC (écart assumé) : l'état pilote n'est pas sensible et le
| canal privé attend l'arbitrage de l'auth animateur.
|
*/

it('diffuse PilotStateChanged sur le canal pilote à l\'ouverture de fenêtre', function (): void {
    Event::fake([PilotStateChanged::class]);

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $round = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
        ->assertOk();

    Event::assertDispatched(PilotStateChanged::class, function (PilotStateChanged $event) use ($session, $theme): bool {
        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1)
            ->and($channels[0]->name)->toBe("pilot.{$session->id}");

        $payload = $event->broadcastWith();
        assertNoCorrectIndexLeak($payload, 'payload');

        expect($payload['theme']['id'])->toBe($theme->id)
            ->and($payload['current_round']['status'])->toBe('open')
            ->and($payload['survivors_count'])->toBeInt()
            ->and($payload['participants_count'])->toBeInt()
            ->and($payload['server_time'])->toBeString();

        return true;
    });
});

