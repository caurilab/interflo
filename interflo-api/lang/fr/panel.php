<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Panel d'administration Filament (français — locale par défaut)
    |--------------------------------------------------------------------------
    |
    | Aucun texte utilisateur en dur dans le code (docs/CONVENTIONS.md §2).
    |
    */

    // Groupes de navigation.
    'nav_group_game' => 'Jeu',
    'nav_group_settings' => 'Réglages',

    // Ressource Tenant.
    'tenants' => [
        'label' => 'Chaîne',
        'plural_label' => 'Chaînes',
        'fields' => [
            'name' => 'Nom',
            'slug' => 'Slug',
            'external_reference' => 'Référence externe (BOS)',
        ],
        'sections' => [
            'identity' => 'Identité',
            'game_config' => 'Configuration de jeu',
        ],
    ],

    // Configuration de jeu par tenant (docs/07 §2.1).
    'game_config' => [
        'answer_window_seconds' => 'Durée de la fenêtre de réponse (secondes)',
        'answer_window_seconds_helper' => 'Vide = valeur par défaut paramétrable (I-31). Point de départ non validé.',
        'measured_mode_enabled' => 'Mode mesuré activé',
        'measured_mode_helper' => 'Activable par chaîne pour la première saison (I-25). ⚠️ Aucune valeur par défaut n\'est tranchée (EX-30) : désactivé ici, à arbitrer.',
        'endgame_rule' => 'Règle de fin de partie (I-28)',
        'endgame_rule_all_survivors' => 'Tous les survivants gagnent',
        'endgame_rule_draw' => 'Tirage au sort parmi les survivants',
        'endgame_rule_draw_warning' => '⚠️ Le tirage au sort bascule le jeu de l\'adresse vers le hasard — cadre légal à instruire (§10.4 du cadrage).',
        'winners_count' => 'Nombre de gagnants retenus (I-28)',
        'winners_count_helper' => 'Options scellées par décision PO : 1, 3 ou 5.',
        'persistent_ranking_enabled' => 'Classement persistant entre émissions',
        'persistent_ranking_helper' => 'Configurable par tenant (I-40). ⚠️ Mécanique non spécifiée (EX-46) : interrupteur seulement.',
    ],

    // Ressource Emission.
    'emissions' => [
        'label' => 'Émission',
        'plural_label' => 'Émissions',
        'fields' => [
            'tenant' => 'Chaîne',
            'title' => 'Titre',
            'external_reference' => 'Référence externe (BOS)',
        ],
    ],

    // Ressource GameSession.
    'game_sessions' => [
        'label' => 'Session de jeu',
        'plural_label' => 'Sessions de jeu',
        'fields' => [
            'emission' => 'Émission',
            'status' => 'Statut',
            'started_at' => 'Démarrée le',
            'ended_at' => 'Clôturée le',
            'pairing_code' => 'Code d\'appairage courant',
            'no_pairing_code' => 'Aucun code actif',
            'pilot_token' => 'Token de pilotage animateur (PROVISOIRE)',
        ],
        'status' => [
            'scheduled' => 'Programmée',
            'live' => 'En direct',
            'ended' => 'Terminée',
        ],
        'actions' => [
            'start' => 'Démarrer',
            'end' => 'Clôturer',
            'rotate_code' => 'Régénérer le code d\'appairage',
            'started' => 'Session démarrée.',
            'ended' => 'Session clôturée.',
            'code_rotated' => 'Nouveau code d\'appairage généré.',
        ],
    ],

    // Ressource Question — banque de questions (EX-40 : validation humaine).
    'questions' => [
        'label' => 'Question',
        'plural_label' => 'Questions',
        'in_bank' => 'En banque',
        'validated' => 'Validée',
        'not_validated' => 'NON VALIDÉE',
        'validated_by_at' => 'Validée par :name le :at',
        'fields' => [
            'theme' => 'Thème',
            'round_number' => 'Manche',
            'body' => 'Énoncé',
            'propositions' => 'Propositions (exactement 4 — I-4)',
            'proposition' => 'Proposition',
            'proposition_n' => 'Proposition :n',
            // ⚠️ INV-2 : la bonne réponse n\'est visible qu\'au back-office.
            'correct_index' => 'Bonne réponse (back-office uniquement)',
            'source' => 'Provenance',
            'validation' => 'Validation humaine (EX-40)',
        ],
        'sources' => [
            'plateau' => 'Plateau',
            'general_culture' => 'Culture générale',
        ],
        'actions' => [
            'validate' => 'Valider',
            'validated' => 'Question validée — diffusable.',
        ],
    ],

    // Page « Seuils et réglages » (lecture seule).
    'thresholds' => [
        'title' => 'Seuils et réglages',
        'navigation_label' => 'Seuils et réglages',
        'intro' => 'Valeurs actives de config(\'interflo\'). Lecture seule : l\'environnement pilote (variables INTERFLO_*).',
        'unvalidated_notice' => '⚠️ Toute valeur paramétrable est un point de départ non validé, à corriger au premier tournage.',
        'columns' => [
            'setting' => 'Réglage',
            'value' => 'Valeur active',
            'nature' => 'Nature',
            'reference' => 'Référence',
        ],
        'nature_sealed' => 'Scellée par décision PO',
        'nature_configurable' => 'Paramétrable (point de départ non validé)',
        'nature_unset' => 'Non arrêtée par le PO',
        'settings' => [
            'propositions_per_question' => 'Propositions par question',
            'elimination_rounds' => 'Manches du format élimination',
            'winner_count_options' => 'Options du nombre de gagnants',
            'anti_automation_floor_ms' => 'Plancher anti-automatisation (ms)',
            'answer_window_seconds' => 'Durée de fenêtre par défaut (s)',
            'server_envelope_seconds' => 'Enveloppe serveur (s)',
            'pairing_code_rotation_seconds' => 'Rotation du code d\'appairage (s)',
            'short_code_length' => 'Longueur du code court',
            'short_code_alphabet' => 'Alphabet du code court',
            'buzzer_max_relaunches' => 'Relances max d\'un tour (buzzer)',
            'general_culture_percent' => 'Part de culture générale (%)',
            'otp_length' => 'Longueur du code OTP',
            'otp_ttl_seconds' => 'Validité du code OTP (s)',
            'otp_max_attempts' => 'Tentatives max par challenge OTP',
            'otp_request_throttle_per_hour' => 'Demandes OTP max / numéro / heure',
            'otp_verify_throttle_per_minute' => 'Vérifications OTP max / numéro / minute',
        ],
    ],

];
