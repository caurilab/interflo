import { SEALED, DEFAULTS } from '../src/config/gameConfig';

/**
 * Test de fumée — verrouille les valeurs SCELLÉES par décision PO.
 * Si l'une de ces valeurs change, c'est une décision produit, pas un refactor.
 */
describe('gameConfig', () => {
  it('garde les valeurs scellées (I-4, I-27, I-28)', () => {
    expect(SEALED.propositionCount).toBe(4);
    expect(SEALED.eliminationRoundCount).toBe(5);
    expect(SEALED.possibleWinnerCounts).toEqual([1, 3, 5]);
  });

  it("refuse les caractères ambigus dans l'alphabet du code court (EX-03)", () => {
    // 0/O, 1/I/L, 5/S retirés — alphabet lisible à l'oral
    expect(DEFAULTS.shortCodeAlphabet).not.toMatch(/[0O1IL5S]/);
  });
});
