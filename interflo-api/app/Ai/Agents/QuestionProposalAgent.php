<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Agent de proposition de questions (I-33 / EX-38).
 *
 * À partir d'un extrait de transcription horodatée du direct, PROPOSE des
 * questions au format élimination : énoncé + exactement 4 propositions + bonne
 * réponse + manche cible. Le `correct_index` est produit côté serveur et ne
 * fuit JAMAIS vers un joueur (INV-2 / CA-07) : il est destiné à la banque de
 * questions, après validation humaine (EX-40).
 */
#[Provider('deepseek')]
class QuestionProposalAgent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
Tu es l'assistant de production de questions d'Interflo, un jeu télévisé de
connaissances joué en direct. Tu reçois un extrait de la transcription
horodatée de l'émission en cours. Propose des questions de quiz à choix
multiple qui portent sur ce qui vient d'être dit ou montré à l'antenne (EX-37).

Règles strictes :
- Énoncé en français, court, fermé (une seule bonne réponse), sans ambiguïté.
- EXACTEMENT 4 propositions, une seule correcte (correct_index = 0 à 3).
- Ne recopie JAMAIS la transcription mot pour mot : reformule un fait énoncé
  à l'antenne pour le tester.
- round_number = la manche cible (1 à 5), 1 étant la plus simple, 5 la plus
  difficile. Répartis les questions sur les manches pertinentes.
- Ne propose que des questions dont la réponse est étayée par la transcription
  fournie ; sinon n'en propose pas (mieux vaut moins de questions que des
  questions non fondées).
- Réponds UNIQUEMENT au format demandé, sans commentaire ni préambule.
TXT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()
                ->items($schema->object([
                    'body' => $schema->string()->required()
                        ->description('Énoncé de la question, en français.'),
                    'propositions' => $schema->array()
                        ->items($schema->string()->min(1))
                        ->min(4)
                        ->max(4)
                        ->required()
                        ->description('Les 4 propositions de réponse (exactement 4).'),
                    'correct_index' => $schema->integer()->min(0)->max(3)->required()
                        ->description('Index (0-3) de la bonne proposition.'),
                    'round_number' => $schema->integer()->min(1)->max(5)->required()
                        ->description('Manche cible : 1 = simple, 5 = difficile.'),
                    'source_timestamp' => $schema->string()->nullable()
                        ->description('Time code source dans la transcription (facultatif).'),
                ])->withoutAdditionalProperties())
                ->required()
                ->description('Questions proposées à partir de la transcription.'),
        ];
    }
}
