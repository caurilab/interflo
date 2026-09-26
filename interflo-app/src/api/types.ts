/**
 * Types du contrat d'API consommé — Interflo.
 *
 * Source d'autorité : docs/rapports/2026-09-26-backend-postgres-otp-appairage.md
 * (§ « Contrat exposé (pour le mobile) ») et app/Http/Resources du backend.
 * NE RIEN INVENTER : toute route ou tout champ doit exister dans ce contrat.
 */

/**
 * Pointeur public renvoyé par `POST /api/v1/pairing/resolve`
 * et `POST /api/v1/sessions/attach` (même forme pour les deux).
 *
 * Charge utile volontairement minimale (R-9 / R-11) : ni slug, ni
 * références BOS, ni horodatages. Le code d'appairage DÉSIGNE la session,
 * il n'autorise rien (I-15 / INV-6).
 */
export type PairingResolution = {
  data: {
    tenant: { name: string };
    emission: { title: string };
    session: { id: number; status: string };
  };
};

/**
 * Réponse de `POST /api/v1/players/phone/verify` : token Sanctum + joueur.
 */
export type PhoneVerification = {
  token: string;
  player: {
    id: number;
    phone: string;
    phone_verified_at: string | null;
  };
};

/* -------------------------------------------------------------------------- */
/* Jeu élimination — contrat du rapport backend 2026-09-26                     */
/* (« Contrat exposé » § frontière joueur) + PlayerGameStateService.           */
/* Toutes les réponses joueur portent `server_time` (ISO 8601) pour la         */
/* synchronisation d'horloge (EX-16).                                          */
/* -------------------------------------------------------------------------- */

/** Référence publique d'un thème dans les charges utiles joueur. */
export type PlayThemeRef = { id: number; title: string };

/**
 * Manche servie au joueur (état `question`).
 *
 * ⚠️ INV-2 / CA-07 : la question ne porte QUE `body` + 4 `propositions`
 * (scellé I-4) — `correct_index` ne transite JAMAIS jusqu'au client.
 *
 * `served_at` est la base de la fenêtre PERSONNELLE du joueur (EX-20) :
 * posé au premier poll qui sert la question, jamais réinitialisé ensuite.
 */
export type PlayRound = {
  id: number;
  round_number: number;
  served_at: string;
  window_seconds: number;
  question: {
    body: string;
    propositions: string[];
  };
};

/**
 * Machine à états exposée par `GET /api/v1/play/state` — 6 états exacts :
 * idle → waiting → question → answered → (question suivante | locked |
 * finished). Union discriminée sur `state`, calquée sur les formes de
 * PlayerGameStateService (vérifiées dans le code backend).
 */
export type PlayState =
  /** Rien à jouer : pas de session attachée active ou aucun thème. */
  | {
      state: 'idle';
      server_time: string;
      population: string;
      session_id: number | null;
    }
  /** Entre deux manches (I-2) : thème courant, numéro de la dernière manche. */
  | {
      state: 'waiting';
      server_time: string;
      population: string;
      session_id: number;
      theme: PlayThemeRef;
      round_number: number | null;
    }
  /** Fenêtre ouverte, question servie (effet de bord du poll, EX-20). */
  | {
      state: 'question';
      server_time: string;
      population: string;
      session_id: number;
      theme: PlayThemeRef;
      round: PlayRound;
    }
  /** Réponse acceptée : son propre verdict, un bit (R-5) — jamais la bonne réponse. */
  | {
      state: 'answered';
      server_time: string;
      population: string;
      session_id: number;
      theme: PlayThemeRef;
      round_number: number;
      correct: boolean;
    }
  /** Verrouillé jusqu'à la fin du thème (EX-32) — spectateur, pas humilié (M-4). */
  | {
      state: 'locked';
      server_time: string;
      population: string;
      session_id: number;
      theme: PlayThemeRef;
    }
  /** Fin de partie (I-28) : SON bit winner, pas la liste des gagnants. */
  | {
      state: 'finished';
      server_time: string;
      population: string;
      session_id: number;
      theme: PlayThemeRef;
      winner: boolean;
    };

/** Réponse enveloppée de `GET /api/v1/play/state`. */
export type PlayStateResponse = { data: PlayState };

/**
 * Succès de `POST /api/v1/rounds/{round}/answer` : UN BIT (R-5) +
 * `server_time` (EX-16). Jamais la bonne réponse.
 */
export type AnswerResponse = {
  data: { correct: boolean; server_time: string };
};

/**
 * Codes d'erreur stables des rejets de réponse (AnswerRejectedException,
 * forme `{message, error, server_time}`) — consommés pour choisir le
 * message i18n, jamais affichés bruts.
 */
export type AnswerErrorCode =
  | 'WINDOW_CLOSED'
  | 'SESSION_ENDED'
  | 'NOT_ATTACHED'
  | 'POPULATION_MISMATCH'
  | 'PLAYER_LOCKED'
  | 'NOT_SERVED'
  | 'ALREADY_ANSWERED'
  | 'IMPOSSIBLE_TIMESTAMP'
  | 'TOO_FAST'
  | 'WINDOW_EXCEEDED';
