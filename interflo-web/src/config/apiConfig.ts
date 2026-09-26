/**
 * Configuration d'accès à l'API Interflo (console animateur).
 *
 * ⚠️ Règle projet : l'URL de base ne doit JAMAIS apparaître en dur dans un
 * composant — elle vit ici, point d'entrée unique.
 */
export const apiConfig = {
  /**
   * URL de base de l'API (sans /api/v1).
   * ⚠️ Point de départ dev : instance Laravel locale (`php artisan serve`).
   * À remplacer par une résolution par environnement dès que le déploiement
   * sera arrêté.
   */
  baseUrl: 'http://127.0.0.1:8000',

  /**
   * Intervalle de polling de l'état du thème (`GET pilot/themes/{id}/state`),
   * en millisecondes.
   * ⚠️ Point de départ NON VALIDÉ : 2 s. Le transport temps réel n'est pas
   * choisi (ADR en attente côté backend) — le polling sobre est provisoire.
   */
  statePollIntervalMs: 2000,
} as const;
