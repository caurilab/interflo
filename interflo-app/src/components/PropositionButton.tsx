import React from 'react';
import { Pressable, Text, View } from 'react-native';
import Animated, {
  FadeInUp,
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';
import { GradientView } from './GradientView';
// Tokens couleur — source unique (badge lettre, états)
import colors from '../config/colors';
import { MOTION } from '../config/animations';

type PropositionButtonProps = {
  /** Libellé de la proposition (placeholder tant que le contrat d'API n'existe pas). */
  label: string;
  /** Lettre du badge (A, B, C, D — I-4 : 4 propositions scellées). */
  letter: string;
  /** Geste unique : buzz + réponse (I-5). */
  onPress: () => void;
  /** Verrouillé après le premier geste — pas de seconde chance, pas de retour en arrière (M-1). */
  disabled: boolean;
  /**
   * Cascade d'entrée à l'ouverture de la manche (D-001 §3) : délai avant
   * l'animation d'arrivée. Ne rejoue qu'au montage — un re-rendu de poll
   * ne relance rien (l'état stable n'est pas animé).
   */
  enterDelayMs?: number;
};

/**
 * Une proposition de réponse — elle EST le buzzer (I-5, M-1).
 *
 * Style gaming D-001 : liseré lumineux en dégradé d'énergie autour d'une
 * carte sombre, badge lettre magenta/cream, état pressé franc (fond magenta
 * profond immédiat, sans animation).
 *
 * ⚠️ Un geste accidentel est une réponse (M-1) : la zone reste très haute
 * (min-h-24, intangible) et les zones sont espacées par le parent
 * (06-ux-ui.md §2.1). Le liseré ne rogne jamais la zone tactile — il est
 * posé EN DESSOUS du contenu, pas autour d'une zone réduite.
 * Aucune confirmation, aucun retour en arrière.
 *
 * Animation (D-001 §3) : entrée en cascade au montage de la manche
 * (`enterDelayMs`), feedback pressé en spring quasi instantané
 * (scale 0.97, worklet thread UI). Le handler `onPress` tire au geste,
 * indépendamment de l'animation — elle ne retarde jamais la réponse (M-1)
 * et reste interruptible à tout moment.
 */
export function PropositionButton({
  label,
  letter,
  onPress,
  disabled,
  enterDelayMs = 0,
}: PropositionButtonProps) {
  const scale = useSharedValue(1);
  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Animated.View
      entering={FadeInUp.delay(enterDelayMs)
        .springify()
        .damping(MOTION.spring.soft.damping)
        .stiffness(MOTION.spring.soft.stiffness)
        .mass(MOTION.spring.soft.mass)}
      style={animatedStyle}
    >
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={label}
        disabled={disabled}
        onPress={onPress}
        // Feedback du geste : spring serré = contact immédiat, pas de délai.
        onPressIn={() => {
          scale.value = withSpring(MOTION.press.scale, MOTION.spring.press);
        }}
        onPressOut={() => {
          scale.value = withSpring(1, MOTION.spring.press);
        }}
        className="active:opacity-90"
      >
        {({ pressed }) => (
        <GradientView
          // Actif : liseré énergie orange → magenta. Pressé : magenta franc.
          // Verrouillé : magenta profond très atténué — la zone reste visible
          // (le joueur voit ce qu'il a touché) mais ne rappelle plus le geste.
          from={
            disabled
              ? colors.brand.dark
              : pressed
                ? colors.brand.DEFAULT
                : colors.energy.from
          }
          to={
            disabled
              ? colors.brand.dark
              : pressed
                ? colors.brand.DEFAULT
                : colors.energy.to
          }
          style={{ borderRadius: 20, opacity: disabled ? 0.35 : 1 }}
          contentStyle={{ padding: 2 }}
        >
          <View
            // Corps sombre de la carte ; pressé : teinte magenta profonde.
            className={`min-h-24 flex-row items-center rounded-[18px] px-5 ${
              pressed && !disabled ? 'bg-brand-dark' : 'bg-surface-raised'
            }`}
            style={{ gap: 16 }}
          >
            {/* Badge lettre — repère gaming, cream sur magenta */}
            <View
              className="h-11 w-11 items-center justify-center rounded-full bg-brand"
            >
              <Text className="text-lg font-extrabold text-cream">{letter}</Text>
            </View>
            <Text
              className={`flex-1 text-xl font-bold ${
                disabled ? 'text-white/50' : 'text-white'
              }`}
            >
              {label}
            </Text>
          </View>
        </GradientView>
      )}
      </Pressable>
    </Animated.View>
  );
}
