import React from 'react';
import { StyleSheet, View } from 'react-native';
import type { StyleProp, ViewStyle } from 'react-native';
// Tokens couleur — source unique (jamais de valeur en dur ailleurs)
import colors from '../config/colors';

/**
 * Dégradé horizontal en superposition de vues — compromis documenté.
 *
 * La direction D-001 veut un dégradé d'énergie (#d96034 → #d6007d) sur les
 * boutons de jeu et les moments forts. Un vrai dégradé exigerait une
 * dépendance native (react-native-linear-gradient ⇒ pod install + rebuild).
 * Pour rester sans dépendance native, le dégradé est rendu par `steps`
 * bandes verticales de couleurs interpolées, posées sous le contenu.
 *
 * Coût : `steps` vues statiques, aucune animation, aucun flou — compatible
 * avec les téléphones d'entrée de gamme (D-001 §3).
 * Limite : à 24 bandes la transition est visuellement lisse ; sur un très
 * grand élément une légère striation peut se deviner de près. Si le PO
 * veut un dégradé parfait, on ajoutera la dépendance native avec rebuild.
 */

/** Nombre de bandes du dégradé — compromis lissage / nombre de vues. */
const DEFAULT_STEPS = 24;

/** Interpole deux couleurs hex (#rrggbb) — t ∈ [0, 1]. */
function mixHex(from: string, to: string, t: number): string {
  const parse = (hex: string) => [
    parseInt(hex.slice(1, 3), 16),
    parseInt(hex.slice(3, 5), 16),
    parseInt(hex.slice(5, 7), 16),
  ];
  const [r1, g1, b1] = parse(from);
  const [r2, g2, b2] = parse(to);
  const channel = (a: number, b: number) =>
    Math.round(a + (b - a) * t)
      .toString(16)
      .padStart(2, '0');
  return `#${channel(r1, r2)}${channel(g1, g2)}${channel(b1, b2)}`;
}

type GradientViewProps = {
  children?: React.ReactNode;
  /** Couleurs du dégradé — par défaut le dégradé d'énergie D-001. */
  from?: string;
  to?: string;
  /** Nombre de bandes (défaut 24). */
  steps?: number;
  /** Style du conteneur externe (taille, marges, rayon). */
  style?: StyleProp<ViewStyle>;
  /** Style du conteneur de contenu, posé AU-DESSUS des bandes. */
  contentStyle?: StyleProp<ViewStyle>;
};

export function GradientView({
  children,
  from = colors.energy.from,
  to = colors.energy.to,
  steps = DEFAULT_STEPS,
  style,
  contentStyle,
}: GradientViewProps) {
  // Interpolation figée au rendu : valeurs statiques, aucun calcul animé.
  const strips = React.useMemo(
    () =>
      Array.from({ length: steps }, (_, index) =>
        mixHex(from, to, steps === 1 ? 0 : index / (steps - 1)),
      ),
    [from, to, steps],
  );

  return (
    <View style={[styles.container, style]}>
      <View
        style={[StyleSheet.absoluteFill, styles.stripsRow, styles.noTouch]}
        pointerEvents="none"
      >
        {strips.map((color, index) => (
          <View
            key={index}
            style={[
              styles.strip,
              // Recouvrement d'1 px : masque les interstices d'arrondi
              // du moteur de layout (fines raies du fond entre bandes).
              index > 0 && styles.stripOverlap,
              { backgroundColor: color },
            ]}
          />
        ))}
      </View>
      {children != null && <View style={contentStyle}>{children}</View>}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    overflow: 'hidden',
  },
  stripsRow: {
    flexDirection: 'row',
  },
  noTouch: {
    pointerEvents: 'none',
  },
  strip: {
    flex: 1,
  },
  stripOverlap: {
    marginLeft: -1,
  },
});
