<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Deepgram — transcription temps réel du direct (I-33 / EX-38)
    |--------------------------------------------------------------------------
    |
    | Fournisseur STT retenu (session 7) : streaming basse latence, robuste au
    | bruit de plateau, français, horodatage au mot, endpoint EU (GDPR).
    |
    | ⚠️ `api_key` ne doit JAMAIS sortir du serveur (aucun commit).
    |
    */

    'api_key' => env('DEEPGRAM_API_KEY'),
    'base_url' => env('DEEPGRAM_BASE_URL', 'https://api.deepgram.com'),

    // Nova-3 : recommandé pour le bruit de fond / chevauchements / champ lointain.
    'model' => env('DEEPGRAM_MODEL', 'nova-3'),
    'language' => env('DEEPGRAM_LANGUAGE', 'fr'),
    'smart_format' => (bool) env('DEEPGRAM_SMART_FORMAT', true),
    'punctuate' => (bool) env('DEEPGRAM_PUNCTUATE', true),
    // Diarisation (qui parle) : désactivée par défaut — le direct est mono-source.
    'diarize' => (bool) env('DEEPGRAM_DIARIZE', false),
];
