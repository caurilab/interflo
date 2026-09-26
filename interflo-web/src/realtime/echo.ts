/**
 * Client Echo (Laravel) pour le transport temps réel Reverb (D-002 §4.2).
 *
 * La console animateur écoute le push `pilot.state` (état pilote + compteurs
 * EX-33) : à sa réception, l'état affiché est mis à jour immédiatement. Le
 * polling reste le transport dégradé (D-1).
 */
import Echo from 'laravel-echo';
import { reverbConfig } from '../config/apiConfig';

/** Crée une instance Echo configurée pour Reverb (protocole Pusher). */
export function createEcho() {
  return new Echo({
    broadcaster: 'pusher',
    key: reverbConfig.key,
    wsHost: reverbConfig.host,
    wsPort: reverbConfig.port,
    wssPort: reverbConfig.port,
    forceTLS: reverbConfig.forceTLS,
    disableStats: true,
    enabledTransports: [...reverbConfig.enabledTransports],
  });
}

/** Nom du canal pilote d'une session (D-002 §4.2). */
export function pilotChannelName(sessionId: number): string {
  return `pilot.${sessionId}`;
}
