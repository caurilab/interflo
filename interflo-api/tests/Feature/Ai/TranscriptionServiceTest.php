<?php

declare(strict_types=1);

use App\Services\TranscriptionService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Transcription temps réel (I-33 / EX-38) — Deepgram
|--------------------------------------------------------------------------
|
| Vérifie le câblage du service Deepgram sans appel réseau : la requête est
| interceptée (Http::fake) et le parsing des mots horodatés est testé.
|
*/

it('transcrit un segment audio en texte + mots horodatés (Deepgram)', function (): void {
    Http::fake([
        'api.deepgram.com/*' => Http::response([
            'results' => [
                'channels' => [
                    [
                        'alternatives' => [
                            [
                                'transcript' => 'Bienvenue dans lémission',
                                'words' => [
                                    ['word' => 'Bienvenue', 'start' => 0.5, 'end' => 1.2],
                                    ['word' => 'dans', 'start' => 1.2, 'end' => 1.5],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(TranscriptionService::class)->transcribe('fake-audio');

    expect($result['text'])->toBe('Bienvenue dans lémission')
        ->and($result['words'])->toBeArray()->toHaveCount(2)
        ->and($result['words'][0])->toMatchArray(['word' => 'Bienvenue', 'start' => 0.5, 'end' => 1.2]);
});

it('lève une exception quand Deepgram refuse la requête', function (): void {
    Http::fake([
        'api.deepgram.com/*' => Http::response(['error' => 'Bad request'], 400),
    ]);

    expect(fn () => app(TranscriptionService::class)->transcribe('fake-audio'))
        ->toThrow(RuntimeException::class);
});
