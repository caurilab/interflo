import React from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, { FadeIn, FadeInUp } from 'react-native-reanimated';
import { BrandLogo } from '../components/BrandLogo';
import { PulseDot } from '../components/PulseDot';
// Tokens couleur — source unique (spinner)
import colors from '../config/colors';

type WaitingScreenProps = {
  /** Nom de la chaîne (tenant.name du pointeur) — affichage uniquement. */
  tenantName: string;
  /** Titre de l'émission (emission.title du pointeur) — affichage uniquement. */
  emissionTitle: string;
  /**
   * idle : aucune partie n'a encore commencé (pas de thème).
   * betweenRounds : entre deux manches d'un thème en cours (I-2).
   */
  mode: 'idle' | 'betweenRounds';
  /** Numéro de la dernière manche (état waiting), null si inconnue. */
  roundNumber: number | null;
  /** Polling en échec répété : on le dit discrètement, sans bloquer. */
  connectionLost: boolean;
  /** Détachement en cours (bouton Quitter). */
  isLeaving: boolean;
  /** Échec du dernier détachement : la session reste attachée côté serveur. */
  quitFailed: boolean;
  /** Quitter la session (DELETE sessions/attach, idempotent côté serveur). */
  onQuit: () => void;
};

/**
 * Écran d'attente — couvre les états `idle` et `waiting` du contrat.
 * Hors fenêtre, l'application est INERTE (I-2, M-2).
 *
 * Pas de compte à rebours local : l'ouverture d'une fenêtre est signifiée
 * PAR L'APPLICATION (I-3, M-3) — ici par la machine à états qui bascule
 * sur GameScreen dès que le poll sert une question (transport PROVISOIRE,
 * en attente de l'ADR temps réel) — jamais par l'écran de télévision.
 *
 * Seule action possible : quitter la session (I-16, I-17).
 *
 * Animation (D-001 §3) : entrée d'écran douce au montage, pulse lent sur
 * le point de la pastille de session — la vie ambiante. ⚠️ Le polling 2 s
 * re-rend cet écran à chaque réponse : les animations ne rejouent qu'au
 * montage (PlayScreen keye l'état stable `waiting`, aucun remount au poll)
 * et le pulse vit en worklet sur le thread UI — zéro interférence.
 */
export function WaitingScreen({
  tenantName,
  emissionTitle,
  mode,
  roundNumber,
  connectionLost,
  isLeaving,
  quitFailed,
  onQuit,
}: WaitingScreenProps) {
  const { t } = useTranslation();

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <View className="flex-1 px-8">
        <Animated.View entering={FadeIn.duration(220)} style={{ alignItems: 'center', paddingTop: 24 }}>
          <BrandLogo />
        </Animated.View>
        <Animated.View
          entering={FadeInUp.duration(320)}
          style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}
        >
          {/* Session courante — pastille au liseré magenta, point pulsant
              (l'attente est vivante, sans être agitée) */}
          <View className="flex-row items-center gap-2 rounded-full border border-brand/60 bg-surface-raised px-5 py-2">
            <PulseDot size={8} variant="slow" />
            <Text className="text-center text-sm font-semibold text-cream">
              {t('waiting.currentSession', { tenant: tenantName, emission: emissionTitle })}
            </Text>
          </View>
          <Text className="mt-8 text-center text-3xl font-extrabold text-white">
            {t('waiting.title')}
          </Text>
          <Text className="mt-4 text-center text-base leading-6 text-white/75">
            {mode === 'betweenRounds'
              ? roundNumber !== null
                ? t('waiting.betweenRounds', { round: roundNumber })
                : t('waiting.betweenRoundsNoNumber')
              : t('waiting.idleSubtitle')}
          </Text>

          {connectionLost && (
            <Animated.View entering={FadeIn.duration(200)}>
              <Text accessibilityLiveRegion="polite" className="mt-6 text-center text-sm font-semibold text-cream-dim">
                {t('waiting.connectionLost')}
              </Text>
            </Animated.View>
          )}

          <Pressable
            onPress={onQuit}
            disabled={isLeaving}
            accessibilityRole="button"
            className="mt-10 min-h-14 min-w-44 items-center justify-center rounded-xl border border-white/15 bg-surface-raised px-6 active:bg-surface-overlay"
          >
            {isLeaving ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text className="text-lg font-bold text-white/80">
                {t('waiting.quit')}
              </Text>
            )}
          </Pressable>

          {quitFailed && (
            <Text accessibilityLiveRegion="polite" className="mt-4 text-center text-base font-semibold text-cream">
              {t('errors.generic')}
            </Text>
          )}
        </Animated.View>
      </View>
    </SafeAreaView>
  );
}
