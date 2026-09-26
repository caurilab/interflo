/**
 * Configuration d'accès à l'API Interflo (console animateur).
 *
 * ⚠️ Règle projet : l'URL de base ne doit JAMAIS apparaître en dur dans un
 * composant — elle vit ici, point d'entrée unique.
 */
export const apiConfig = {
  /**
   * URL de base de l'API (sans /api/v1).
   * ⚠️ Point de départ dev : instance Laravel servie par Herd
   * (`api.interflo.test`, HTTPS). À remplacer par une résolution par
   * environnement dès que le déploiement sera arrêté.
   */
  baseUrl: 'https://api.interflo.test',

  /**
   * Intervalle de polling de l'état du thème (`GET pilot/themes/{id}/state`),
   * en millisecondes.
   * ⚠️ Point de départ NON VALIDÉ : 2 s. Le transport temps réel n'est pas
   * choisi (ADR en attente côté backend) — le polling sobre est provisoire.
   */
  statePollIntervalMs: 2000,
} as const;

/**
 * Configuration du transport temps réel Reverb (D-002 §4.2).
 * ⚠️ POINT DE DÉPART DEV : la clé est l'identifiant PUBLIC de l'app Reverb
 * (REVERB_APP_KEY côté API), alignée sur `.env` de l'API.
 */
export const reverbConfig = {
  key: 'zwipvzkreqbtb5avs1bm',
  host: 'localhost',
  port: 8080,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
} as const;
