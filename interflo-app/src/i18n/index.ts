import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import fr from './locales/fr.json';
import en from './locales/en.json';

/**
 * Initialisation i18n (convention PO, CONVENTIONS.md §2).
 *
 * - Français = locale par défaut : le public visé est francophone.
 * - Anglais = seconde locale ET fallback : une clé absente de `en`
 *   est un défaut bloquant à la revue — les deux locales partent ensemble.
 * - Aucun texte utilisateur en dur dans les écrans : tout passe par une clé.
 *
 * ⚠️ Interpolation uniquement ({{variable}}) : pas de formes plurielles
 * i18next pour l'instant, afin de ne pas dépendre d'Intl.PluralRules
 * sur Hermes. Si des pluriels deviennent nécessaires, vérifier le support
 * Intl du moteur avant d'activer les clés `_one` / `_other`.
 */

export const DEFAULT_LANGUAGE = 'fr' as const;
export const FALLBACK_LANGUAGE = 'en' as const;

/** Langues supportées — toute nouvelle locale ajoute son fichier ici. */
export const SUPPORTED_LANGUAGES = [DEFAULT_LANGUAGE, FALLBACK_LANGUAGE] as const;
export type SupportedLanguage = (typeof SUPPORTED_LANGUAGES)[number];

i18n.use(initReactI18next).init({
  lng: DEFAULT_LANGUAGE,
  fallbackLng: FALLBACK_LANGUAGE,
  resources: {
    fr: { translation: fr },
    en: { translation: en },
  },
  interpolation: {
    // React échappe déjà le HTML — pas de double échappement.
    escapeValue: false,
  },
});

/**
 * Point d'accroche pour un futur sélecteur de langue.
 *
 * ⚠️ HYPOTHÈSE NON TRANCHÉE : l'emplacement du sélecteur (réglages de
 * l'app ? écran d'appairage ? langue système ?) n'est pas spécifié dans
 * 06-ux-ui.md pour une app joueur grand public. Cette fonction est le
 * seul contrat : l'UI se branchera dessus le jour où le PO arbitre.
 */
export function changeLanguage(language: SupportedLanguage): Promise<unknown> {
  return i18n.changeLanguage(language);
}

export default i18n;
