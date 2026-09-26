import React, { useEffect, useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withDelay,
  withTiming,
} from 'react-native-reanimated';
// Tokens couleur — source unique (jamais de valeur en dur ailleurs)
import colors from '../config/colors';
import { MOTION } from '../config/animations';

type ConfettiBurstProps = {
  /** Nombre de confettis (défaut : MOTION.confetti.particleCount). */
  count?: number;
};

type Particle = {
  id: number;
  color: string;
  size: number;
  dx: number;
  dy: number;
  rotation: number;
  delay: number;
  duration: number;
  round: boolean;
};

// Palette de fête dérivée des tokens D-001 (magenta, orange, cream, blanc).
const PALETTE = [
  colors.brand.DEFAULT,
  colors.brand.light,
  colors.energy.from,
  colors.cream.DEFAULT,
  colors.white,
];

/** Tirage uniforme dans [min, max]. */
function randomBetween(min: number, max: number): number {
  return min + Math.random() * (max - min);
}

/**
 * Confettis de célébration — un burst de particules colorées qui s'éparpillent
 * depuis le centre de leur conteneur en tombant, tournant et s'estompant
 * (D-001 §3 : le produit est vivant, moments forts = fête).
 *
 * Garde-fous respectés : uniquement `transform` (translate + rotate) et
 * `opacity` en worklet thread UI, aucune ombre ni flou (entrée de gamme),
 * coup unique au montage, `prefers-reduced-motion` → rien n'est rendu.
 * Décoration pure : non interactif, masqué aux lecteurs d'écran.
 */
export function ConfettiBurst({ count = MOTION.confetti.particleCount }: ConfettiBurstProps) {
  const reduceMotion = useReducedMotion();

  // Particules tirées une seule fois au montage (trajectoires aléatoires).
  const particles = useMemo<Particle[]>(() => {
    const c = MOTION.confetti;
    return Array.from({ length: count }, (_, id) => ({
      id,
      color: PALETTE[id % PALETTE.length],
      size: randomBetween(c.minSize, c.maxSize),
      // Biais vers le bas (gravité) : dy majoritairement positif.
      dx: randomBetween(-c.spread, c.spread),
      dy: randomBetween(c.fall * 0.2, c.fall),
      rotation: randomBetween(-c.maxRotationDeg, c.maxRotationDeg),
      delay: randomBetween(c.minDelayMs, c.maxDelayMs),
      duration: randomBetween(c.minDurationMs, c.maxDurationMs),
      round: Math.random() < 0.4,
    }));
  }, [count]);

  if (reduceMotion) {
    return null;
  }

  return (
    <View
      pointerEvents="none"
      accessibilityElementsHidden
      importantForAccessibility="no"
      style={StyleSheet.absoluteFill}
    >
      {particles.map((particle) => (
        <ConfettiParticle key={particle.id} particle={particle} />
      ))}
    </View>
  );
}

function ConfettiParticle({ particle }: { particle: Particle }) {
  const progress = useSharedValue(0);

  useEffect(() => {
    progress.value = withDelay(
      particle.delay,
      withTiming(1, { duration: particle.duration, easing: Easing.out(Easing.quad) }),
    );
  }, [particle.delay, particle.duration, progress]);

  const animatedStyle = useAnimatedStyle(() => {
    const t = progress.value;
    return {
      transform: [
        { translateX: particle.dx * t },
        { translateY: particle.dy * t },
        { rotate: `${particle.rotation * t}deg` },
      ],
      // Estompe en fin de course (quadratique : net au début, doux à la fin).
      opacity: MOTION.confetti.maxOpacity * (1 - t * t),
    };
  });

  return (
    <Animated.View
      style={[
        {
          position: 'absolute',
          // Ancré au centre du conteneur, puis décalé de sa propre taille.
          left: '50%',
          top: '50%',
          width: particle.size,
          height: particle.size * 1.5,
          marginLeft: -particle.size / 2,
          marginTop: -(particle.size * 1.5) / 2,
          borderRadius: particle.round ? particle.size / 2 : 2,
          backgroundColor: particle.color,
        },
        animatedStyle,
      ]}
    />
  );
}
