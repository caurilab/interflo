<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Messages serveur Interflo (français — locale par défaut)
    |--------------------------------------------------------------------------
    |
    | Aucun texte utilisateur en dur dans le code (docs/CONVENTIONS.md §2).
    |
    */

    // Corps du SMS contenant le code OTP. La locale du joueur n'est pas
    // connue à ce stade : le SMS part dans la locale par défaut (fr).
    'otp_sms' => 'Interflo : votre code de vérification est :code. Il expire dans :minutes minutes.',

    // Réponse générique de vérification : identique quelle que soit la cause
    // (code faux, expiré, tentatives dépassées, numéro inconnu) — pas
    // d'énumération ni d'oracle.
    'phone_code_invalid' => 'Code invalide ou expiré. Veuillez demander un nouveau code.',

    // Résolution d'appairage : 404 générique (code inconnu ou expiré — EX-04).
    'pairing_code_not_found' => 'Code inconnu ou expiré.',

    // Attachement refusé : la session est terminée, l'accès est mort (I-16).
    'session_ended' => 'Cette session de jeu est terminée.',

    // Le numéro de téléphone doit être vérifié avant de jouer (I-8).
    'phone_not_verified' => 'Votre numéro de téléphone doit être vérifié pour continuer.',

    /*
    |----------------------------------------------------------------------
    | Format élimination — rejets serveur d'une réponse (mission §3)
    |----------------------------------------------------------------------
    |
    | Chaque rejet porte un code stable ('error' en JSON) consommé par
    | mobile et web, et server_time pour la synchro d'horloge (EX-16).
    |
    */

    'answer' => [
        // Hors fenêtre, le serveur refuse (I-2 / EX-11 / INV-8 / CA-02).
        'window_closed' => 'La fenêtre de réponse n\'est pas ouverte.',
        // Émission terminée, accès mort (I-16).
        'session_ended' => 'Cette session de jeu est terminée.',
        // Le joueur n'est pas attaché à la session du thème.
        'not_attached' => 'Vous n\'êtes pas attaché à cette session de jeu.',
        // Les deux populations ne concourent jamais ensemble (I-1 / EX-23).
        'population_mismatch' => 'Cette partie ne concerne pas votre population.',
        // Verrouillé jusqu'à la fin du thème (EX-32).
        'player_locked' => 'Vous êtes éliminé de cette partie.',
        // La question n'a pas été servie à ce joueur (EX-20).
        'not_served' => 'Aucune question ne vous a été servie sur cette manche.',
        // Une seule réponse par joueur et par manche.
        'already_answered' => 'Vous avez déjà répondu à cette manche.',
        // Horodatage physiquement impossible (I-6 / EX-17 / CA-03).
        'impossible_timestamp' => 'Horodatage de la réponse impossible.',
        // Plancher anti-automatisation (I-30 / EX-18).
        'too_fast' => 'Réponse trop rapide pour être humaine.',
        // Fenêtre personnelle dépassée (EX-20).
        'window_exceeded' => 'Votre fenêtre de réponse est dépassée.',
    ],

    /*
    |----------------------------------------------------------------------
    | Pilotage animateur (⚠️ auth PROVISOIRE — voir EnsurePilotToken)
    |----------------------------------------------------------------------
    */

    'pilot' => [
        'unauthorized' => 'Token de pilotage inconnu.',
        'theme_finished' => 'Cette partie est terminée.',
        'previous_round_not_closed' => 'La manche précédente n\'est pas clôturée.',
        'round_currently_open' => 'Une fenêtre est ouverte — attendez sa clôture pour programmer la manche suivante.',
        'rounds_exhausted' => 'Les 5 manches de cette partie sont déjà programmées.',
        'round_not_pending' => 'Cette manche n\'est pas en attente d\'ouverture.',
        'round_not_open' => 'Cette manche n\'est pas ouverte.',
        'rounds_not_all_closed' => 'Toutes les manches doivent être clôturées avant la fin de partie.',
        'winners_count_invalid' => 'Le nombre de gagnants doit être 1, 3 ou 5 (I-28).',
        // EX-40 : pas de validation humaine, pas de diffusion.
        'question_not_validated' => 'Cette question n\'a pas été validée par un humain.',
        'question_already_used' => 'Cette question est déjà programmée sur une manche.',
        'question_round_mismatch' => 'Cette question ne vise pas cette manche.',
    ],

];
