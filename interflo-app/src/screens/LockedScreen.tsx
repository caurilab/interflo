import React from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, { FadeIn, FadeInUp, ZoomIn } from 'react-native-reanimated';
import { MOTION } from '../config/animations';
import { EnergyBackground } from '../components/EnergyBackground';
import colors from '../config/colors';

type LockedScreenProps = {
  /** Titre du thème pour lequel le joueur est éliminé (charge play/state). */
  themeTitle: string;
  /** Détachement en cours (bouton Quitter). */
  isLeaving: boolean;
  /** Échec du dernier détachement : la session reste attachée côté serveur. */
  quitFailed: boolean;
  /** Quitter la session (DELETE sessions/attach, idempotent côté serveur). */
  onQuit: () => void;
};

/**
 * Écran « éliminé pour ce thème » — état `locked` du contrat (EX-32).
 *
 * Le joueur est verrouillé jusqu'à la fin du thème (erreur OU silence
 * dans la fenêtre, déduit côté serveur). Il RESTE spectateur : la partie
 * continue devant lui à la télévision, et l'état `finished` (I-28) lui
 * dira la fin — il ne faut ni l'humilier ni le pousser à quitter (M-4 :
 * un ton sec fait décrocher le public).
 *
 * Direction D-001 : carte sombre au liseré cream discret (comme le « faux »
 * de FeedbackScreen) — jamais de rouge punitif, jamais de grisé triste.
 *
 * TODO(produit) : ce que le spectateur verrouillé VOIT pendant la fin du
 * thème (manches restantes en lecture seule ? simple message ?) n'est pas
 * spécifié dans 06-ux-ui.md — cet écran est le point de départ minimal.
 */
export function LockedScreen({ themeTitle, isLeaving, quitFailed, onQuit }: LockedScreenProps) {
  const { t } = useTranslation();

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <EnergyBackground intensity="calm" />
      <View className="flex-1 items-center justify-center px-6">
        {/* Entrée sobre : fondu + montée douce, pastille discrète.
            Rien de punitif ni de triste (M-4) — le joueur reste spectateur. */}
        <Animated.View entering={FadeInUp.duration(320)} style={{ alignSelf: 'stretch' }}>
          <View className="w-full items-center rounded-3xl border-2 border-cream/40 bg-surface-raised px-8 py-12">
            <Animated.View entering={ZoomIn.delay(MOTION.cascade.badgeMs).duration(260)}>
              <View
                className="h-20 w-20 items-center justify-center rounded-full border-2 border-cream/60 bg-surface-deep"
                accessibilityElementsHidden
                importantForAccessibility="no"
              >
                {/* Glyphe décoratif « spectateur », non localisé */}
                <Text className="text-4xl font-extrabold text-cream">★</Text>
              </View>
            </Animated.View>
            <Animated.View entering={FadeIn.delay(MOTION.cascade.titleMs).duration(240)}>
              <Text className="mt-6 text-center text-3xl font-extrabold text-cream">
                {t('locked.title')}
              </Text>
              <Text className="mt-3 text-center text-base font-semibold text-white/70">
                {t('locked.theme', { theme: themeTitle })}
              </Text>
            </Animated.View>
            <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
              <Text className="mt-4 text-center text-lg leading-7 text-white/85">
                {t('locked.subtitle')}
              </Text>
            </Animated.View>
          </View>
        </Animated.View>

        {/* Quitter — DISCRET, jamais un appel à partir (M-4) : le joueur
            éliminé reste spectateur s'il le veut, mais garde le choix. */}
        <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
          <Pressable
            onPress={onQuit}
            disabled={isLeaving}
            accessibilityRole="button"
            className="mt-8 min-h-12 min-w-32 items-center justify-center rounded-xl border border-white/15 bg-surface-raised px-6 active:bg-surface-overlay"
          >
            {isLeaving ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text className="text-base font-semibold text-white/70">
                {t('locked.quit')}
              </Text>
            )}
          </Pressable>
          {quitFailed && (
            <Text accessibilityLiveRegion="polite" className="mt-3 text-center text-sm font-semibold text-cream">
              {t('errors.generic')}
            </Text>
          )}
        </Animated.View>
      </View>
    </SafeAreaView>
  );
}
