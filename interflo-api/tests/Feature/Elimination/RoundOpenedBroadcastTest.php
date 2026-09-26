<?php

declare(strict_types=1);

use App\Events\RoundOpened;
use App\Models\GameRound;
use App\Models\GameSession;
use Illuminate\Support\Facades\Event;

/*
|--------------------------------------------------------------------------
| Push temps réel à l'ouverture d'une fenêtre (D-002 §4.1)
|--------------------------------------------------------------------------
|
| À l'ouverture d'une manche, RoundOpened est diffusé sur le canal public
| de la population de la session. La charge utile est identique pour tous,
| sans correct_index (CA-07) ni served_at (EX-20 — fenêtre personnelle).
|
*/

it('diffuse RoundOpened sur le canal de la population, sans correct_index ni served_at', function (): void {
    Event::fake([RoundOpened::class]);

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $round = GameRound::factory()->for($theme, 'theme')->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
        ->assertOk();

    Event::assertDispatched(RoundOpened::class, function (RoundOpened $event) use ($session, $theme, $round): bool {
        // Canal public : session.{sessionId}.{population}.
        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1)
            ->and($channels[0]->name)->toBe("session.{$session->id}.{$theme->population}");

        $payload = $event->broadcastWith();

        // CA-07 : correct_index ne fuit jamais, à aucun niveau de la charge utile.
        assertNoCorrectIndexLeak($payload, 'payload');

        // EX-20 : served_at est personnel, il ne voyage pas dans le push partagé.
        expect(array_keys($payload))->not->toContain('served_at');

        expect($payload['theme']['id'])->toBe($theme->id)
            ->and($payload['round']['id'])->toBe($round->id)
            ->and($payload['round']['round_number'])->toBe(1)
            ->and($payload['round']['window_seconds'])->toBeInt()
            ->and($payload['round']['question']['body'])->toBeString()
            ->and($payload['round']['question']['propositions'])->toHaveCount(4);

        return true;
    });
});

it('ne diffuse PAS RoundOpened quand la fenêtre ne peut pas être ouverte (I-2)', function (): void {
    Event::fake([RoundOpened::class]);

    $session = GameSession::factory()->create();
    [$theme] = playableTheme($session);
    $round = GameRound::factory()->for($theme, 'theme')->open()->create([
        'round_number' => 1,
        'question_id' => $theme->questions()->where('round_number', 1)->sole()->id,
    ]);

    // Une manche déjà ouverte ne se rouvre pas → pas de diffusion.
    $this->postJson("/api/v1/pilot/rounds/{$round->id}/open", [], pilotHeaders($session))
        ->assertUnprocessable();

    Event::assertNotDispatched(RoundOpened::class);
});
