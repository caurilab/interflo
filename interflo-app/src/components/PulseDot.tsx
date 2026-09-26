import React, { useEffect } from 'react';
import Animated, {
  Easing,
  interpolate,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
// Tokens couleur — source unique (jamais de valeur en dur)
import colors from '../config/colors';
import { MOTION } from '../config/animations';

type PulseDotProps = {
  /** Diamètre du point au repos, px. */
  size?: number;
  /** Couleur du point — par défaut le magenta de marque. */
  color?: string;
  /**
   * slow : indicateur d'attente (calme). fast : fenêtre ouverte (vive).
   * celebration : respiration mesurée de la pastille gagnante.
   */
  variant?: keyof typeof MOTION.pulse;
};

/**
 * Point lumineux à pulse ambiant — la « vie » discrète des écrans
 * (D-001 §3 : le produit doit être vivant).
 *
 * Animation en worklet Reanimated (thread UI) sur `transform` + `opacity`
 * uniquement : aucun layout, aucun flou, aucune ombre — coût nul sur
 * téléphone d'entrée de gamme. Boucle infinie en aller-retour, amplitude
 * faible. `prefers-reduced-motion` (réglage système) : le point reste fixe.
 */
export function PulseDot({
  size = 10,
  color = colors.brand.DEFAULT,
  variant = 'slow',
}: PulseDotProps) {
  const reduceMotion = useReducedMotion();
  const progress = useSharedValue(1);
  const spec = MOTION.pulse[variant];

  useEffect(() => {
    if (reduceMotion) {
      progress.value = 1;
      return;
    }
    progress.value = withRepeat(
      withTiming(spec.scaleMin, {
        duration: spec.periodMs / 2,
        easing: Easing.inOut(Easing.quad),
      }),
      -1,
      true,
    );
  }, [progress, spec, reduceMotion]);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: progress.value }],
    opacity: interpolate(
      progress.value,
      [spec.scaleMin, 1],
      [spec.opacityMin, 1],
    ),
  }));

  return (
    <Animated.View
      // Glyphe purement décoratif — le statut est déjà porté par le texte.
      accessibilityElementsHidden
      importantForAccessibility="no"
      style={[
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
        },
        animatedStyle,
      ]}
    />
  );
}
