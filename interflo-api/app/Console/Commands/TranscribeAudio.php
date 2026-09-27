<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TranscriptionService;
use Illuminate\Console\Command;

/**
 * Outil de démonstration (I-33) : transcrit un fichier audio via Deepgram.
 * Utile pour vérifier la clé DEEPGRAM_API_KEY — `php artisan ai:transcribe /tmp/interflo-test-16k.wav`.
 */
class TranscribeAudio extends Command
{
    protected $signature = 'ai:transcribe {file : Chemin du fichier audio à transcrire}';

    protected $description = 'Transcrit un fichier audio via Deepgram (I-33).';

    public function handle(TranscriptionService $service): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        $result = $service->transcribe(file_get_contents($file));

        $this->info('Texte : '.$result['text']);
        $this->info('Mots horodatés : '.count($result['words']));

        foreach ($result['words'] as $word) {
            $this->line(sprintf('  [%06.2f-%06.2f] %s', $word['start'], $word['end'], $word['word']));
        }

        return self::SUCCESS;
    }
}
