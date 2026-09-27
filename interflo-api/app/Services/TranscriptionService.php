<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Transcription temps réel du direct (I-33 / EX-38) via Deepgram.
 *
 * Envoie un segment audio au modèle Nova-3 (français) et renvoie le texte
 * transcrit + les mots horodatés, nécessaires au clic « time code → retrouver
 * le passage » de la console agent questions (EX-39).
 */
class TranscriptionService
{
    /**
     * Transcrit un segment audio (octets) et renvoie le texte + les mots horodatés.
     *
     * @return array{text: string, words: list<array{word: string, start: float, end: float}>}
     *
     * @throws RuntimeException si Deepgram refuse la requête.
     */
    public function transcribe(string $audioBytes, string $mimeType = 'audio/wav'): array
    {
        $response = Http::withToken((string) config('deepgram.api_key'))
            ->withQueryParameters([
                'model' => config('deepgram.model'),
                'language' => config('deepgram.language'),
                'smart_format' => config('deepgram.smart_format') ? 'true' : 'false',
                'punctuate' => config('deepgram.punctuate') ? 'true' : 'false',
            ])
            ->withHeaders(['Content-Type' => $mimeType])
            ->withBody($audioBytes, $mimeType)
            ->timeout(60)
            ->post((string) config('deepgram.base_url').'/v1/listen');

        if ($response->failed()) {
            throw new RuntimeException('Deepgram a refusé la transcription : '.$response->body());
        }

        return $this->extract($response);
    }

    /**
     * @return array{text: string, words: list<array{word: string, start: float, end: float}>}
     */
    private function extract(Response $response): array
    {
        $text = (string) $response->json('results.channels.0.alternatives.0.transcript', '');

        $words = array_map(
            fn (array $word): array => [
                'word' => (string) $word['word'],
                'start' => (float) $word['start'],
                'end' => (float) $word['end'],
            ],
            $response->json('results.channels.0.alternatives.0.words', []),
        );

        return ['text' => $text, 'words' => $words];
    }
}
