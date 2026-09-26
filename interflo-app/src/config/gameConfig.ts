/**
 * Constantes de jeu — Interflo.
 *
 * ⚠️ Règle du projet (PROMPT-DEMARRAGE.md §2) : aucune valeur numérique
 * n'est validée. Trois valeurs SEULEMENT sont scellées par décision PO :
 * 4 propositions (I-4), 5 manches (I-27), 1/3/5 gagnants (I-28).
 * Toute autre valeur ci-dessous est un POINT DE DÉPART PARAMÉTRABLE,
 * à faire valider ou corriger par le PO.
 */

/** Valeurs scellées — règles du jeu, pas des réglages. */
export const SEALED = {
  /** I-4 : choix multiple à 4 propositions. */
  propositionCount: 4,
  /** I-27 : format élimination en 5 manches. */
  eliminationRoundCount: 5,
  /** I-28 : gagnants retenus en fin de partie (configurable par tenant). */
  possibleWinnerCounts: [1, 3, 5] as const,
} as const;

/** Points de départ paramétrables — NON validés par le PO. */
export const DEFAULTS = {
  /**
   * ⚠️ Longueur du code court d'appairage — point de départ.
   * Ordre de grandeur évoqué au cadrage : 6 caractères (I-14).
   * La longueur et l'alphabet exacts seront arrêtés côté serveur (§16, q15).
   */
  shortCodeLength: 6,
  /**
   * ⚠️ Alphabet lisible à l'oral (EX-03) — point de départ.
   * Caractères ambigus retirés : 0/O, 1/I/L, 5/S. À arrêter définitivement
   * avec le serveur qui génère les codes.
   */
  shortCodeAlphabet: 'ABCDEFGHJKMNPQRTUVWXYZ2346789',
  /**
   * ⚠️ Longueur du code OTP de vérification téléphone (I-8) — point de
   * départ aligné sur `otp_length` côté serveur (config/interflo.php = 6,
   * rapport backend 2026-09-26). La valeur serveur fait foi.
   */
  otpLength: 6,
  /**
   * ⚠️ Intervalle de polling de `GET /api/v1/play/state` — transport
   * PROVISOIRE, point de départ NON validé, en attente de l'ADR temps
   * réel (04-architecture §5). 2 s = compromis réactivité/économie de
   * données (R-11) : ~1,5 Ko/min au repos. Le polling ne tourne que
   * lorsqu'une session est attachée ET l'app au premier plan.
   */
  pollIntervalMs: 2_000,
  /**
   * Nombre d'échecs de polling consécutifs avant de signifier au joueur
   * que la connexion est perdue (bandeau discret, pas de blocage — les
   * tentatives continuent). Point de départ non validé.
   */
  pollFailureThreshold: 3,
} as const;
