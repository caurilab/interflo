import React, { useCallback, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import Animated, { FadeIn, FadeOut } from 'react-native-reanimated';
import type { RootStackParamList } from '../navigation/types';
import { detachSession } from '../api/endpoints';
import { getStoredToken } from '../storage/authToken';
import { usePlayStateMachine } from '../game/usePlayStateMachine';
import { MOTION } from '../config/animations';
import colors from '../config/colors';
import { WaitingScreen } from './WaitingScreen';
import { GameScreen } from './GameScreen';
import { FeedbackScreen } from './FeedbackScreen';
import { LockedScreen } from './LockedScreen';
import { SessionEndScreen } from './SessionEndScreen';

type Props = NativeStackScreenProps<RootStackParamList, 'Play'>;

/**
 * Conteneur du jeu élimination — une session attachée, une machine à états.
 *
 * Les transitions ne sont PAS de la navigation : l'état serveur
 * (`play/state`, polling PROVISOIRE en attente de l'ADR temps réel) pilote
 * directement la vue rendue — idle/waiting → WaitingScreen, question →
 * GameScreen, answered → FeedbackScreen, locked (EX-32) → LockedScreen,
 * finished (I-28) → SessionEndScreen. Pas de race de navigation possible.
 *
 * Chaque bascule d'état est animée (D-001 §3) : fondu croisé entre la vue
 * sortante et l'entrante, keyé sur l'identité de l'état — jamais relancé
 * par un poll qui ne change rien.
 *
 * Hors fenêtre, l'application est inerte pour le joueur (I-2/M-2) : seule
 * action hors fenêtre = quitter la session.
 */
export function PlayScreen({ navigation, route }: Props) {
  // sessionId n'est pas utile à la machine : le serveur déduit la session
  // de l'attachement du joueur (unicité I-17). Il reste dans les params
  // de route pour les futurs besoins (ex. re-attach).
  const { tenantName, emissionTitle } = route.params;
  const [isLeaving, setIsLeaving] = useState(false);
  const [quitFailed, setQuitFailed] = useState(false);

  // Token mort ou incohérence d'état : retour à l'appairage (le token
  // local a déjà été oublié par la machine).
  const handleAuthLost = useCallback(() => {
    navigation.popToTop();
  }, [navigation]);

  const machine = usePlayStateMachine(handleAuthLost);

  const handleQuit = async () => {
    setIsLeaving(true);
    setQuitFailed(false);
    try {
      const token = await getStoredToken();
      // Sans token local, rien à détacher côté serveur : sortie directe.
      if (token) {
        await detachSession(token);
      }
      navigation.popToTop();
    } catch {
      // Le détachement échoué laisse la session attachée côté serveur
      // (unicité I-17) : on reste sur l'écran et on le dit au joueur.
      setQuitFailed(true);
      setIsLeaving(false);
    }
  };

  const state = machine.effectiveState;

  // — Sélection de la vue et de sa clé de transition —
  // La clé identifie l'ÉTAT affiché, pas le poll : un re-rendu de polling
  // sans changement d'état garde la même clé → aucun remount, aucune
  // animation relancée (on anime les TRANSITIONS, pas les états stables).
  let screenKey = 'waiting';
  let content: React.ReactNode;

  // Avant le premier poll : vue d'attente (l'app vient d'attacher la
  // session, le premier état arrive à la cadence du polling).
  if (state === null || state.state === 'idle' || state.state === 'waiting') {
    content = (
      <WaitingScreen
        tenantName={tenantName}
        emissionTitle={emissionTitle}
        mode={state !== null && state.state === 'waiting' ? 'betweenRounds' : 'idle'}
        roundNumber={state !== null && state.state === 'waiting' ? state.round_number : null}
        connectionLost={machine.connectionLost}
        quitFailed={quitFailed}
        isLeaving={isLeaving}
        onQuit={handleQuit}
      />
    );
  } else if (state.state === 'question') {
    // Nouvelle manche = nouvelle clé : l'arrivée d'une question rejoue
    // l'entrée (cross-fade + cascade des propositions) — elle se sent.
    screenKey = `question-${state.round.id}`;
    content = (
      <GameScreen
        themeTitle={state.theme.title}
        round={state.round}
        disabled={
          machine.sendingAnswer || machine.blockedRoundId === state.round.id
        }
        rejection={machine.rejection}
        onAnswer={(index) => machine.answer(state.round, index)}
      />
    );
  } else if (state.state === 'answered') {
    // Verdict individuel juste/faux (I-29/M-4) — jamais la bonne réponse.
    screenKey = `answered-${state.round_number}`;
    content = <FeedbackScreen isCorrect={state.correct} />;
  } else if (state.state === 'locked') {
    // Éliminé pour ce thème (EX-32) — spectateur, ton encourageant (M-4).
    // Quitter reste possible, mais discret : jamais un appel à partir.
    screenKey = 'locked';
    content = (
      <LockedScreen
        themeTitle={state.theme.title}
        isLeaving={isLeaving}
        quitFailed={quitFailed}
        onQuit={handleQuit}
      />
    );
  } else {
    // finished (I-28) : SON bit winner, pas la liste des gagnants.
    screenKey = 'finished';
    content = (
      <SessionEndScreen
        winner={state.winner}
        themeTitle={state.theme.title}
        isLeaving={isLeaving}
        quitFailed={quitFailed}
        onQuit={handleQuit}
      />
    );
  }

  // Fondu croisé entre les états (D-001 §3) : l'écran sortant s'efface en
  // 140 ms pendant que l'entrant apparaît en 260 ms — tous deux en plein
  // écran absolu, aucun saut de layout, animation interruptible à tout
  // moment. Worklets Reanimated : le thread JS reste libre pour le poll.
  return (
    <View style={[styles.container, { backgroundColor: colors.surface.DEFAULT }]}>
      <Animated.View
        key={screenKey}
        entering={FadeIn.duration(MOTION.screen.enterMs)}
        exiting={FadeOut.duration(MOTION.screen.exitMs)}
        style={StyleSheet.absoluteFill}
      >
        {content}
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
});
