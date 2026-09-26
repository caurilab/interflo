/**
 * Configuration réseau — Interflo.
 *
 * ⚠️ POINT DE DÉPART DEV : l'URL de base vise l'API Laravel locale
 * (`php artisan serve`). 127.0.0.1 fonctionne car le simulateur iOS
 * partage le réseau de la machine hôte. Pour un appareil physique ou
 * un environnement de recette, remplacer par l'URL d'entrée adéquate —
 * JAMAIS en dur dans les composants : tout passe par ce fichier.
 */
export const API_CONFIG = {
  /** Racine de l'API (sans suffixe /api/v1 : celui-ci vit dans les endpoints). */
  baseUrl: 'http://127.0.0.1:8000',
  /**
   * Délai maximum d'une requête — le public visé est sur connexions
   * limitées : mieux vaut un échec explicite et rapide qu'un spinner infini.
   * Point de départ NON validé par le PO.
   */
  requestTimeoutMs: 10_000,
} as const;
