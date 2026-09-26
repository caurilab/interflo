<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\QuestionProposalService;
use Illuminate\Console\Command;

/**
 * Outil de démonstration (I-33) : propose des questions à partir d'un extrait
 * de transcription. Utile pour vérifier le provider DeepSeek une fois la clé
 * DEEPSEEK_API_KEY renseignée — `php artisan ai:propose-questions "..."`.
 */
class ProposeQuestions extends Command
{
    protected $signature = 'ai:propose-questions {transcript : Extrait de transcription horodatée à analyser}';

    protected $description = 'Propose des questions à partir d\'une transcription (I-33).';

    public function handle(QuestionProposalService $service): int
    {
        $questions = $service->propose((string) $this->argument('transcript'));

        if ($questions === []) {
            $this->warn('Aucune question proposée.');

            return self::SUCCESS;
        }

        foreach ($questions as $index => $question) {
            $this->info(sprintf(
                '[%d] manche %s — %s',
                $index + 1,
                $question['round_number'] ?? '?',
                $question['body'] ?? '?',
            ));

            foreach ($question['propositions'] ?? [] as $i => $proposition) {
                $correct = ($question['correct_index'] ?? null) === $i ? ' ✓' : '';
                $this->line(sprintf('    %d. %s%s', $i + 1, $proposition, $correct));
            }

            if (filled($question['source_timestamp'] ?? null)) {
                $this->line(sprintf('    ⏱ source : %s', $question['source_timestamp']));
            }
        }

        return self::SUCCESS;
    }
}
