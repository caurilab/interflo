/**
 * Machine à états du jeu élimination — pilotée par `GET /api/v1/play/state`.
 *
 * Cycle : idle → waiting → question → answered (verdict juste/faux, I-29)
 * → question suivante | locked (EX-32) | finished (I-28, bit winner).
 *
 * Transport PROVISOIRE : polling HTTP sobre (intervalle
 * `DEFAULTS.pollIntervalMs`, point de départ non validé — en attente de
 * l'ADR temps réel). Garde-fous R-11 (économie de données) :
 * - le polling ne tourne QUE si l'écran de jeu est monté (donc une session
 *   est attachée) ET l'application au premier plan (AppState) ;
 * - reprise immédiate au retour au premier plan, arrêt propre sinon ;
 * - setTimeout récursif : jamais deux requêtes en vol simultanées.
 *
 * Horloge (I-6 / EX-16) : chaque réponse portant `server_time` étalonne
 * une ServerClock ; l'horodatage du geste est corrigé de cet offset.
 *
 * ⚠️ Effet de bord documenté du contrat : le premier poll qui trouve une
 * fenêtre ouverte SERT la question (pose served_at, EX-20) — la fenêtre
 * personnelle du joueur démarre là. Le poll est donc aussi le signal
 * d'ouverture (M-3 : l'ouverture est signifiée par l'application).
 */
import { useCallback, useEffect, useRef, useState } from 'react';
import { AppState } from 'react-native';
import { ApiError } from '../api/client';
import { getPlayState, submitRoundAnswer } from '../api/endpoints';
import type { PlayRound, PlayState } from '../api/types';
import { DEFAULTS } from '../config/gameConfig';
import { createEcho, populationChannelName } from '../realtime/echo';
import { clearToken, getStoredToken } from '../storage/authToken';
import { ServerClock } from './clockSync';

/**
 * Clé de notice i18n pour les rejets de réponse — jamais le code brut
 * du serveur dans l'UI, et un ton qui ne dramatise pas (M-4).
 */
export type RejectionNotice = 'tooFast' | 'windowExceeded' | 'network' | 'generic';

/** Verdict local posé dès le 200 de l'answer, sans attendre le poll suivant. */
type LocalVerdict = { roundId: number; correct: boolean };

export type PlayMachine = {
  /** Dernier état brut renvoyé par le serveur (null avant le premier poll). */
  playState: PlayState | null;
  /** État effectivement affiché (verdict local superposé si réponse fraîche). */
  effectiveState: PlayState | null;
  /** Vrai après N échecs de polling consécutifs (seuil DEFAULTS). */
  connectionLost: boolean;
  /** Une réponse est en cours d'envoi — boutons verrouillés (M-1). */
  sendingAnswer: boolean;
  /** Dernier rejet codé de l'answer (null = rien à signaler). */
  rejection: RejectionNotice | null;
  /**
   * Manche verrouillée localement après un rejet DÉFINITIF (boutons
   * inertes jusqu'à ce que le poll fasse avancer l'état).
   */
  blockedRoundId: number | null;
  /** Le geste (I-5/M-1) : envoi immédiat, horodaté au geste corrigé offset. */
  answer: (round: PlayRound, answerIndex: number) => void;
};

