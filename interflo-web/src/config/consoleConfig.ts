/**
 * Configuration des consoles de direct.
 *
 * ⚠️ Règle projet : AUCUNE valeur numérique n'est validée par le PO.
 * Toute valeur ci-dessous est un POINT DE DÉPART PARAMÉTRABLE, jamais une
 * constante métier. Les valeurs scellées (4 propositions, 5 manches,
 * 1/3/5 gagnants) ne sont PAS ici : ce sont des règles du jeu, pas des réglages.
 */
export const consoleConfig = {
  /**
   * F-5 / EXA-5 — Enveloppe à meubler par l'animateur après chaque question,
   * en secondes. Rappel visible en permanence sur la console animateur.
   *
   * ⚠️ Point de départ non validé : ordre de grandeur 20-25 s
   * (docs/INTERFLO_PRODUCT.md §4.4, non mesuré). La valeur réelle dépend de la
   * durée de fenêtre d'écoute, encore inconnue (question ouverte n°3).
   * TODO : à terme, cette valeur doit venir de la configuration du tenant
   * (I-31 côté serveur), pas d'un fichier statique.
   */
  fillerEnvelopeSeconds: 22,

  /**
   * Ordre de grandeur du nombre maximal de relances en format buzzer (I-7).
   * ⚠️ Non arrêté : « fixe ou à la main de l'animateur » est une question
   * ouverte (n°7). Affiché à titre indicatif uniquement.
   */
  buzzerMaxRelancesIndicatif: 3,
} as const;
