/**
 * Garde-fous des constantes d'animation (D-001 §3).
 *
 * Ces réglages sont des points de départ paramétrables, mais ils portent
 * des invariants d'expérience : geste immédiat (M-1), entrée de gamme
 * (pas de boucle agitée), transitions qui se sentent sans traîner.
 * Un changement de valeur doit rester DANS ces bornes ou être arbitré PO.
 */
import { MOTION } from '../src/config/animations';

describe('MOTION — constantes d\'animation (D-001 §3)', () => {
  it('la cascade des propositions reste légère (~60 ms par proposition)', () => {
    expect(MOTION.stagger.stepMs).toBe(60);
    // La dernière proposition arrive avant ~500 ms : la fenêtre est courte.
    expect(MOTION.stagger.baseMs + 3 * MOTION.stagger.stepMs).toBeLessThanOrEqual(500);
  });

  it('le feedback pressé est un léger resserrage (M-1 : geste immédiat)', () => {
    expect(MOTION.press.scale).toBeGreaterThanOrEqual(0.95);
    expect(MOTION.press.scale).toBeLessThan(1);
    // Spring quasi instantané : raideur élevée, masse faible.
    expect(MOTION.spring.press.stiffness).toBeGreaterThanOrEqual(400);
    expect(MOTION.spring.press.mass).toBeLessThanOrEqual(0.5);
  });

  it('les transitions d\'écran restent brèves (ni flash ni traînée)', () => {
    expect(MOTION.screen.enterMs).toBeGreaterThanOrEqual(150);
    expect(MOTION.screen.enterMs).toBeLessThanOrEqual(400);
    expect(MOTION.screen.exitMs).toBeLessThan(MOTION.screen.enterMs);
  });

  it('les pulses ambiants sont lents et d\'amplitude faible (entrée de gamme)', () => {
    for (const spec of Object.values(MOTION.pulse)) {
      expect(spec.periodMs).toBeGreaterThanOrEqual(1000);
      expect(spec.scaleMin).toBeGreaterThanOrEqual(0.75);
      expect(spec.scaleMin).toBeLessThan(1);
    }
    // Le pulse de célébration reste mesuré (jamais de battement agressif).
    expect(MOTION.pulse.celebration.scaleMin).toBeGreaterThanOrEqual(0.9);
  });

  it('la cascade interne des écrans de verdict reste sous la seconde', () => {
    expect(MOTION.cascade.subtitleMs).toBeLessThanOrEqual(600);
    expect(MOTION.cascade.badgeMs).toBeLessThan(MOTION.cascade.titleMs);
    expect(MOTION.cascade.titleMs).toBeLessThan(MOTION.cascade.subtitleMs);
  });

  it('le halo ambiant reste lent et discret (entrée de gamme)', () => {
    for (const spec of Object.values(MOTION.energy)) {
      expect(spec.periodMs).toBeGreaterThanOrEqual(3000);
      expect(spec.opacityMax).toBeLessThanOrEqual(0.2);
      expect(spec.opacityMin).toBeLessThan(spec.opacityMax);
      expect(spec.driftPx).toBeLessThanOrEqual(60);
    }
  });

  it('les ondes de choc sont brèves, finies et mesurées (pas de boucle)', () => {
    expect(MOTION.shockwave.ringCount).toBeGreaterThanOrEqual(1);
    expect(MOTION.shockwave.ringCount).toBeLessThanOrEqual(3);
    expect(MOTION.shockwave.durationMs).toBeLessThanOrEqual(1500);
    expect(MOTION.shockwave.maxOpacity).toBeLessThanOrEqual(0.7);
  });

  it('les confettis sont un coup unique, bref et peu nombreux (entrée de gamme)', () => {
    expect(MOTION.confetti.particleCount).toBeLessThanOrEqual(60);
    expect(MOTION.confetti.maxDurationMs).toBeLessThanOrEqual(2500);
    expect(MOTION.confetti.maxOpacity).toBeLessThanOrEqual(1);
    expect(MOTION.confetti.spread).toBeLessThanOrEqual(240);
    expect(MOTION.confetti.fall).toBeLessThanOrEqual(400);
  });
});
