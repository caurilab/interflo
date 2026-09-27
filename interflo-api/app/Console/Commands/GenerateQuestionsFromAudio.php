<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\QuestionGenerationService;
use Illuminate\Console\Command;

/**
 * Outil de démonstration (I-33) : transcrit un fichier audio (Deepgram) puis
 * propose des questions (DeepSeek). `php artisan ai:generate-questions storage/test-audio/interflo-test-16k.wav`.
 */
class GenerateQuestionsFromAudio extends Command
{
    protected $signature = 'ai:generate-questions {file : Chemin du fichier audio à analyser}';

    protected $description = 'Transcrit un audio puis propose des questions (I-33).';

    public function handle(QuestionGenerationService $service): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        $result = $service->fromAudio(file_get_contents($file));

        $this->info('Transcription : '.$result['transcript']);
        $this->info('Questions proposées : '.count($result['questions']));

        foreach ($result['questions'] as $index => $question) {
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
        }

        return self::SUCCESS;
    }
}
