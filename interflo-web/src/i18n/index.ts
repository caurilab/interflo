import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import en from './locales/en.json'
import fr from './locales/fr.json'

/**
 * Initialisation i18n des consoles web (convention PO §2).
 *
 * - Français = locale par défaut (public francophone), anglais en fallback.
 * - Pas de détecteur de langue complexe : le choix se fait via le composant
 *   LanguageSwitcher et persiste dans localStorage.
 * - Règle bloquante : toute clé `fr` doit exister en `en` (vérifiée par
 *   scripts/check-i18n-keys.mjs).
 */

const STORAGE_KEY = 'interflo-language'
const storedLanguage = localStorage.getItem(STORAGE_KEY)

void i18n.use(initReactI18next).init({
  resources: {
    fr: { translation: fr },
    en: { translation: en },
  },
  lng: storedLanguage === 'en' ? 'en' : 'fr',
  fallbackLng: 'en',
  interpolation: {
    // React échappe déjà le HTML — pas de double échappement.
    escapeValue: false,
  },
})

// L'attribut lang du document suit la locale active (lecteurs d'écran).
document.documentElement.lang = i18n.language
i18n.on('languageChanged', (language) => {
  document.documentElement.lang = language
  localStorage.setItem(STORAGE_KEY, language)
})

export default i18n
