/**
 * Synchronisation d'horloge serveur ↔ appareil (I-6 / EX-16) — PROVISOIRE.
 *
 * Pourquoi : l'horodatage de la réponse se fait AU GESTE, sur l'appareil
 * (I-6 — on ne pénalise pas les connexions lentes), mais l'horloge d'un
 * téléphone grand public peut dériver de plusieurs secondes. Chaque
 * réponse du contrat porte `server_time` (ISO 8601) : on en déduit un
 * offset serveur↔appareil et on horodate le geste avec l'horloge corrigée.
 *
 * Méthode (volontairement simple, documentée comme provisoire) :
 * - à chaque requête, on note t0 (avant l'envoi) et t1 (à la réception)
 *   en horloge appareil ;
 * - l'estimation est offset = server_time − (t0 + t1) / 2 (milieu de
 *   l'aller-retour, hypothèse de symétrie) ;
 * - on conserve l'échantillon au PLUS PETIT aller-retour (le moins
 *   asymétrique) — pas de moyenne, un seul échantillon de référence ;
 * - aucune dérive n'est compensée : un meilleur échantillon remplace
 *   le précédent.
 *
 * ⚠️ La tolérance est gérée CÔTÉ SERVEUR (`clock_tolerance_ms`, défaut
 * 2 000 ms — EX-17) : cette estimation n'a pas à être parfaite, elle doit
 * seulement ramener l'horodatage dans la tolérance. L'ADR temps réel
 * remplacera probablement cette heuristique.
 *
 * Sans état global : une instance par machine de jeu (une session).
 */

/** Échantillon : offset estimé + aller-retour qui l'a produit. */
type ClockSample = {
  offsetMs: number;
  roundTripMs: number;
};

export class ServerClock {
  private sample: ClockSample | null = null;

  /**
   * Enregistre un échantillon issu d'une réponse portant `server_time`.
   * Ne retient que l'échantillon au plus petit aller-retour.
   *
   * @param serverTimeIso `server_time` de la réponse (ISO 8601).
   * @param sentAtMs      Horloge appareil juste avant l'envoi (ms epoch).
   * @param receivedAtMs  Horloge appareil juste après réception (ms epoch).
   */
  recordSample(serverTimeIso: string, sentAtMs: number, receivedAtMs: number): void {
    const serverMs = Date.parse(serverTimeIso);
    if (Number.isNaN(serverMs)) {
      // Horloge serveur illisible : on ignore l'échantillon plutôt que
      // de corrompre l'offset — le serveur borne de toute façon (EX-17).
      return;
    }
    const roundTripMs = Math.max(0, receivedAtMs - sentAtMs);
    const offsetMs = serverMs - (sentAtMs + receivedAtMs) / 2;
    if (this.sample === null || roundTripMs < this.sample.roundTripMs) {
      this.sample = { offsetMs, roundTripMs };
    }
  }

  /**
   * Heure serveur estimée, en ms epoch. À utiliser pour `client_timestamp`
   * (horodatage du geste, I-6) — jamais pour l'affichage.
   *
   * Sans échantillon (première réponse pas encore reçue), retombe sur
   * l'horloge appareil brute : le serveur rejettera un horodatage
   * physiquement impossible (IMPOSSIBLE_TIMESTAMP) sans conséquence pour
   * le joueur (le droit de répondre n'est pas consommé).
   */
  nowMs(): number {
    return Math.round(Date.now() + (this.sample?.offsetMs ?? 0));
  }

  /** Offset courant en ms (diagnostic uniquement — non affiché). */
  get offsetMs(): number {
    return this.sample?.offsetMs ?? 0;
  }
}
