/**
 * Configuration réseau — Interflo.
 *
 * ⚠️ POINT DE DÉPART DEV : l'URL de base vise l'API Laravel servie par
 * Laravel Herd (site `api.interflo.test`, HTTPS). Le simulateur iOS
 * partage le réseau et le DNS de la machine hôte, donc `api.interflo.test`
 * y est résolu. Pour un appareil physique ou un environnement de recette,
 * remplacer par l'URL d'entrée adéquate — JAMAIS en dur dans les composants :
 * tout passe par ce fichier.
 *
 * 🧪 MODE TÉLÉPHONE (Expo Go) : l'appareil physique ne résout PAS
 * `api.interflo.test` ni `localhost`. On vise donc l'IP LAN de la machine de
 * dev, avec l'API servie sur `0.0.0.0` :
 *   php artisan serve --host=0.0.0.0 --port=8000
 * (Reverb écoute déjà sur 0.0.0.0:8080). Si l'IP change (autre Wi-Fi),
 * mettre à jour DEV_HOST ci-dessous.
 */
const DEV_HOST = '192.168.1.5';

export const API_CONFIG = {
  /** Racine de l'API (sans suffixe /api/v1 : celui-ci vit dans les endpoints). */
  baseUrl: `http://${DEV_HOST}:8000`,
  /**
   * Délai maximum d'une requête — le public visé est sur connexions
   * limitées : mieux vaut un échec explicite et rapide qu'un spinner infini.
   * Point de départ NON validé par le PO.
   */
  requestTimeoutMs: 10_000,
} as const;

/**
 * Configuration du transport temps réel Reverb (D-002 §4.1).
 *
 * ⚠️ POINT DE DÉPART DEV : la clé `key` est l'identifiant PUBLIC de l'app
 * Reverb (REVERB_APP_KEY côté API) — pas un secret, elle voyage dans l'URL
 * de la connexion. Elle est générée par `php artisan reverb:install` et doit
 * rester alignée avec `.env` de l'API. En production, elle viendra d'une
 * configuration exposée par l'API (TODO), pas d'une valeur en dur.
 */
export const REVERB_CONFIG = {
  key: 'zwipvzkreqbtb5avs1bm',
  /** Hôte du serveur Reverb. `localhost` = simulateur ; IP LAN = téléphone. */
  host: DEV_HOST,
  port: 8080,
  /** ws:// en local (scheme http) ; wss:// en production. */
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
} as const;
