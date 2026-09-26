#!/usr/bin/env node
/**
 * Vérifie la cohérence des locales i18n (convention PO §2) :
 * « une clé absente de la locale en est un défaut bloquant ».
 *
 * Contrôles :
 * - fr.json et en.json ont exactement les mêmes clés (structure incluse) ;
 * - aucune valeur vide ;
 * - les placeholders d'interpolation ({{...}}) sont identiques entre fr et en.
 *
 * Usage : node scripts/check-i18n-keys.mjs (ou npm run check:i18n)
 * Sortie 0 = synchronisé, 1 = divergence.
 */
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const localesDir = join(dirname(fileURLToPath(import.meta.url)), '../src/i18n/locales')
const fr = JSON.parse(readFileSync(join(localesDir, 'fr.json'), 'utf8'))
const en = JSON.parse(readFileSync(join(localesDir, 'en.json'), 'utf8'))

/** Aplati { a: { b: 'x' } } en { 'a.b': 'x' }. */
function flatten(node, prefix = '') {
  return Object.entries(node).reduce((acc, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key
    if (value !== null && typeof value === 'object') return { ...acc, ...flatten(value, path) }
    return { ...acc, [path]: value }
  }, {})
}

const frFlat = flatten(fr)
const enFlat = flatten(en)

const errors = []

for (const key of Object.keys(frFlat)) {
  if (!(key in enFlat)) errors.push(`Clé présente en fr mais absente en en : ${key}`)
}
for (const key of Object.keys(enFlat)) {
  if (!(key in frFlat)) errors.push(`Clé présente en en mais absente en fr : ${key}`)
}

for (const [key, value] of Object.entries(frFlat)) {
  if (typeof value !== 'string' || value.trim() === '') errors.push(`Valeur vide ou non textuelle (fr) : ${key}`)
  if (key in enFlat) {
    const enValue = enFlat[key]
    if (typeof enValue !== 'string' || enValue.trim() === '') {
      errors.push(`Valeur vide ou non textuelle (en) : ${key}`)
    } else {
      // Les placeholders {{...}} doivent être identiques dans les deux locales.
      const frPlaceholders = [...value.matchAll(/\{\{\w+\}\}/g)].map((m) => m[0]).sort()
      const enPlaceholders = [...enValue.matchAll(/\{\{\w+\}\}/g)].map((m) => m[0]).sort()
      if (frPlaceholders.join(',') !== enPlaceholders.join(',')) {
        errors.push(
          `Placeholders divergents pour ${key} : fr=${frPlaceholders.join(',')} en=${enPlaceholders.join(',')}`,
        )
      }
    }
  }
}

const keyCount = Object.keys(frFlat).length

if (errors.length > 0) {
  console.error(`❌ Locales i18n NON synchronisées (${errors.length} divergence(s)) :`)
  for (const error of errors) console.error(`  - ${error}`)
  process.exit(1)
}

console.log(`✅ Locales i18n synchronisées : ${keyCount} clés, fr + en, placeholders identiques.`)
