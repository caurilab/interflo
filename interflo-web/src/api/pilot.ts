import { apiConfig } from '../config/apiConfig'

/**
 * Client HTTP de la frontière PILOTAGE de l'API Interflo.
 *
 * Contrat consommé tel quel : docs/rapports/2026-09-26-backend-moteur-elimination.md
 * (section « Contrat exposé »). AUCUNE route n'est inventée ici.
 *
 * ⚠️ Auth animateur PROVISOIRE et non spécifiée : token opaque de session
 * porté par l'en-tête `X-Pilot-Token` (middleware pilot.token). Le jeton est
 * saisi par l'animateur et stocké en sessionStorage — jamais réaffiché.
 *
 * Formes de réponse (enveloppe Laravel `{data: …}`) :
 * - RoundResource        : {id, theme_id, round_number, question_id, status, window_opened_at, window_closed_at}
 * - état du thème        : {theme, current_round|null, survivors_count, participants_count, server_time}
 * - fin de partie        : [{player_id, rank|null}] (idempotent)
 * - erreurs métier       : 422 avec clé de champ ; ressource hors session : 404 (pas d'oracle, R-10)
 */

// ─── Types du contrat ───────────────────────────────────────────────────────

export type RoundStatus = 'pending' | 'open' | 'closed'
export type ThemeStatus = 'active' | 'finished'
export type Population = 'studio' | 'home'

/** Manche telle que renvoyée par RoundResource (open/close/store). */
export interface PilotRound {
  id: number
  theme_id: number
  round_number: number
  question_id: number
  status: RoundStatus
  window_opened_at: string | null
  window_closed_at: string | null
}

/**
 * Manche courante dans l'état du thème : forme réduite (sans theme_id ni
 * question_id — voir EliminationThemeService::pilotState).
 */
export interface PilotCurrentRound {
  id: number
  round_number: number
  status: RoundStatus
  window_opened_at: string | null
  window_closed_at: string | null
}

/** Réponse de POST pilot/themes (ThemeResource). */
export interface PilotTheme {
  id: number
  game_session_id: number
  title: string
  population: Population
  status: ThemeStatus
  current_round: PilotRound | null
}

/** Réponse de GET pilot/themes/{theme}/state (ThemeStateResource). */
export interface PilotThemeState {
  theme: {
    id: number
    title: string
    population: Population
    status: ThemeStatus
  }
  current_round: PilotCurrentRound | null
  /** Compteur studio EX-33 — le chiffre qui descend à l'écran. */
  survivors_count: number
  participants_count: number
  server_time: string
}

/** Réponse de POST pilot/themes/{theme}/finish (WinnerResource). */
export interface PilotWinner {
  player_id: number
  rank: number | null
}

// ─── Erreur typée ───────────────────────────────────────────────────────────

export class PilotApiError extends Error {
  /** Statut HTTP — null quand la requête n'a pas atteint le serveur. */
  readonly status: number | null
  /** Code d'erreur stable du backend, quand fourni. */
  readonly code: string | null

  constructor(message: string, status: number | null, code: string | null = null) {
    super(message)
    this.name = 'PilotApiError'
    this.status = status
    this.code = code
  }

  /** 401 (jeton inconnu) ou 404 (ressource hors session) : jeton inutilisable. */
  get isAuthFailure(): boolean {
    return this.status === 401 || this.status === 404
  }
}

// ─── Jeton pilote (sessionStorage — jamais réaffiché après saisie) ─────────

const TOKEN_STORAGE_KEY = 'interflo-pilot-token'
const THEME_STORAGE_KEY = 'interflo-pilot-theme-id'

export function getPilotToken(): string | null {
  return sessionStorage.getItem(TOKEN_STORAGE_KEY)
}

export function setPilotToken(token: string): void {
  sessionStorage.setItem(TOKEN_STORAGE_KEY, token)
}

export function clearPilotToken(): void {
  sessionStorage.removeItem(TOKEN_STORAGE_KEY)
  sessionStorage.removeItem(THEME_STORAGE_KEY)
}

