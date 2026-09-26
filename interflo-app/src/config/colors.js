/**
 * Palette Interflo — source unique de vérité des tokens couleur (mobile).
 *
 * Direction visuelle D-001 « fun, gaming, mais corporate » (2026-09-25) :
 * palette dérivée du logo officiel — magenta #d6007d, dégradé d'énergie
 * #d96034 → #d6007d, cream #fceaac, surfaces sombres obligatoires
 * (le wordmark du logo est blanc).
 *
 * Consommée par :
 * - tailwind.config.js → classes NativeWind (bg-brand, text-cream…),
 * - App.tsx / RootNavigator.tsx → thème React Navigation,
 * - composants dégradés (GradientView) via styles inline alimentés par
 *   ces tokens — jamais de valeur hexadécimale en dur ailleurs.
 *
 * Module CommonJS volontairement : requis à la fois par Metro/babel (app)
 * et par Node (tailwind.config.js) sans transpilation.
 *
 * TODO(design) : personnalisation par tenant non tranchée (06-ux-ui.md §6).
 */
module.exports = {
  // Magenta de marque — actions principales, accents forts (D-001 §2)
  brand: {
    DEFAULT: '#D6007D',
    // Éclairci : liserés lumineux, états actifs sur fond sombre
    light: '#FF4DAB',
    // Profond : état pressé, ombres colorées, dégradés internes
    dark: '#7A0047',
  },
  // Dégradé d'énergie — boutons de jeu et moments forts (D-001 §2)
  energy: {
    from: '#D96034', // orange
    to: '#D6007D', // magenta (= brand.DEFAULT, dupliqué pour lisibilité du dégradé)
  },
  // Cream — highlights, texte sur accents, éléments « gain » (D-001 §2)
  cream: {
    DEFAULT: '#FCEAAC',
    // Atténué : texte secondaire posé sur un accent
    dim: '#B9AC78',
  },
  // Surfaces sombres — salon éclairé par la télévision (06-ux-ui.md §2.2)
  surface: {
    DEFAULT: '#101418', // fond principal mobile (D-001 §2)
    deep: '#0B0F14', // fond maximal (puits sous les cartes, champs de saisie)
    raised: '#1A2129', // cartes et zones d'action
    overlay: '#242E38', // état pressé des zones neutres
  },
  white: '#FFFFFF', // texte principal sur fond sombre
};
