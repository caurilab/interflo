/**
 * Client Echo (Laravel) pour le transport temps réel Reverb (D-002 §4.1).
 *
 * Le push `round.opened` est un ACCÉLÉRATEUR de latence : à sa réception,
 * le client déclenche un poll immédiat (l'état serveur reste la source de
 * vérité — `served_at`/EX-20 est posé côté serveur). Le polling reste le
 * transport dégradé (D-1) quand le WebSocket est injoignable.
 */
import Echo from 'laravel-echo';
// Build React Native de pusher-js : il utilise le WebSocket natif de RN,
// pas `window.WebSocket` (absent en React Native).
import Pusher from 'pusher-js/react-native';
import { REVERB_CONFIG } from '../config/api';

/** Crée une instance Echo configurée pour Reverb (protocole Pusher). */
export function createEcho() {
  return new Echo({
    broadcaster: 'pusher',
    client: Pusher,
    key: REVERB_CONFIG.key,
    wsHost: REVERB_CONFIG.host,
    wsPort: REVERB_CONFIG.port,
    wssPort: REVERB_CONFIG.port,
    forceTLS: REVERB_CONFIG.forceTLS,
    disableStats: true,
    enabledTransports: [...REVERB_CONFIG.enabledTransports],
  });
}

/**
 * Nom du canal public de la population d'une session (D-002 §4.1) —
 * identique au canal du broadcast `App\Events\RoundOpened` côté API.
 */
export function populationChannelName(sessionId: number, population: string): string {
  return `session.${sessionId}.${population}`;
}
