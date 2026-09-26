<?php

declare(strict_types=1);

use App\Ai\Agents\QuestionProposalAgent;
use App\Services\QuestionProposalService;
use Laravel\Ai\Ai;
use Laravel\Ai\Attributes\Provider;

/*
|--------------------------------------------------------------------------
| Proposition de questions assistée (I-33 / EX-38) — agent DeepSeek
|--------------------------------------------------------------------------
|
| Le modèle PROPOSE, un humain VALIDE (EX-40). Aucune question n'est diffusable
| sans validation. Ces tests sont fakes : aucun appel réseau, pas de clé requise.
|
*/

it('câble l\'agent de propositions sur le fournisseur DeepSeek', function (): void {
    $providers = (new ReflectionClass(QuestionProposalAgent::class))->getAttributes(Provider::class);

    expect($providers)->toHaveCount(1)
        ->and($providers[0]->newInstance()->value)->toBe('deepseek');
});

it('propose des questions structurées à partir d\'une transcription (fake, sans réseau)', function (): void {
    Ai::fakeAgent(QuestionProposalAgent::class, [
        ['questions' => [
            [
                'body' => 'Quel invité a évoqué le football ivoirien ?',
                'propositions' => ['Didier', 'Serge', 'Aïcha', 'Moussa'],
                'correct_index' => 0,
                'round_number' => 2,
                'source_timestamp' => '00:14:22',
            ],
        ]],
    ]);

    $questions = app(QuestionProposalService::class)->propose(
        "[00:14:20] L'invité a évoqué le football ivoirien…",
    );

    expect($questions)->toBeArray()->toHaveCount(1)
        ->and($questions[0]['body'])->toBe('Quel invité a évoqué le football ivoirien ?')
        ->and($questions[0]['propositions'])->toHaveCount(4)
        ->and($questions[0]['correct_index'])->toBe(0)
        ->and($questions[0]['round_number'])->toBe(2);
});

it('renvoie une liste vide quand le modèle ne propose rien', function (): void {
    Ai::fakeAgent(QuestionProposalAgent::class, [
        ['questions' => []],
    ]);

    expect(app(QuestionProposalService::class)->propose('...'))->toBe([]);
});
