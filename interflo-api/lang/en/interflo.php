<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Interflo server messages (English)
    |--------------------------------------------------------------------------
    |
    | No hard-coded user-facing text (docs/CONVENTIONS.md §2). Les deux
    | locales partent ensemble ou pas du tout.
    |
    */

    // SMS body carrying the OTP code. Player locale unknown at this stage.
    'otp_sms' => 'Interflo: your verification code is :code. It expires in :minutes minutes.',

    // Generic verification failure: identical whatever the cause — no
    // enumeration, no oracle.
    'phone_code_invalid' => 'Invalid or expired code. Please request a new code.',

    // Pairing resolution: generic 404 (unknown or expired code — EX-04).
    'pairing_code_not_found' => 'Unknown or expired code.',

    // Attachment refused: the session has ended (I-16).
    'session_ended' => 'This game session has ended.',

    // Phone number must be verified before playing (I-8).
    'phone_not_verified' => 'Your phone number must be verified to continue.',

    /*
    |----------------------------------------------------------------------
    | Elimination format — server-side answer rejections (mission §3)
    |----------------------------------------------------------------------
    |
    | Each rejection carries a stable code ('error' in JSON) consumed by
    | mobile and web clients, plus server_time for clock sync (EX-16).
    |
    */

    'answer' => [
        // Outside the window, the server refuses (I-2 / EX-11 / INV-8 / CA-02).
        'window_closed' => 'The answer window is not open.',
        // Show over, access dead (I-16).
        'session_ended' => 'This game session has ended.',
        // The player is not attached to the theme's session.
        'not_attached' => 'You are not attached to this game session.',
        // The two populations never compete together (I-1 / EX-23).
        'population_mismatch' => 'This game does not concern your population.',
        // Locked until the end of the theme (EX-32).
        'player_locked' => 'You are eliminated from this game.',
        // The question was never served to this player (EX-20).
        'not_served' => 'No question was served to you on this round.',
        // One answer per player per round.
        'already_answered' => 'You have already answered this round.',
        // Physically impossible timestamp (I-6 / EX-17 / CA-03).
        'impossible_timestamp' => 'Impossible answer timestamp.',
        // Anti-automation floor (I-30 / EX-18).
        'too_fast' => 'Answer too fast to be human.',
        // Personal window exceeded (EX-20).
        'window_exceeded' => 'Your answer window has expired.',
    ],

    /*
    |----------------------------------------------------------------------
    | Show-host piloting (⚠️ PROVISIONAL auth — see EnsurePilotToken)
    |----------------------------------------------------------------------
    */

    'pilot' => [
        'unauthorized' => 'Unknown pilot token.',
        'theme_finished' => 'This game is finished.',
        'previous_round_not_closed' => 'The previous round is not closed.',
        'round_currently_open' => 'A window is open — wait for its closure before scheduling the next round.',
        'rounds_exhausted' => 'All 5 rounds of this game are already scheduled.',
        'round_not_pending' => 'This round is not waiting to be opened.',
        'round_not_open' => 'This round is not open.',
        'rounds_not_all_closed' => 'All rounds must be closed before the endgame.',
        'winners_count_invalid' => 'The winners count must be 1, 3 or 5 (I-28).',
        // EX-40: no human validation, no broadcast.
        'question_not_validated' => 'This question has not been validated by a human.',
        'question_already_used' => 'This question is already scheduled on a round.',
        'question_round_mismatch' => 'This question does not target this round.',
    ],

];
