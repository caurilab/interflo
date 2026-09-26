/**
 * Endpoints du contrat d'API v1 — Interflo.
 *
 * Routes EXACTES du rapport backend 2026-09-26 — n'en inventer aucune.
 * Accès public pour phone/* et pairing/resolve ; Bearer Sanctum +
 * numéro vérifié pour sessions/attach.
 */
import { request } from './client';
import type {
  AnswerResponse,
  PairingResolution,
  PhoneVerification,
  PlayStateResponse,
} from './types';

/**
 * `POST /api/v1/players/phone/request-code` — public, throttle 10/h/numéro.
 * Succès : 204 vide (identique que le numéro soit connu ou non — non-énumération).
 * Erreurs : 422 (phone, format E.164), 429.
 *
 * @param phone Numéro au format international E.164 (ex. +33612345678).
 */
export function requestPhoneCode(phone: string): Promise<undefined> {
  return request({
    method: 'POST',
    path: '/api/v1/players/phone/request-code',
    body: { phone },
  });
}

/**
 * `POST /api/v1/players/phone/verify` — public, throttle 10/min/numéro.
 * Succès : 200 {token, player:{id, phone, phone_verified_at}}.
 * Erreurs : 422 (code — « invalide ou expiré », générique), 429.
 */
export function verifyPhoneCode(
  phone: string,
  code: string,
): Promise<PhoneVerification> {
  return request({
    method: 'POST',
    path: '/api/v1/players/phone/verify',
    body: { phone, code },
  });
}

/**
 * `POST /api/v1/pairing/resolve` — public.
 * Succès : 200 {data:{tenant:{name}, emission:{title}, session:{id, status}}}.
 * Erreur : 404 générique (« Code inconnu ou expiré »).
 *
 * ⚠️ Le code est un pointeur PUBLIC (I-15) : il ne passe JAMAIS en Bearer,
 * n'est jamais stocké comme un secret, et ne donne aucune autorisation.
 */
export function resolvePairingCode(code: string): Promise<PairingResolution> {
  return request({
    method: 'POST',
    path: '/api/v1/pairing/resolve',
    body: { code },
  });
}

/**
 * `POST /api/v1/sessions/attach` — Bearer Sanctum + numéro vérifié.
 * Succès : 200, même forme que resolve. Erreurs : 401, 403, 422 (session_id).
 */
export function attachSession(
  sessionId: number,
  token: string,
): Promise<PairingResolution> {
  return request({
    method: 'POST',
    path: '/api/v1/sessions/attach',
    body: { session_id: sessionId },
    token,
  });
}

/**
 * `DELETE /api/v1/sessions/attach` — Bearer Sanctum + numéro vérifié.
 * Succès : 204 (idempotent). Erreurs : 401, 403.
 */
export function detachSession(token: string): Promise<undefined> {
  return request({ method: 'DELETE', path: '/api/v1/sessions/attach', token });
}

/**
 * `GET /api/v1/play/state` — Bearer Sanctum + numéro vérifié.
 * Succès : 200 {data: PlayState} — 6 états (idle/waiting/question/answered/
 * locked/finished), voir PlayState dans types.ts.
 *
 * ⚠️ Transport PROVISOIRE (polling HTTP sobre, en attente de l'ADR temps
 * réel) avec effet de bord documenté côté serveur : le premier poll qui
 * trouve une fenêtre ouverte SERT la question au joueur (pose served_at,
 * EX-20) — la fenêtre personnelle démarre là.
 */
export function getPlayState(token: string): Promise<PlayStateResponse> {
  return request({ method: 'GET', path: '/api/v1/play/state', token });
}

/**
 * `POST /api/v1/rounds/{round}/answer` — Bearer Sanctum + numéro vérifié.
 * Body : `{answer_index: 0..3 (I-4), client_timestamp: ms epoch, horodatage
 * du geste SUR L'APPAREIL (I-6), corrigé de l'offset d'horloge (EX-16)}`.
 * Succès : 200 {data:{correct, server_time}} — un bit (R-5).
 * Rejets codés : WINDOW_CLOSED, SESSION_ENDED, NOT_ATTACHED,
 * POPULATION_MISMATCH, PLAYER_LOCKED, NOT_SERVED (403), ALREADY_ANSWERED
 * (409), IMPOSSIBLE_TIMESTAMP, TOO_FAST (422), WINDOW_EXCEEDED (403) —
 * exposés via `ApiError.code` (+ `serverTime` pour ré-étalonner l'horloge).
 */
export function submitRoundAnswer(
  roundId: number,
  answerIndex: number,
  clientTimestampMs: number,
  token: string,
): Promise<AnswerResponse> {
  return request({
    method: 'POST',
    path: `/api/v1/rounds/${roundId}/answer`,
    body: { answer_index: answerIndex, client_timestamp: clientTimestampMs },
    token,
  });
}
