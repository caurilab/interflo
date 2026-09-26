import React from 'react';
import { Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, { FadeIn, FadeInDown } from 'react-native-reanimated';
import type { PlayRound } from '../api/types';
import type { RejectionNotice } from '../game/usePlayStateMachine';
import { SEALED } from '../config/gameConfig';
import { MOTION } from '../config/animations';
import { PropositionButton } from '../components/PropositionButton';
import { PulseDot } from '../components/PulseDot';
import { EnergyBackground } from '../components/EnergyBackground';

type GameScreenProps = {
  /** Titre du thème en cours (charge utile play/state). */
  themeTitle: string;
  /** Manche servie : question + 4 propositions (jamais correct_index, INV-2). */
  round: PlayRound;
  /**
   * Boutons verrouillés : réponse en cours d'envoi ou rejet définitif.
   * Dès le premier geste, les 4 propositions se ferment (M-1).
   */
  disabled: boolean;
  /** Rejet codé à traduire en message i18n — jamais le code brut. */
  rejection: RejectionNotice | null;
  /** Le geste : index de la proposition tapée (0..3, I-4). */
  onAnswer: (answerIndex: number) => void;
};

/**
 * Écran de jeu — la fenêtre est ouverte (état `question` du contrat).
 *
 * 4 propositions (I-4, scellé), chacune EST un buzzer (I-5, M-1) : un seul
 * geste vaut buzz ET réponse, envoyé immédiatement avec l'horodatage du
 * geste (I-6). Pas de confirmation, pas de retour en arrière — un geste
 * accidentel est une réponse : les zones sont très hautes et espacées
 * (06-ux-ui.md §2.1).
 *
 * Pendant la fenêtre : la question, 4 propositions, et RIEN d'autre
 * (06-ux-ui.md §2.3) — la bannière « fenêtre ouverte » est l'affordance
 * de la fenêtre elle-même (I-3).
 *
 * ⚠️ Pas de compte à rebours local de la fenêtre : served_at/window_seconds
 * sont transmis par le contrat mais un décompte affiché sur une horloge
 * appareil non fiable induirait le joueur en erreur — le serveur borne
 * la fenêtre (EX-20). Choix documenté, à arbitrer PO.
 */
export function GameScreen({
  themeTitle,
  round,
  disabled,
  rejection,
  onAnswer,
}: GameScreenProps) {
  const { t } = useTranslation();

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <EnergyBackground intensity="live" />
      <View className="flex-1 px-5 py-6">
        {/* Bannière fenêtre ouverte — point magenta pulsant, entrée animée
            (l'ouverture de la fenêtre doit se SENTIR, D-001 §3) */}
        <Animated.View
          entering={FadeInDown.springify()
            .damping(MOTION.spring.soft.damping)
            .stiffness(MOTION.spring.soft.stiffness)}
          style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }}
        >
          <PulseDot size={10} variant="fast" />
          <Text className="text-sm font-extrabold uppercase tracking-widest text-cream">
            {t('game.windowOpen')}
          </Text>
        </Animated.View>

        {/* Thème + numéro de manche (5 manches, I-27 scellé) */}
        <Animated.View entering={FadeIn.delay(MOTION.stagger.baseMs / 2).duration(220)}>
          <Text className="mt-3 text-center text-sm font-semibold text-white/60">
            {t('game.roundLabel', {
              theme: themeTitle,
              round: round.round_number,
              total: SEALED.eliminationRoundCount,
            })}
          </Text>
        </Animated.View>

        {/* La question servie par le serveur (effet de bord du poll, EX-20) */}
        <Animated.View entering={FadeIn.delay(MOTION.stagger.baseMs / 2).duration(260)}>
          <Text className="mt-4 text-center text-2xl font-extrabold text-white">
            {round.question.body}
          </Text>
        </Animated.View>

        {/* 4 propositions (I-4), zones larges et espacées (M-1), entrée en
            cascade légère (stagger ~60 ms — D-001 §3). Ne rejoue qu'au
            montage de la manche : PlayScreen keye l'écran sur round.id. */}
        <View className="mt-8 flex-1 justify-center gap-5">
          {round.question.propositions
            .slice(0, SEALED.propositionCount)
            .map((label, index) => (
              <PropositionButton
                key={index}
                letter={String.fromCharCode(65 + index)}
                label={label}
                disabled={disabled}
                enterDelayMs={MOTION.stagger.baseMs + index * MOTION.stagger.stepMs}
                onPress={() => onAnswer(index)}
              />
            ))}
        </View>

        {/* Rejet codé de la réponse — message qui ne dramatise pas (M-4).
            La zone garde une hauteur minimale pour ne pas faire sauter
            les propositions au moment du geste. */}
        <View className="min-h-8 items-center justify-center">
          {rejection !== null && (
            <Animated.View entering={FadeIn.duration(180)}>
              <Text
                accessibilityLiveRegion="polite"
                className="text-center text-base font-semibold text-cream"
              >
                {t(`game.rejection.${rejection}`)}
              </Text>
            </Animated.View>
          )}
        </View>
      </View>
    </SafeAreaView>
  );
}