export function usePlayStateMachine(onAuthLost: () => void): PlayMachine {
  const [playState, setPlayState] = useState<PlayState | null>(null);
  const [failureCount, setFailureCount] = useState(0);
  const [sendingAnswer, setSendingAnswer] = useState(false);
  const [rejection, setRejection] = useState<RejectionNotice | null>(null);
  const [localVerdict, setLocalVerdict] = useState<LocalVerdict | null>(null);
  /**
   * Manche dont les boutons restent verrouillés après un rejet DÉFINITIF
   * (WINDOW_EXCEEDED, ALREADY_ANSWERED…) : rien à retenter, le poll fait
   * avancer l'état. Réinitialisé au changement de manche.
   */
  const [blockedRoundId, setBlockedRoundId] = useState<number | null>(null);

  // Refs : la boucle de polling et le geste ne doivent pas se réarmer à
  // chaque rendu — seules les valeurs affichées vivent en state.
  const clockRef = useRef(new ServerClock());
  const tokenRef = useRef<string | null>(null);
  const sendingRef = useRef(false);
  const localVerdictRef = useRef<LocalVerdict | null>(null);
  const blockedRoundIdRef = useRef<number | null>(null);
  const onAuthLostRef = useRef(onAuthLost);
  onAuthLostRef.current = onAuthLost;

  const handleAuthFailure = useCallback(async () => {
    // Token mort ou numéro plus vérifié : on oublie le token local et on
    // renvoie au flux d'appairage (même politique que session/attach.ts).
    await clearToken();
    onAuthLostRef.current();
  }, []);

  useEffect(() => {
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;
    let inFlight = false;
    // Transport temps réel (D-002 §4.1) : Echo/WebSocket, accélérateur de
    // latence. Null tant qu'aucune session n'est connue.
    let echo: ReturnType<typeof createEcho> | null = null;
    let channelName: string | null = null;

    const poll = async () => {
      if (cancelled || inFlight) {
        return;
      }
      if (tokenRef.current === null) {
        tokenRef.current = await getStoredToken();
        if (tokenRef.current === null) {
          // Pas de token alors qu'on est sur l'écran de jeu : état
          // incohérent — retour à l'appairage.
          onAuthLostRef.current();
          return;
        }
      }
      inFlight = true;
      const sentAt = Date.now();
      try {
        const response = await getPlayState(tokenRef.current);
        clockRef.current.recordSample(
          response.data.server_time,
          sentAt,
          Date.now(),
        );
        if (!cancelled) {
          setPlayState((previous) => {
            // Le verdict local n'a de sens que pour la manche qu'il vise :
            // il s'efface dès que le serveur rend le verdict (answered) ou
            // que la manche n'est plus la manche courante.
            if (
              localVerdictRef.current !== null &&
              (response.data.state !== 'question' ||
                response.data.round.id !== localVerdictRef.current.roundId)
            ) {
              localVerdictRef.current = null;
              setLocalVerdict(null);
            }
            // Changement de manche (ou sortie de l'état question) : le
            // verrouillage de rejet définitif et la notice associée ne
            // survivent pas à la manche qui les a produits.
            const newRoundId =
              response.data.state === 'question' ? response.data.round.id : null;
            const previousRoundId =
              previous !== null && previous.state === 'question'
                ? previous.round.id
                : null;
            if (newRoundId !== previousRoundId) {
              if (blockedRoundIdRef.current !== null) {
                blockedRoundIdRef.current = null;
                setBlockedRoundId(null);
              }
              setRejection(null);
            }
            return response.data;
          });
          setFailureCount(0);
          // Une fois session + population connues, s'abonne au canal de la
          // population (le push `round.opened` déclenchera un poll immédiat).
          ensureSubscription(response.data);
        }
      } catch (error) {
        if (cancelled) {
          return;
        }
        if (
          error instanceof ApiError &&
          (error.status === 401 || error.status === 403) &&
          error.code === undefined
        ) {
          // 401/403 SANS code métier = problème d'authentification
          // (un rejet de jeu porte toujours un code stable).
          await handleAuthFailure();
          return;
        }
        if (error instanceof ApiError && error.serverTime !== undefined) {
          // Même une erreur porte server_time (EX-16) : on en profite.
          clockRef.current.recordSample(error.serverTime, sentAt, Date.now());
        }
        setFailureCount((count) => count + 1);
      } finally {
        inFlight = false;
      }
    };

    /**
     * S'abonne au canal temps réel de la population du joueur (D-002 §4.1).
     * Idempotent : ne ré-abonne pas si le canal n'a pas changé. À la
     * réception du push `round.opened`, déclenche un poll immédiat — l'état
     * serveur reste la source de vérité (served_at/EX-20 posé côté serveur),
     * le push n'est qu'un accélérateur de latence (D-1 : le polling reste).
     */
    const ensureSubscription = (data: PlayState): void => {
      if (cancelled || data.session_id === null) {
        return;
      }
      const name = populationChannelName(data.session_id, data.population);
      if (name === channelName) {
        return;
      }
      if (echo === null) {
        echo = createEcho();
      }
      if (channelName !== null) {
        echo.leaveChannel(channelName);
      }
      echo.channel(name).listen('.round.opened', () => {
        void poll();
      });
      channelName = name;
    };

    const scheduleNext = () => {
      if (cancelled) {
        return;
      }
      timer = setTimeout(async () => {
        await poll();
        scheduleNext();
      }, DEFAULTS.pollIntervalMs);
    };

    // Premier poll immédiat, puis cadence régulière.
    poll().then(scheduleNext);

    // Premier plan uniquement (R-11) : en arrière-plan, arrêt propre ;
    // au retour, poll immédiat (la fenêtre peut s'être ouverte entre-temps).
    const subscription = AppState.addEventListener('change', (nextState) => {
      if (cancelled) {
        return;
      }
      if (nextState === 'active') {
        if (timer !== null) {
          clearTimeout(timer);
          timer = null;
        }
        poll().then(scheduleNext);
      } else if (timer !== null) {
        clearTimeout(timer);
        timer = null;
      }
    });

    return () => {
      cancelled = true;
      subscription.remove();
      if (timer !== null) {
        clearTimeout(timer);
      }
      // Nettoyage Reverb : quitter le canal puis fermer la connexion.
      if (echo !== null) {
        if (channelName !== null) {
          echo.leaveChannel(channelName);
        }
        echo.disconnect();
      }
    };
  }, [handleAuthFailure]);

  /**
   * Le geste (I-5/M-1) : un tap = buzz + réponse, envoi immédiat.
   *
   * - Horodatage AU GESTE (I-6), corrigé de l'offset serveur (EX-16) ;
   * - verrouillage local des 4 propositions dès le premier geste ;
   * - rejets non létaux (TOO_FAST, IMPOSSIBLE_TIMESTAMP, réseau) : le droit
   *   de répondre n'est PAS consommé côté serveur → on déverrouille et on
   *   laisse le joueur retenter, avec un message qui ne dramatise pas ;
   * - rejets définitifs (WINDOW_EXCEEDED, ALREADY_ANSWERED, PLAYER_LOCKED…)
   *   : boutons verrouillés, le poll suivant fait avancer l'état.
   */
  const answer = useCallback((round: PlayRound, answerIndex: number) => {
    if (sendingRef.current || localVerdictRef.current !== null) {
      return;
    }
    if (tokenRef.current === null) {
      onAuthLostRef.current();
      return;
    }
    // ⚠️ L'horodatage est capturé À L'INSTANT DU GESTE — jamais à
    // l'arrivée serveur (I-6). Corrigé de l'offset d'horloge (EX-16).
    const clientTimestamp = clockRef.current.nowMs();

    sendingRef.current = true;
    setSendingAnswer(true);
    setRejection(null);

    const sentAt = Date.now();
    const token = tokenRef.current;
    submitRoundAnswer(round.id, answerIndex, clientTimestamp, token)
      .then((response) => {
        clockRef.current.recordSample(
          response.data.server_time,
          sentAt,
          Date.now(),
        );
        // Verdict immédiat (un bit, R-5) : l'écran de feedback s'affiche
        // sans attendre le poll suivant ; le poll confirmera (answered).
        const verdict: LocalVerdict = {
          roundId: round.id,
          correct: response.data.correct,
        };
        localVerdictRef.current = verdict;
        setLocalVerdict(verdict);
      })
      .catch((error: unknown) => {
        if (!(error instanceof ApiError)) {
          setRejection('generic');
          return;
        }
        if (error.serverTime !== undefined) {
          clockRef.current.recordSample(error.serverTime, sentAt, Date.now());
        }
        switch (error.code) {
          case 'TOO_FAST':
          case 'IMPOSSIBLE_TIMESTAMP':
            // Rejets NON létaux : le droit de répondre n'est pas consommé
            // (documenté côté backend) — on déverrouille pour la retente.
            setRejection(error.code === 'TOO_FAST' ? 'tooFast' : 'generic');
            break;
          case 'WINDOW_EXCEEDED':
            // Fenêtre dépassée : rien à retenter, boutons verrouillés —
            // le poll fera avancer l'état (waiting ou locked). Message
            // sans dramatiser.
            setRejection('windowExceeded');
            blockedRoundIdRef.current = round.id;
            setBlockedRoundId(round.id);
            break;
          case undefined:
            // Pas de code stable : soit réseau coupé (status 0 — rien
            // n'est arrivé au serveur, le geste peut être retenté), soit
            // erreur de validation générique.
            setRejection(error.status === 0 ? 'network' : 'generic');
            break;
          default:
            // WINDOW_CLOSED / NOT_SERVED / PLAYER_LOCKED / ALREADY_ANSWERED
            // / SESSION_ENDED / POPULATION_MISMATCH / NOT_ATTACHED : état
            // serveur plus avancé que l'état local — boutons verrouillés,
            // le poll réconcilie.
            setRejection('generic');
            blockedRoundIdRef.current = round.id;
            setBlockedRoundId(round.id);
            break;
        }
      })
      .finally(() => {
        sendingRef.current = false;
        setSendingAnswer(false);
      });
  }, []);

  // Déverrouillage après un rejet non létal : `rejection` remis à null
  // quand la manche change (le message d'un rejet ne survit pas à la
  // manche qui le l'a produit — il est affiché tant que l'état local
  // reste `question` sur la même manche, cf. GameScreen).

  // État affiché : le verdict local prime sur l'état `question` du serveur
  // pour la manche visée (feedback immédiat après le 200 de l'answer).
  const effectiveState: PlayState | null =
    playState !== null &&
    playState.state === 'question' &&
    localVerdict !== null &&
    localVerdict.roundId === playState.round.id
      ? {
          state: 'answered',
          server_time: playState.server_time,
          population: playState.population,
          session_id: playState.session_id,
          theme: playState.theme,
          round_number: playState.round.round_number,
          correct: localVerdict.correct,
        }
      : playState;

  return {
    playState,
    effectiveState,
    connectionLost: failureCount >= DEFAULTS.pollFailureThreshold,
    sendingAnswer,
    rejection,
    blockedRoundId,
    answer,
  };
}
