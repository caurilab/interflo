<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Filament admin panel (English)
    |--------------------------------------------------------------------------
    |
    | No hard-coded user-facing string in code (docs/CONVENTIONS.md §2).
    |
    */

    // Navigation groups.
    'nav_group_game' => 'Game',
    'nav_group_settings' => 'Settings',

    // Tenant resource.
    'tenants' => [
        'label' => 'Channel',
        'plural_label' => 'Channels',
        'fields' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'external_reference' => 'External reference (BOS)',
        ],
        'sections' => [
            'identity' => 'Identity',
            'game_config' => 'Game configuration',
        ],
    ],

    // Per-tenant game configuration (docs/07 §2.1).
    'game_config' => [
        'answer_window_seconds' => 'Answer window duration (seconds)',
        'answer_window_seconds_helper' => 'Empty = configurable default value (I-31). Unvalidated starting point.',
        'measured_mode_enabled' => 'Measured mode enabled',
        'measured_mode_helper' => 'Per-channel option for the first season (I-25). ⚠️ No default decided (EX-30): disabled here, to be arbitrated.',
        'endgame_rule' => 'Endgame rule (I-28)',
        'endgame_rule_all_survivors' => 'All survivors win',
        'endgame_rule_draw' => 'Random draw among survivors',
        'endgame_rule_draw_warning' => '⚠️ A random draw shifts the game from skill to chance — legal framework to be assessed (§10.4).',
        'winners_count' => 'Number of winners kept (I-28)',
        'winners_count_helper' => 'Options sealed by PO decision: 1, 3 or 5.',
        'persistent_ranking_enabled' => 'Persistent ranking across broadcasts',
        'persistent_ranking_helper' => 'Configurable per tenant (I-40). ⚠️ Mechanics unspecified (EX-46): switch only.',
    ],

    // Emission resource.
    'emissions' => [
        'label' => 'Broadcast',
        'plural_label' => 'Broadcasts',
        'fields' => [
            'tenant' => 'Channel',
            'title' => 'Title',
            'external_reference' => 'External reference (BOS)',
        ],
    ],

    // GameSession resource.
    'game_sessions' => [
        'label' => 'Game session',
        'plural_label' => 'Game sessions',
        'fields' => [
            'emission' => 'Broadcast',
            'status' => 'Status',
            'started_at' => 'Started at',
            'ended_at' => 'Ended at',
            'pairing_code' => 'Current pairing code',
            'no_pairing_code' => 'No active code',
            'pilot_token' => 'Show-host pilot token (PROVISIONAL)',
        ],
        'status' => [
            'scheduled' => 'Scheduled',
            'live' => 'Live',
            'ended' => 'Ended',
        ],
        'actions' => [
            'start' => 'Start',
            'end' => 'Close',
            'rotate_code' => 'Regenerate pairing code',
            'started' => 'Session started.',
            'ended' => 'Session closed.',
            'code_rotated' => 'New pairing code generated.',
        ],
    ],

    // Question resource — question bank (EX-40: human validation).
    'questions' => [
        'label' => 'Question',
        'plural_label' => 'Questions',
        'in_bank' => 'In bank',
        'validated' => 'Validated',
        'not_validated' => 'NOT VALIDATED',
        'validated_by_at' => 'Validated by :name on :at',
        'fields' => [
            'theme' => 'Theme',
            'round_number' => 'Round',
            'body' => 'Body',
            'propositions' => 'Propositions (exactly 4 — I-4)',
            'proposition' => 'Proposition',
            'proposition_n' => 'Proposition :n',
            // ⚠️ INV-2: the correct answer is only visible in the back-office.
            'correct_index' => 'Correct answer (back-office only)',
            'source' => 'Source',
            'validation' => 'Human validation (EX-40)',
        ],
        'sources' => [
            'plateau' => 'Show floor',
            'general_culture' => 'General knowledge',
        ],
        'actions' => [
            'validate' => 'Validate',
            'validated' => 'Question validated — broadcastable.',
        ],
    ],

    // "Thresholds and settings" page (read-only).
    'thresholds' => [
        'title' => 'Thresholds and settings',
        'navigation_label' => 'Thresholds and settings',
        'intro' => 'Active values of config(\'interflo\'). Read-only: the environment drives them (INTERFLO_* variables).',
        'unvalidated_notice' => '⚠️ Every configurable value is an unvalidated starting point, to be corrected at the first shooting.',
        'columns' => [
            'setting' => 'Setting',
            'value' => 'Active value',
            'nature' => 'Nature',
            'reference' => 'Reference',
        ],
        'nature_sealed' => 'Sealed by PO decision',
        'nature_configurable' => 'Configurable (unvalidated starting point)',
        'nature_unset' => 'Not decided by the PO',
        'settings' => [
            'propositions_per_question' => 'Propositions per question',
            'elimination_rounds' => 'Elimination format rounds',
            'winner_count_options' => 'Winner count options',
            'anti_automation_floor_ms' => 'Anti-automation floor (ms)',
            'answer_window_seconds' => 'Default answer window (s)',
            'server_envelope_seconds' => 'Server envelope (s)',
            'pairing_code_rotation_seconds' => 'Pairing code rotation (s)',
            'short_code_length' => 'Short code length',
            'short_code_alphabet' => 'Short code alphabet',
            'buzzer_max_relaunches' => 'Max relaunches per round (buzzer)',
            'general_culture_percent' => 'General knowledge share (%)',
            'otp_length' => 'OTP code length',
            'otp_ttl_seconds' => 'OTP code validity (s)',
            'otp_max_attempts' => 'Max attempts per OTP challenge',
            'otp_request_throttle_per_hour' => 'Max OTP requests / number / hour',
            'otp_verify_throttle_per_minute' => 'Max OTP verifications / number / minute',
        ],
    ],

];
