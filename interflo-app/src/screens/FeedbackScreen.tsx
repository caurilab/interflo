import React from 'react';
import { Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, {
  FadeIn,
  FadeInUp,
  ZoomIn,
} from 'react-native-reanimated';
import { GradientView } from '../components/GradientView';
import { EnergyBackground } from '../components/EnergyBackground';
import { ShockwaveRings } from '../components/ShockwaveRings';
// Tokens couleur — source unique (pastille de verdict)
import colors from '../config/colors';
import { MOTION } from '../config/animations';

type FeedbackScreenProps = {
  /**
   * Verdict individuel (I-29) — un bit servi par le contrat
   * (`answered.correct` / réponse 200 de l'answer, R-5).
   */
  isCorrect: boolean;
};

/**
 * Écran de retour individuel — juste ou faux (I-29, M-4).
 *
 * JAMAIS la bonne réponse : si le client la recevait pour afficher « faux »,
 * elle serait lisible dans le trafic et le jeu serait cassé (CA-07).
 *
 * ⚠️ C'est le moment le plus délicat du produit (M-4) : la grande majorité
 * des joueurs verra « faux » à chaque tour. Le ton reste encourageant —
 * un retour sec ou humiliant fait décrocher le public.
 *
 * L'écran reste affiché tant que le serveur est en état `answered` ; la
 * machine à états bascule ensuite sur la manche suivante, le locked
 * (EX-32) ou la fin de partie (I-28) — rien à toucher ici.
 *
 * Direction D-001 :
 * - « juste » = célébration énergique : panneau en dégradé d'énergie,
 *   titre cream très gras — un moment fort, sans animation coûteuse.
 * - « faux » = encourageant et chaleureux : titre cream, carte sombre au
 *   liseré cream discret — JAMAIS grisé triste, jamais de rouge punitif.
 *
 * Animation (D-001 §3, worklets Reanimated) :
 * - « juste » : entrée du panneau en spring modéré + pastille ✓ qui pop —
 *   la célébration se sent sans rien de coûteux ;
 * - « faux » : entrée douce (fondu + montée lente) — JAMAIS de secousse
 *   punitive ni de rouge agressif (M-4, garde-fou D-001 §3).
 */
export function FeedbackScreen({ isCorrect }: FeedbackScreenProps) {
  const { t } = useTranslation();

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <EnergyBackground intensity={isCorrect ? 'celebrate' : 'calm'} />
      <View className="flex-1 items-center justify-center px-6">
        {isCorrect ? (
          /* Célébration énergique — dégradé d'énergie, entrée en spring */
          <Animated.View
            entering={FadeInUp.springify()
              .damping(MOTION.spring.soft.damping)
              .stiffness(MOTION.spring.soft.stiffness)}
            style={{ alignSelf: 'stretch' }}
          >
            <GradientView style={{ borderRadius: 28, alignSelf: 'stretch' }}>
              <View className="items-center px-8 py-12">
                {/* Pastille de verdict qui pop — glyphe décoratif, non localisé */}
                <Animated.View
                  entering={ZoomIn.delay(MOTION.cascade.badgeMs)
                    .springify()
                    .damping(MOTION.spring.pop.damping)
                    .stiffness(MOTION.spring.pop.stiffness)}
                >
                  <View className="h-20 w-20">
                    <ShockwaveRings color={colors.cream.DEFAULT} size={80} />
                    <View
                      className="h-20 w-20 items-center justify-center rounded-full bg-cream"
                      accessibilityElementsHidden
                      importantForAccessibility="no"
                    >
                      <Text className="text-4xl font-extrabold" style={{ color: colors.brand.DEFAULT }}>
                        ✓
                      </Text>
                    </View>
                  </View>
                </Animated.View>
                <Animated.View entering={FadeIn.delay(MOTION.cascade.titleMs).duration(240)}>
                  <Text className="mt-6 text-center text-4xl font-extrabold text-cream">
                    {t('feedback.correctTitle')}
                  </Text>
                </Animated.View>
                <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
                  <Text className="mt-4 text-center text-lg font-semibold leading-7 text-white">
                    {t('feedback.correctSubtitle')}
                  </Text>
                </Animated.View>
              </View>
            </GradientView>
          </Animated.View>
        ) : (
          /* Encourageant, jamais humiliant — entrée douce, liseré cream */
          <Animated.View
            entering={FadeInUp.duration(320)}
            style={{ alignSelf: 'stretch' }}
          >
            <View className="w-full items-center rounded-3xl border-2 border-cream/40 bg-surface-raised px-8 py-12">
              <Animated.View entering={ZoomIn.delay(MOTION.cascade.badgeMs).duration(260)}>
                <View
                  className="h-20 w-20 items-center justify-center rounded-full border-2 border-cream/60 bg-surface-deep"
                  accessibilityElementsHidden
                  importantForAccessibility="no"
                >
                  <Text className="text-4xl font-extrabold text-cream">↻</Text>
                </View>
              </Animated.View>
              <Animated.View entering={FadeIn.delay(MOTION.cascade.titleMs).duration(240)}>
                <Text className="mt-6 text-center text-4xl font-extrabold text-cream">
                  {t('feedback.wrongTitle')}
                </Text>
              </Animated.View>
              <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
                <Text className="mt-4 text-center text-lg leading-7 text-white/85">
                  {t('feedback.wrongSubtitle')}
                </Text>
              </Animated.View>
            </View>
          </Animated.View>
        )}

        {/* Le joueur attend la manche suivante — rien à faire, on le dit. */}
        <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs + 120).duration(240)}>
          <Text className="mt-8 text-center text-base font-semibold text-white/60">
            {t('feedback.waitingNext')}
          </Text>
        </Animated.View>
      </View>
    </SafeAreaView>
  );
}
