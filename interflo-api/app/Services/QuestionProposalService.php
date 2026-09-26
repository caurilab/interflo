<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\QuestionProposalAgent;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * Propose des questions à partir d'une transcription du direct (I-33 / EX-38).
 *
 * Enrobe l'agent DeepSeek : le modèle PROPOSE, un humain VALIDE (EX-40).
 * La sortie est une liste de candidats — aucune question ne porte de
 * `validated_at` tant qu'un agent humain ne l'a pas validée.
 */
class QuestionProposalService
{
    public function __construct(private readonly QuestionProposalAgent $agent) {}

    /**
     * Propose des questions à partir d'un extrait de transcription.
     *
     * @return list<array{body: string, propositions: list<string>, correct_index: int, round_number: int, source_timestamp: string|null}>
     */
    public function propose(string $transcript): array
    {
        $response = $this->agent->prompt($transcript);

        if (! $response instanceof StructuredAgentResponse) {
            return [];
        }

        return $response->structured['questions'] ?? [];
    }
}
