<?php

declare(strict_types=1);

use App\Services\QuestionGenerationService;
use App\Services\QuestionProposalService;
use App\Services\TranscriptionService;

/*
|--------------------------------------------------------------------------
| Chaîne I-33 / EX-38 : audio → transcription → questions proposées
|--------------------------------------------------------------------------
|
| Vérifie l'enchaînement des deux briques IA (Deepgram puis DeepSeek) sans
| appel réseau : les deux dépendances sont mockées.
|
*/

it('enchaîne transcription puis proposition de questions (I-33)', function (): void {
    $transcription = Mockery::mock(TranscriptionService::class);
    $transcription->shouldReceive('transcribe')
        ->once()
        ->with('fake-audio', 'audio/wav')
        ->andReturn([
            'text' => 'Notre invité a parlé du football ivoirien.',
            'words' => [['word' => 'football', 'start' => 0.5, 'end' => 1.0]],
        ]);

    $proposal = Mockery::mock(QuestionProposalService::class);
    $proposal->shouldReceive('propose')
        ->once()
        ->with('Notre invité a parlé du football ivoirien.')
        ->andReturn([
            [
                'body' => 'Quel sport a évoqué l\'invité ?',
                'propositions' => ['Football', 'Tennis', 'Basket', 'Rugby'],
                'correct_index' => 0,
                'round_number' => 1,
                'source_timestamp' => '00:00:05',
            ],
        ]);

    $service = new QuestionGenerationService($transcription, $proposal);

    $result = $service->fromAudio('fake-audio');

    expect($result['transcript'])->toBe('Notre invité a parlé du football ivoirien.')
        ->and($result['words'])->toHaveCount(1)
        ->and($result['questions'])->toHaveCount(1)
        ->and($result['questions'][0]['body'])->toBe('Quel sport a évoqué l\'invité ?');
});
