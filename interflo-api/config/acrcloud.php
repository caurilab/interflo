<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ACRCloud — empreinte audio (I-43) + transcription (I-33)
    |--------------------------------------------------------------------------
    |
    | Un seul fournisseur pour l'empreinte (Live Channel Detection) et la
    | transcription temps réel (Speech to Text) du direct.
    |
    | ⚠️ PROVISOIRE : l'accès se fait avec les identifiants du projet ACRCloud.
    | `access_secret` ne doit JAMAIS sortir du serveur (aucun commit).
    |
    */

    'host' => env('ACRCLOUD_HOST'),
    'access_key' => env('ACRCLOUD_ACCESS_KEY'),
    'access_secret' => env('ACRCLOUD_ACCESS_SECRET'),
];
