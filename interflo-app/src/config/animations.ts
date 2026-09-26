/**
 * Constantes d'animation — Interflo (D-001 §3, amendement PO 2026-09-25).
 *
 * Direction : « tout doit être bien animé, avec des effets » — le produit
 * est vivant. Garde-fous D-001 §3 / §4 :
 * - tout passe par Reanimated (worklets, thread UI) — JAMAIS d'animation
 *   pilotée par le thread JS, qui doit rester libre pour le polling 2 s ;
 * - propriétés animées limitées à `opacity` et `transform` — aucun flou,
 *   aucune ombre portée animée (téléphones d'entrée de gamme, batterie) ;
 * - les animations ne bloquent jamais le geste (M-1) : le feedback pressé
 *   est un spring quasi instantané et le handler `onPress` tire sans attendre ;
 * - on anime les TRANSITIONS, pas les états stables : le polling qui
 *   re-rend un écran sans changement d'état ne relance rien (les entrées
 *   sont keyées sur l'identité de l'état, cf. PlayScreen).
 *
 * ⚠️ Règle du projet : aucune de ces valeurs n'est validée par le PO —
 * ce sont des POINTS DE DÉPART PARAMÉTRABLES (comme DEFAULTS de
 * gameConfig), à faire arbitrer si le ressenti ne convient pas.
 */

export const MOTION = {
  /**
   * Transition entre états de jeu (waiting → question → answered → …) :
   * fondu croisé piloté par PlayScreen. L'entrée est un peu plus longue
   * que la sortie — l'arrivée d'une nouvelle question doit se sentir.
   */
  screen: {
    enterMs: 260,
    exitMs: 140,
  },

  /**
   * Cascade d'entrée des 4 propositions (I-4) à l'ouverture d'une manche.
   * `staggerMs` = délai entre chaque proposition (~60 ms demandé) ;
   * `baseMs` = la question se pose d'abord, les réponses suivent.
   */
  stagger: {
    baseMs: 120,
    stepMs: 60,
  },

  /**
   * Feedback du geste sur une proposition (M-1 : le geste reste immédiat).
   * Le handler de réponse part au `onPress`, indépendamment de l'animation.
   */
  press: {
    scale: 0.97,
  },

  /**
   * Springs modérés — l'énergie gaming sans le rebond cartoon.
   * - press : quasi instantané (le doigt sent un contact, pas un délai) ;
   * - soft : entrées d'écran et panneaux (ferme, sans dépassement marqué) ;
   * - pop : pastilles de verdict / gagnant (petit dépassement = fête).
   */
  spring: {
    press: { damping: 26, stiffness: 520, mass: 0.4 },
    soft: { damping: 20, stiffness: 160, mass: 0.9 },
    pop: { damping: 11, stiffness: 240, mass: 0.7 },
  },

  /**
   * Pulse ambiant — la vie discrète des écrans d'attente et la célébration
   * mesurée de l'écran gagnant. Amplitude faible, période longue : aucun
   * coût perceptible sur entrée de gamme.
   */
  pulse: {
    /** Indicateur d'attente (WaitingScreen) — lent, calme. */
    slow: { periodMs: 1800, scaleMin: 0.82, opacityMin: 0.45 },
    /** Point « fenêtre ouverte » (GameScreen) — plus vif, la fenêtre est courte. */
    fast: { periodMs: 1100, scaleMin: 0.78, opacityMin: 0.55 },
    /** Pastille gagnant (SessionEndScreen) — respiration de fête mesurée. */
    celebration: { periodMs: 1200, scaleMin: 0.94, opacityMin: 0.9 },
  },

  /**
   * Délais de cascade internes aux écrans de verdict / fin (pastille,
   * titre, sous-titre arrivent l'un après l'autre).
   */
  cascade: {
    badgeMs: 160,
    titleMs: 260,
    subtitleMs: 360,
  },

  /**
   * Halo d'énergie ambiant (EnergyBackground) — deux nappes magenta/orange
   * qui respirent et dérivent lentement derrière le contenu. Amplitude et
   * opacité faibles : la « vie » du fond, jamais une distraction (entrée de
   * gamme). Intensité croissante calm (attente) → live (fenêtre) →
   * celebrate (gain). Périodes longues, aucun coût perceptible.
   */
  energy: {
    calm: { periodMs: 8000, opacityMin: 0.05, opacityMax: 0.1, driftPx: 24 },
    live: { periodMs: 6000, opacityMin: 0.08, opacityMax: 0.15, driftPx: 32 },
    celebrate: { periodMs: 4000, opacityMin: 0.1, opacityMax: 0.18, driftPx: 40 },
  },

  /**
   * Ondes de choc (ShockwaveRings) autour des pastilles de verdict/gagnant —
   * anneaux qui s'étendent et s'estompent, pur transform + opacity. Coup
   * unique : la célébration du moment, sans boucle coûteuse.
   */
  shockwave: {
    ringCount: 2,
    durationMs: 1000,
    staggerMs: 240,
    maxScale: 2.2,
    maxOpacity: 0.55,
  },

  /**
   * Confettis de célébration (ConfettiBurst) — particules qui s'éparpillent
   * depuis le point de célébration en tombant (gravité), en tournant et en
   * s'estompant. Pur transform + opacity (worklets), aucune ombre ni flou —
   * entrée de gamme. Coup unique au montage, pas de boucle.
   */
  confetti: {
    particleCount: 36,
    minDurationMs: 1000,
    maxDurationMs: 1800,
    minDelayMs: 0,
    maxDelayMs: 220,
    /** Étendue horizontale max (px). */
    spread: 150,
    /** Chute verticale (gravité), px. */
    fall: 240,
    minSize: 6,
    maxSize: 12,
    maxRotationDeg: 360,
    maxOpacity: 0.95,
  },
} as const;