/**
 * Thème en cours de pilotage, mémorisé pour survivre à un rechargement de la
 * tablette en plein direct. ⚠️ Supposition documentée : le contrat n'expose
 * aucune route de liste des thèmes — sans cette mémoire locale, un refresh
 * perdrait la partie en cours.
 */
export function getPilotThemeId(): number | null {
  const raw = sessionStorage.getItem(THEME_STORAGE_KEY)
  if (raw === null) return null
  const id = Number.parseInt(raw, 10)
  return Number.isInteger(id) && id > 0 ? id : null
}

export function setPilotThemeId(id: number): void {
  sessionStorage.setItem(THEME_STORAGE_KEY, String(id))
}

/** Oublie le thème courant (partie terminée, nouvelle partie) — sans toucher au jeton. */
export function clearPilotThemeId(): void {
  sessionStorage.removeItem(THEME_STORAGE_KEY)
}

// ─── Requêtes ───────────────────────────────────────────────────────────────

interface RequestOptions {
  method?: 'GET' | 'POST'
  token: string
  body?: Record<string, unknown>
}

async function request<T>(path: string, options: RequestOptions): Promise<T> {
  let response: Response
  try {
    response = await fetch(`${apiConfig.baseUrl}/api/v1${path}`, {
      method: options.method ?? 'GET',
      headers: {
        'X-Pilot-Token': options.token,
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      },
      body: options.body ? JSON.stringify(options.body) : undefined,
    })
  } catch {
    // Réseau coupé / serveur injoignable : aucun statut HTTP.
    throw new PilotApiError('network', null)
  }

  if (!response.ok) {
    const payload: unknown = await response.json().catch(() => null)
    const message =
      payload !== null && typeof payload === 'object' && 'message' in payload
        ? String((payload as { message: unknown }).message)
        : `HTTP ${response.status}`
    const code =
      payload !== null && typeof payload === 'object' && 'error' in payload
        ? String((payload as { error: unknown }).error)
        : null
    throw new PilotApiError(message, response.status, code)
  }

  // Enveloppe Laravel : {data: …}.
  const envelope = (await response.json()) as { data: T }
  return envelope.data
}

// ─── Routes du contrat pilote (toutes sous /api/v1/pilot) ──────────────────

/** POST pilot/themes — crée le thème + sa manche 1 (question validée exigée, EX-40). */
export function createTheme(
  token: string,
  input: { title: string; population: Population; firstQuestionId: number },
): Promise<PilotTheme> {
  return request<PilotTheme>('/pilot/themes', {
    method: 'POST',
    token,
    body: {
      title: input.title,
      population: input.population,
      first_question_id: input.firstQuestionId,
    },
  })
}

/** POST pilot/rounds — programme la manche suivante (numéro déduit côté serveur). */
export function createRound(token: string, themeId: number, questionId: number): Promise<PilotRound> {
  return request<PilotRound>('/pilot/rounds', {
    method: 'POST',
    token,
    body: { theme_id: themeId, question_id: questionId },
  })
}

/** POST pilot/rounds/{round}/open — ouvre la fenêtre (I-2, état serveur). */
export function openRound(token: string, roundId: number): Promise<PilotRound> {
  return request<PilotRound>(`/pilot/rounds/${roundId}/open`, { method: 'POST', token })
}

/** POST pilot/rounds/{round}/close — ferme la fenêtre (I-2). */
export function closeRound(token: string, roundId: number): Promise<PilotRound> {
  return request<PilotRound>(`/pilot/rounds/${roundId}/close`, { method: 'POST', token })
}

/** GET pilot/themes/{theme}/state — état serveur + compteur survivants (EX-33). */
export function getThemeState(token: string, themeId: number): Promise<PilotThemeState> {
  return request<PilotThemeState>(`/pilot/themes/${themeId}/state`, { token })
}

/** POST pilot/themes/{theme}/finish — fin de partie (I-28), idempotent. */
export function finishTheme(token: string, themeId: number): Promise<PilotWinner[]> {
  return request<PilotWinner[]>(`/pilot/themes/${themeId}/finish`, { method: 'POST', token })
}
