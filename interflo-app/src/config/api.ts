/**
 * Configuration réseau — Interflo.
 *
 * ⚠️ POINT DE DÉPART DEV : l'URL de base vise l'API Laravel servie par
 * Laravel Herd (site `api.interflo.test`, HTTPS). Le simulateur iOS
 * partage le réseau et le DNS de la machine hôte, donc `api.interflo.test`
 * y est résolu. Pour un appareil physique ou un environnement de recette,
 * remplacer par l'URL d'entrée adéquate — JAMAIS en dur dans les composants :
 * tout passe par ce fichier.
 */
export const API_CONFIG = {
  /** Racine de l'API (sans suffixe /api/v1 : celui-ci vit dans les endpoints). */
  baseUrl: 'https://api.interflo.test',
  /**
   * Délai maximum d'une requête — le public visé est sur connexions
   * limitées : mieux vaut un échec explicite et rapide qu'un spinner infini.
   * Point de départ NON validé par le PO.
   */
  requestTimeoutMs: 10_000,
} as const;
