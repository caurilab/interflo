/**
 * Attachement à une session — étape commune du flux d'appairage.
 *
 * Appelé après la confirmation de l'émission, ou juste après la
 * vérification du numéro (I-8) quand un token frais est disponible.
 *
 * Contrat (rapport backend 2026-09-26) :
 * - `POST /api/v1/sessions/attach` exige Bearer Sanctum + numéro vérifié ;
 * - 401/403 → token absent, invalide ou numéro non vérifié : le joueur
 *   repart par le flux téléphone (le token local est oublié) ;
 * - succès → même pointeur que resolve (tenant / émission / session).
 */
import { ApiError } from '../api/client';
import { attachSession } from '../api/endpoints';
import { clearToken, getStoredToken } from '../storage/authToken';

export type AttachResult =
  | { status: 'attached' }
  /** Pas de token, ou token rejeté : il faut passer par le flux téléphone. */
  | { status: 'phoneRequired' }
  | { status: 'error'; error: ApiError };

/**
 * Tente l'attachement. `freshToken` (juste issu de la vérification OTP)
 * prime sur le token persisté ; à défaut c'est le token persisté qui sert.
 */
export async function attachToSession(
  sessionId: number,
  freshToken?: string,
): Promise<AttachResult> {
  const token = freshToken ?? (await getStoredToken());
  if (!token) {
    return { status: 'phoneRequired' };
  }

  try {
    await attachSession(sessionId, token);
    return { status: 'attached' };
  } catch (error) {
    if (error instanceof ApiError && (error.status === 401 || error.status === 403)) {
      // Token mort ou numéro plus vérifié : on oublie le token local et
      // on renvoie vers la vérification du numéro (I-8).
      await clearToken();
      return { status: 'phoneRequired' };
    }
    return {
      status: 'error',
      error: error instanceof ApiError ? error : new ApiError(0, 'unknown'),
    };
  }
}
