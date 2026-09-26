import fr from '../src/i18n/locales/fr.json';
import en from '../src/i18n/locales/en.json';

/**
 * Garde-fou i18n (CONVENTIONS.md §2) : une clé absente de l'une des deux
 * locales est un défaut bloquant — fr et en partent ensemble ou pas du tout.
 */

/** Aplati un objet de locale en liste de clés pointées (« pairing.title »). */
function flattenKeys(node: Record<string, unknown>, prefix = ''): string[] {
  return Object.entries(node).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value !== null && typeof value === 'object') {
      return flattenKeys(value as Record<string, unknown>, path);
    }
    return [path];
  });
}

describe('cohérence des locales i18n', () => {
  const frKeys = flattenKeys(fr).sort();
  const enKeys = flattenKeys(en).sort();

  it('toute clé de fr.json existe dans en.json', () => {
    const missingInEn = frKeys.filter((key) => !enKeys.includes(key));
    expect(missingInEn).toEqual([]);
  });

  it('toute clé de en.json existe dans fr.json', () => {
    const missingInFr = enKeys.filter((key) => !frKeys.includes(key));
    expect(missingInFr).toEqual([]);
  });

  it('les deux locales exposent le même nombre de clés', () => {
    expect(frKeys.length).toBe(enKeys.length);
  });
});
