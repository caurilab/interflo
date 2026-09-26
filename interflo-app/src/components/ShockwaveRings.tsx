import React, { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withDelay,
  withTiming,
} from 'react-native-reanimated';
import { MOTION } from '../config/animations';

type ShockwaveRingsProps = {
  /** Couleur des anneaux (cream sur les pastilles de verdict/gagnant). */
  color: string;
  /** Diamètre de la pastille autour de laquelle les anneaux s'étendent. */
  size?: number;
};

/**
 * Ondes de choc de célébration — anneaux concentriques qui s'étendent puis
 * s'estompent derrière une pastille de verdict (✓) ou de gagnant (★).
 *
 * Coup unique au montage (pas de boucle) : la fête du moment, sans coût
 * continu. Garde-fous D-001 §3 respectés — uniquement `transform` (scale) et
 * `opacity` en worklet thread UI, aucun flou ni ombre. `prefers-reduced-motion`
 * : rien n'est rendu (les nappes et anneaux sont purement décoratifs).
 */
export function ShockwaveRings({ color, size = 80 }: ShockwaveRingsProps) {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) {
    return null;
  }

  return (
    <View
      pointerEvents="none"
      accessibilityElementsHidden
      importantForAccessibility="no"
      style={[StyleSheet.absoluteFill, { alignItems: 'center', justifyContent: 'center' }]}
    >
      {Array.from({ length: MOTION.shockwave.ringCount }, (_, index) => (
        <Ring key={index} index={index} color={color} size={size} />
      ))}
    </View>
  );
}

function Ring({
  index,
  color,
  size,
}: {
  index: number;
  color: string;
  size: number;
}) {
  const progress = useSharedValue(0);
  const { durationMs, staggerMs, maxScale, maxOpacity } = MOTION.shockwave;

  useEffect(() => {
    progress.value = withDelay(
      index * staggerMs,
      withTiming(1, { duration: durationMs, easing: Easing.out(Easing.quad) }),
    );
  }, [index, progress, durationMs, staggerMs]);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: 1 + progress.value * (maxScale - 1) }],
    opacity: (1 - progress.value) * maxOpacity,
  }));

  return (
    <Animated.View
      style={[
        {
          position: 'absolute',
          width: size,
          height: size,
          borderRadius: size / 2,
          borderWidth: 3,
          borderColor: color,
        },
        animatedStyle,
      ]}
    />
  );
}
