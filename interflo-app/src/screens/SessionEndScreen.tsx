import React, { useEffect } from 'react';
import { Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import Animated, {
  Easing,
  FadeIn,
  FadeInUp,
  ZoomIn,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { GradientView } from '../components/GradientView';
import { EnergyBackground } from '../components/EnergyBackground';
import { ShockwaveRings } from '../components/ShockwaveRings';
import { MOTION } from '../config/animations';
// Tokens couleur — source unique (anneaux de célébration)
import colors from '../config/colors';

type SessionEndScreenProps = {
  /** Bit winner personnel (I-28) — SON bit, jamais la liste des gagnants. */
  winner: boolean;
  /** Titre du thème terminé (charge play/state). */
  themeTitle: string;
};

/**
 * Écran de fin de partie — état `finished` du contrat (I-28).
 *
 * ⚠️⚠️ TODO(produit) : ce que voit le joueur en fin de thème au-delà du
 * bit winner N'EST PAS SPÉCIFIÉ (06-ux-ui.md §2.3 et §6 ; cadrage §16
 * q17) : classement ? gain ? prochain rendez-vous ? simple remerciement ?
 * Cet écran est un placeholder qui consomme le bit winner du contrat,
 * à ne PAS faire évoluer sans arbitrage du PO.
 *
 * Direction D-001 : gagnant = dégradé d'énergie (moment fort) ; non
 * gagnant = carte sombre chaleureuse, jamais humiliante (M-4).
 *
 * Animation (D-001 §3, amendement PO — tout est vivant, worklets
 * Reanimated uniquement) :
 * - gagnant : moment de fête mesuré — panneau en spring, cascade
 *   pastille → titre → sous-titre, respiration lente de la pastille ★
 *   (amplitude faible, entrée de gamme) ;
 * - non gagnant : entrée sobre en fondu montant — chaleureux, jamais sec.
 * Le gel produit porte sur le CONTENU (placeholder) : ces animations ne
 * changent ni les textes ni la structure d'information.
 */
export function SessionEndScreen({ winner, themeTitle }: SessionEndScreenProps) {
  const { t } = useTranslation();

  // Respiration de la pastille gagnante — boucle lente, amplitude faible.
  const reduceMotion = useReducedMotion();
  const breath = useSharedValue(1);
  const celebration = MOTION.pulse.celebration;
  useEffect(() => {
    if (!winner || reduceMotion) {
      breath.value = 1;
      return;
    }
    breath.value = withRepeat(
      withTiming(celebration.scaleMin, {
        duration: celebration.periodMs / 2,
        easing: Easing.inOut(Easing.quad),
      }),
      -1,
      true,
    );
  }, [breath, celebration, winner, reduceMotion]);
  const breathStyle = useAnimatedStyle(() => ({
    transform: [{ scale: breath.value }],
    opacity: 1 - (1 - breath.value) * ((1 - celebration.opacityMin) / (1 - celebration.scaleMin)),
  }));

  return (
    <SafeAreaView className="flex-1 bg-surface">
      <EnergyBackground intensity={winner ? 'celebrate' : 'calm'} />
      <View className="flex-1 items-center justify-center px-6">
        {winner ? (
          /* Gagnant — moment de fête : spring + cascade + respiration */
          <Animated.View
            entering={FadeInUp.springify()
              .damping(MOTION.spring.soft.damping)
              .stiffness(MOTION.spring.soft.stiffness)}
            style={{ alignSelf: 'stretch' }}
          >
            <GradientView style={{ borderRadius: 28, alignSelf: 'stretch' }}>
              <View className="items-center px-8 py-12">
                <Animated.View
                  entering={ZoomIn.delay(MOTION.cascade.badgeMs)
                    .springify()
                    .damping(MOTION.spring.pop.damping)
                    .stiffness(MOTION.spring.pop.stiffness)}
                >
                  <Animated.View style={breathStyle}>
                    <View className="h-20 w-20">
                      <ShockwaveRings color={colors.cream.DEFAULT} size={80} />
                      <View
                        className="h-20 w-20 items-center justify-center rounded-full bg-cream"
                        accessibilityElementsHidden
                        importantForAccessibility="no"
                      >
                        <Text className="text-4xl font-extrabold text-brand">★</Text>
                      </View>
                    </View>
                  </Animated.View>
                </Animated.View>
                <Animated.View entering={FadeIn.delay(MOTION.cascade.titleMs).duration(240)}>
                  <Text className="mt-6 text-center text-4xl font-extrabold text-cream">
                    {t('finished.winnerTitle')}
                  </Text>
                </Animated.View>
                <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
                  <Text className="mt-4 text-center text-lg font-semibold leading-7 text-white">
                    {t('finished.winnerSubtitle', { theme: themeTitle })}
                  </Text>
                </Animated.View>
              </View>
            </GradientView>
          </Animated.View>
        ) : (
          /* Non gagnant — entrée sobre, chaleureux, jamais sec (M-4) */
          <Animated.View entering={FadeInUp.duration(320)} style={{ alignSelf: 'stretch' }}>
            <View className="w-full items-center rounded-3xl border-2 border-cream/40 bg-surface-raised px-8 py-12">
              <Animated.View entering={FadeIn.delay(MOTION.cascade.titleMs).duration(240)}>
                <Text className="text-center text-3xl font-extrabold text-cream">
                  {t('finished.loserTitle')}
                </Text>
              </Animated.View>
              <Animated.View entering={FadeIn.delay(MOTION.cascade.subtitleMs).duration(240)}>
                <Text className="mt-4 text-center text-lg leading-7 text-white/85">
                  {t('finished.loserSubtitle', { theme: themeTitle })}
                </Text>
              </Animated.View>
            </View>
          </Animated.View>
        )}
        {/* TODO(produit) : classement final ? prochain rendez-vous ?
            À trancher par le PO (cadrage §16 q17). */}
      </View>
    </SafeAreaView>
  );
}
