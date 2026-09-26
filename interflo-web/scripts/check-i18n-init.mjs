#!/usr/bin/env node
/**
 * Test de fumée : initialise i18next exactement comme src/i18n/index.ts
 * (hors React / DOM) et vérifie qu'aucun warning n'est émis et que chaque
 * clé se résout dans les deux locales.
 *
 * Usage : node scripts/check-i18n-init.mjs (ou npm run check:i18n)
 * Sortie 0 = initialisation propre, 1 = warning ou clé non résolue.
 */
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import i18next from 'i18next'
import { initReactI18next } from 'react-i18next'

const localesDir = join(dirname(fileURLToPath(import.meta.url)), '../src/i18n/locales')
const fr = JSON.parse(readFileSync(join(localesDir, 'fr.json'), 'utf8'))
const en = JSON.parse(readFileSync(join(localesDir, 'en.json'), 'utf8'))

/** Aplati { a: { b: 'x' } } en ['a.b']. */
function leafKeys(node, prefix = '') {
  return Object.entries(node).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key
    return value !== null && typeof value === 'object' ? leafKeys(value, path) : [path]
  })
}

const warnings = []
const originalWarn = console.warn
console.warn = (...args) => warnings.push(args.join(' '))

const instance = i18next.createInstance()
await instance.use(initReactI18next).init({
  resources: {
    fr: { translation: fr },
    en: { translation: en },
  },
  lng: 'fr',
  fallbackLng: 'en',
  interpolation: { escapeValue: false },
})

console.warn = originalWarn

const errors = []
// Variables d'interpolation utilisées par les locales (fournies au cas où).
const interpolationValues = { seconds: 22, max: 3 }

for (const language of ['fr', 'en']) {
  for (const key of leafKeys(fr)) {
    const output = instance.t(key, { lng: language, ...interpolationValues })
    // i18next renvoie la clé brute quand la traduction manque.
    if (typeof output !== 'string' || output === key || output.trim() === '') {
      errors.push(`Clé non résolue en ${language} : ${key}`)
    }
  }
}

const i18nWarnings = warnings.filter((w) => w.toLowerCase().includes('i18next'))

if (errors.length > 0 || i18nWarnings.length > 0) {
  console.error('❌ Initialisation i18n défaillante :')
  for (const error of errors) console.error(`  - ${error}`)
  for (const warning of i18nWarnings) console.error(`  - warning : ${warning}`)
  process.exit(1)
}

console.log('✅ i18n initialisé sans warning, toutes les clés se résolvent en fr et en.')
