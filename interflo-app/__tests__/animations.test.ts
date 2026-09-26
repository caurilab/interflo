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
});
