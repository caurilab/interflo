<?php

declare(strict_types=1);

use App\Services\GameStateRedis;

/*
|--------------------------------------------------------------------------
| État chaud Redis des réponses (D-002 §4.3)
|--------------------------------------------------------------------------
|
| Unicité atomique (HSETNX) + enregistrement en Redis, flush vers PostgreSQL
| par App\Jobs\PersistAnswer. Le hash est purgé après le flush.
|
*/

it('enregistre une réponse une seule fois par joueur et par manche (unicité HSETNX)', function (): void {
    $redis = app(GameStateRedis::class);
    $redis->clear(999_999);

    // Première réponse : posée (HSETNX → 1).
    expect($redis->recordAnswer(999_999, 1, 0, true))->toBeTrue();
    // Seconde réponse du même joueur : refusée (HSETNX → 0).
    expect($redis->recordAnswer(999_999, 1, 1, false))->toBeFalse();

    // Un seul enregistrement pour ce joueur.
    expect($redis->answers(999_999))->toHaveCount(1);

    $redis->clear(999_999);
    expect($redis->answers(999_999))->toBeEmpty();
});

it('isole les réponses par manche', function (): void {
    $redis = app(GameStateRedis::class);
    $redis->clear(999_998);
    $redis->clear(999_997);

    $redis->recordAnswer(999_998, 1, 0, true);
    $redis->recordAnswer(999_997, 1, 1, false);

    expect($redis->answers(999_998))->toHaveCount(1)
        ->and($redis->answers(999_997))->toHaveCount(1);

    $redis->clear(999_998);
    $redis->clear(999_997);
});
