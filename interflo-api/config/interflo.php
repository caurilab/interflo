<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Réglages du jeu Interflo
|--------------------------------------------------------------------------
|
| Toutes les valeurs de réglage du jeu vivent ici, pilotées par
| l'environnement (préfixe INTERFLO_). Ne jamais écrire ces valeurs
| en dur dans le code.
|
| ⚠️ Aucune valeur numérique n'est validée par le PO : chaque valeur
| paramétrable est un « point de départ non validé », à corriger au
| premier tournage (docs/03-prd.md §10, point 4).
|
| Exception : trois valeurs sont SCELLÉES par décision PO — ce sont des
| règles du jeu, pas des réglages. Elles figurent ici par commodité de
| lecture, marquées « scellée par décision PO », et ne sont pas exposées
| à l'environnement.
|
| Références : docs/INTERFLO_PRODUCT.md §3 (décisions I-xx),
| docs/03-prd.md (exigences EX-xx).
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Valeurs SCELLÉES par décision PO — règles du jeu, pas des réglages
    |----------------------------------------------------------------------
    |
    | Ne pas rendre paramétrables, ne pas modifier sans décision PO.
    |
    */

    'sealed' => [

        // Scellée par décision PO (I-4) : la réponse est un choix multiple
        // à 4 propositions. Le vocal est écarté.
        'propositions_per_question' => 4,

        // Scellée par décision PO (I-27) : le format élimination se joue
        // en 5 manches.
        'elimination_rounds' => 5,

        // Scellée par décision PO (I-28) : en fin de partie élimination,
        // tirage au sort pour retenir 1, 3 ou 5 gagnants (ou tous les
        // survivants, configurable par tenant — mécanique non spécifiée).
        'winner_count_options' => [1, 3, 5],

    ],

    /*
    |----------------------------------------------------------------------
    | Valeurs paramétrables — points de départ NON VALIDÉS
    |----------------------------------------------------------------------
    */

    // Plancher anti-automatisation, en millisecondes (I-30 / EX-18) :
    // seuil de temps de réponse minimum sous lequel le serveur rejette,
    // car aucun humain ne répond aussi vite.
    // ⚠️ Point de départ non validé (800 ms). À ne pas confondre avec la
    // durée de fenêtre (I-31) — ce sont deux choses différentes.
    'anti_automation_floor_ms' => (int) env('INTERFLO_ANTI_AUTOMATION_FLOOR_MS', 800),

    // Durée de la fenêtre de réponse personnelle, en secondes (I-31 / EX-19) :
    // configurable par tenant depuis le tableau de bord. ⚠️ La fenêtre est
    // personnelle, pas absolue (EX-20) : elle court à partir du moment où
    // chaque joueur voit la question.
    // ⚠️ Point de départ non validé (10 s).
    'answer_window_seconds' => (int) env('INTERFLO_ANSWER_WINDOW_SECONDS', 10),

    // Pire décalage de diffusion attendu, en secondes (EX-20) : l'enveloppe
    // serveur doit couvrir la fenêtre personnelle PLUS ce décalage.
    // ⚠️ Point de départ non validé (15 s).
    'server_envelope_seconds' => (int) env('INTERFLO_SERVER_ENVELOPE_SECONDS', 15),

    // Tolérance d'horloge, en millisecondes (EX-16) : borne acceptée sur
    // l'horodatage client — un client_timestamp dans le futur au-delà de
    // cette tolérance, ou antérieur à served_at, est rejeté (EX-17). Le
    // client calcule son offset via le server_time présent dans chaque
    // réponse joueur (PROVISOIRE — en attente de l'ADR temps réel).
    // ⚠️ Point de départ non validé (2000 ms).
    'clock_tolerance_ms' => (int) env('INTERFLO_CLOCK_TOLERANCE_MS', 2000),

    // Longueur du token de pilotage animateur (⚠️ PROVISOIRE : l'auth de la
    // console tablette n'est spécifiée nulle part — token opaque par session,
    // en-tête X-Pilot-Token. À remplacer dès arbitrage).
    // ⚠️ Point de départ non validé (48 caractères).
    'pilot_token_length' => (int) env('INTERFLO_PILOT_TOKEN_LENGTH', 48),

    // Période de rotation du code d'appairage, en secondes (I-18 / EX-04) :
    // le code est renouvelé en cours d'antenne ; un code photographié est
    // déjà mort. Ordre de grandeur évoqué : 15 à 30 s.
    // ⚠️ Point de départ non validé (20 s).
    'pairing_code_rotation_seconds' => (int) env('INTERFLO_PAIRING_CODE_ROTATION_SECONDS', 20),

    // Longueur du code court d'appairage (I-14 / EX-02) : saisi à la main,
    // annoncé à l'oral. Ordre de grandeur évoqué : 6 caractères.
    // ⚠️ Point de départ non validé (6).
    'short_code_length' => (int) env('INTERFLO_SHORT_CODE_LENGTH', 6),

    // Alphabet du code court (EX-03) : lisible à l'oral, SANS caractères
    // ambigus — pas de 0/O, 1/I/L, 5/S.
    // ⚠️ Point de départ non validé : 22 lettres + 7 chiffres.
    'short_code_alphabet' => (string) env('INTERFLO_SHORT_CODE_ALPHABET', 'ABCDEFGHJKMNPQRTUVWXYZ2346789'),

    // Nombre maximum de relances d'un tour par l'animateur, format buzzer
    // (I-7 / EX-25) : « jusqu'à 3 fois, à sa main ». Le nombre exact reste
    // ouvert (§16 question 7 de INTERFLO_PRODUCT.md).
    // ⚠️ Point de départ non validé (3).
    'buzzer_max_relaunches' => (int) env('INTERFLO_BUZZER_MAX_RELAUNCHES', 3),

    // Pourcentage de questions de culture générale (I-32 / EX-37) : les
    // questions sont majoritairement tirées de l'émission elle-même, mais
    // la répartition exacte n'est PAS arrêtée par le PO (§16 question 9).
    // ⚠️ Non arrêté : null tant que le PO n'a pas tranché.
    'general_culture_percent' => ($value = env('INTERFLO_GENERAL_CULTURE_PERCENT')) === null || $value === ''
        ? null
        : (int) $value,

    /*
    |----------------------------------------------------------------------
    | Vérification du numéro de téléphone (I-8)
    |----------------------------------------------------------------------
    |
    | Toutes les valeurs ci-dessous sont des points de départ NON VALIDÉS.
    |
    */

    // Longueur du code OTP envoyé par SMS.
    // ⚠️ Point de départ non validé (6 chiffres).
    'otp_length' => (int) env('INTERFLO_OTP_LENGTH', 6),

    // Durée de validité d'un code OTP, en secondes.
    // ⚠️ Point de départ non validé (300 s = 5 min).
    'otp_ttl_seconds' => (int) env('INTERFLO_OTP_TTL_SECONDS', 300),

    // Nombre maximum de tentatives de saisie par challenge avant sa mort.
    // ⚠️ Point de départ non validé (5).
    'otp_max_attempts' => (int) env('INTERFLO_OTP_MAX_ATTEMPTS', 5),

    // Nombre maximum de demandes de code par numéro et par heure.
    // ⚠️ Point de départ non validé (10).
    'otp_request_throttle_per_hour' => (int) env('INTERFLO_OTP_REQUEST_THROTTLE_PER_HOUR', 10),

    // Nombre maximum de tentatives de vérification par numéro et par minute
    // (filet en plus de otp_max_attempts, qui borne chaque challenge).
    // ⚠️ Valeur introduite par le backend, NON demandée par la mission :
    // point de départ non validé (10), à arbitrer.
    'otp_verify_throttle_per_minute' => (int) env('INTERFLO_OTP_VERIFY_THROTTLE_PER_MINUTE', 10),

];
