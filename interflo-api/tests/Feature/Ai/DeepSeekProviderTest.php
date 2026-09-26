<?php

declare(strict_types=1);

use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Providers\DeepSeekProvider;
use Laravel\Ai\Responses\AgentResponse;

/*
|--------------------------------------------------------------------------
| Intégration Laravel AI SDK (laravel/ai) — fournisseur DeepSeek
|--------------------------------------------------------------------------
|
| Vérifie le câblage du SDK installé : fournisseur de texte par défaut
| DeepSeek, résolution du driver, et génération de texte via un agent fake
| (aucun appel réseau — la clé DEEPSEEK_API_KEY n'est pas requise en test).
|
*/

it('utilise DeepSeek comme fournisseur de texte par défaut', function (): void {
    expect(config('ai.default'))->toBe('deepseek')
        ->and(config('ai.providers.deepseek.driver'))->toBe('deepseek');
});

it('résout le driver DeepSeek sans clé requise à la construction', function (): void {
    expect(Ai::textProvider('deepseek'))->toBeInstanceOf(DeepSeekProvider::class);
});

it('génère du texte via un agent DeepSeek (fake, sans réseau)', function (): void {
    Ai::fakeAgent(AnonymousAgent::class, ['Réponse simulée']);

    $response = (new AnonymousAgent('', [], []))
        ->prompt('Bonjour', [], 'deepseek', 'deepseek-chat');

    expect($response)->toBeInstanceOf(AgentResponse::class)
        ->and($response->text)->toBe('Réponse simulée');
});
