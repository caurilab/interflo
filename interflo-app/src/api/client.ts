/**
 * Client HTTP minimal — Interflo.
 *
 * fetch nu, sans dépendance : le public visé est sur connexions limitées,
 * chaque kilo-octet de bundle et chaque requête compte.
 *
 * Gestion d'erreurs : toute sortie non 2xx est une `ApiError` portant le
 * statut HTTP. Les écrans traduisent le statut en message i18n — aucun
 * texte utilisateur ne vit ici (CONVENTIONS.md §2).
 *
 * Statuts du contrat (rapport backend 2026-09-26) :
 * - 401 : token absent/invalide (routes Bearer)
 * - 403 : numéro non vérifié
 * - 404 : code d'appairage inconnu ou expiré (réponse générique)
 * - 422 : validation (phone, code, session_id)
 * - 429 : throttle OTP (10/h requête, 10/min vérification)
 */
import { API_CONFIG } from '../config/api';

export class ApiError extends Error {
  /**
   * @param status Statut HTTP, ou 0 si la requête n'a pas abouti
   *               (réseau coupé, DNS, timeout — indiscernables via fetch).
   * @param code   Code d'erreur stable du contrat quand le serveur en
   *               fournit un (ex. TOO_FAST, WINDOW_CLOSED — forme d'erreur
   *               `{message, error, server_time}` des rejets de réponse).
   *               Absent sur les erreurs génériques de validation.
   * @param serverTime Horloge serveur au moment du rejet (EX-16) — permet
   *               de ré-étalonner l'offset d'horloge même sur une erreur.
   */
  constructor(
    public readonly status: number,
    message: string,
    public readonly code?: string,
    public readonly serverTime?: string,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  /** Vrai si l'appareil n'a pas pu joindre le serveur (pas de réponse HTTP). */
  get isNetworkError(): boolean {
    return this.status === 0;
  }
}

type RequestOptions = {
  method: 'GET' | 'POST' | 'DELETE';
  /** Chemin sous la racine API, ex. `/api/v1/pairing/resolve`. */
  path: string;
  body?: Record<string, unknown>;
  /** Token Sanctum (routes Bearer) — jamais le code d'appairage (I-15). */
  token?: string;
};

/**
 * Exécute une requête JSON typée sur le contrat.
 * Résout `undefined` pour les réponses vides (204).
 */
export async function request<T = undefined>({
  method,
  path,
  body,
  token,
}: RequestOptions): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }
  if (token !== undefined) {
    headers.Authorization = `Bearer ${token}`;
  }

  // Timeout explicite : fetch n'en a pas — sur réseau dégradé, une requête
  // sans échéance laisserait l'écran bloqué indéfiniment.
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), API_CONFIG.requestTimeoutMs);

  let response: Response;
  try {
    response = await fetch(`${API_CONFIG.baseUrl}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      signal: controller.signal,
    });
  } catch {
    // Réseau coupé ou timeout abort : indiscernables, même message côté écran.
    throw new ApiError(0, 'network unreachable');
  } finally {
    clearTimeout(timeout);
  }

  if (response.status === 204) {
    return undefined as T;
  }

  if (!response.ok) {
    // Forme d'erreur du contrat : `{message, error, server_time}` (rejets
    // de réponse) ou `{message, errors}` (validation Laravel). On extrait
    // le code stable et l'horloge serveur quand ils existent, sans jamais
    // casser sur un corps inattendu ou vide.
    let code: string | undefined;
    let serverTime: string | undefined;
    try {
      const payload: unknown = await response.json();
      if (typeof payload === 'object' && payload !== null) {
        const errorBody = payload as Record<string, unknown>;
        if (typeof errorBody.error === 'string') {
          code = errorBody.error;
        }
        if (typeof errorBody.server_time === 'string') {
          serverTime = errorBody.server_time;
        }
      }
    } catch {
      // Corps non JSON ou vide : le statut seul suffit aux écrans.
    }
    throw new ApiError(response.status, `HTTP ${response.status}`, code, serverTime);
  }

  return (await response.json()) as T;
}
