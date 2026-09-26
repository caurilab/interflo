import { ServerClock } from '../src/game/clockSync';

/**
 * Synchronisation d'horloge (I-6/EX-16) : l'offset est estimé au milieu de
 * l'aller-retour, et seul l'échantillon au plus petit aller-retour est
 * conservé. La tolérance reste gérée côté serveur (clock_tolerance_ms).
 */
describe('ServerClock', () => {
  const ISO = '2026-09-26T20:00:00.000Z';
  const SERVER_MS = Date.parse(ISO);

  it('sans échantillon, retombe sur l\'horloge appareil (offset 0)', () => {
    const clock = new ServerClock();
    expect(clock.offsetMs).toBe(0);
    const before = Date.now();
    const now = clock.nowMs();
    const after = Date.now();
    expect(now).toBeGreaterThanOrEqual(before);
    expect(now).toBeLessThanOrEqual(after);
  });

  it('estime l\'offset au milieu de l\'aller-retour', () => {
    const clock = new ServerClock();
    // t0 = 1000, t1 = 1200 → milieu 1100 → offset = SERVER_MS − 1100.
    clock.recordSample(ISO, 1000, 1200);
    expect(clock.offsetMs).toBe(SERVER_MS - 1100);
  });

  it('conserve l\'échantillon au plus petit aller-retour', () => {
    const clock = new ServerClock();
    // Échantillon large (RTT 1000) puis précis (RTT 100) sur la même heure.
    clock.recordSample(ISO, 1000, 2000);
    clock.recordSample(ISO, 5000, 5100);
    expect(clock.offsetMs).toBe(SERVER_MS - 5050);
    // Un échantillon moins bon ne remplace pas le meilleur.
    clock.recordSample(ISO, 9000, 11000);
    expect(clock.offsetMs).toBe(SERVER_MS - 5050);
  });

  it('ignore un server_time illisible plutôt que de corrompre l\'offset', () => {
    const clock = new ServerClock();
    clock.recordSample(ISO, 1000, 1200);
    clock.recordSample('pas-une-date', 0, 1);
    expect(clock.offsetMs).toBe(SERVER_MS - 1100);
  });

  it('nowMs applique l\'offset (horodatage du geste corrigé, I-6)', () => {
    const clock = new ServerClock();
    // Horloge appareil 5 s en retard sur le serveur.
    const t0 = Date.now();
    clock.recordSample(new Date(t0 + 5000).toISOString(), t0, t0);
    expect(clock.nowMs()).toBeGreaterThanOrEqual(Date.now() + 4999);
  });
});
