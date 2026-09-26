import React, { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
// Tokens couleur — source unique (jamais de valeur en dur ailleurs)
import colors from '../config/colors';
import { MOTION } from '../config/animations';

type EnergyBackgroundProps = {
  /** Intensité ambiante : calm (attente) | live (fenêtre) | celebrate (gain). */
  intensity?: keyof typeof MOTION.energy;
};

/**
 * Halo d'énergie ambiant — le fond « vivant » des écrans de jeu (D-001 §3 :
 * le produit doit être vivant, sans coût pour les téléphones d'entrée de
 * gamme).
 *
 * Deux nappes de couleur (magenta haut-gauche, orange bas-droite) rendues
 * comme de grands disques largement hors-champ, à faible opacité : leur
 * courbure se lit comme de la lumière ambiante, pas comme une forme dure.
 * Sans flou ni ombre (garde-fou D-001 §3) — uniquement `transform` (échelle
 * + translation) et `opacity`, pilotés en worklet sur le thread UI.
 *
 * `prefers-reduced-motion` : les nappes restent fixes (fond statique).
 * Décoration pure : non interactif, masqué aux lecteurs d'écran.
 */
export function EnergyBackground({ intensity = 'calm' }: EnergyBackgroundProps) {
  const reduceMotion = useReducedMotion();
  const spec = MOTION.energy[intensity];
  // Phase partagée 0 → 1 → 0 (aller-retour) : les deux nappes respirent en
  // opposition pour un mouvement organique, jamais mécanique.
  const phase = useSharedValue(0);

  useEffect(() => {
    if (reduceMotion) {
      phase.value = 0;
      return;
    }
    phase.value = withRepeat(
      withTiming(1, { duration: spec.periodMs, easing: Easing.inOut(Easing.quad) }),
      -1,
      true,
    );
  }, [phase, spec.periodMs, reduceMotion]);

  // Nappe magenta (haut-gauche) — respiration + dérive lente.
  const glowTopStyle = useAnimatedStyle(() => {
    const k = phase.value;
    return {
      transform: [
        { scale: 1 + k * 0.18 },
        { translateX: spec.driftPx * k },
        { translateY: spec.driftPx * k * 0.6 },
      ],
      opacity: spec.opacityMin + (spec.opacityMax - spec.opacityMin) * k,
    };
  });

  // Nappe orange (bas-droite) — phase opposée.
  const glowBottomStyle = useAnimatedStyle(() => {
    const k = 1 - phase.value;
    return {
      transform: [
        { scale: 1 + k * 0.14 },
        { translateX: -spec.driftPx * k },
        { translateY: -spec.driftPx * k * 0.6 },
      ],
      opacity: spec.opacityMin + (spec.opacityMax - spec.opacityMin) * k,
    };
  });

  return (
    <View
      style={StyleSheet.absoluteFill}
      pointerEvents="none"
      accessibilityElementsHidden
      importantForAccessibility="no"
    >
      <Animated.View style={[styles.glow, styles.glowTop, glowTopStyle]} />
      <Animated.View style={[styles.glow, styles.glowBottom, glowBottomStyle]} />
    </View>
  );
}

const styles = StyleSheet.create({
  glow: {
    position: 'absolute',
    width: 420,
    height: 420,
    borderRadius: 210,
  },
  glowTop: {
    top: -160,
    left: -140,
    backgroundColor: colors.brand.DEFAULT,
  },
  glowBottom: {
    bottom: -180,
    right: -160,
    backgroundColor: colors.energy.from,
  },
});
