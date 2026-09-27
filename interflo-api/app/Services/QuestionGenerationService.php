<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Chaîne I-33 / EX-38 : audio → transcription (Deepgram) → questions proposées
 * (DeepSeek). Le modèle PROPOSE, un humain VALIDE (EX-40) — aucune question ne
 * part à l'antenne sans validation.
 */
class QuestionGenerationService
{
    public function __construct(
        private readonly TranscriptionService $transcription,
        private readonly QuestionProposalService $proposal,
    ) {}

    /**
     * Transcrit un segment audio puis propose des questions à partir du texte.
     *
     * @return array{
     *   transcript: string,
     *   words: list<array{word: string, start: float, end: float}>,
     *   questions: list<array{body: string, propositions: list<string>, correct_index: int, round_number: int, source_timestamp: string|null}>
     * }
     */
    public function fromAudio(string $audioBytes, string $mimeType = 'audio/wav'): array
    {
        $transcription = $this->transcription->transcribe($audioBytes, $mimeType);

        return [
            'transcript' => $transcription['text'],
            'words' => $transcription['words'],
            'questions' => $this->proposal->propose($transcription['text']),
        ];
    }
}
