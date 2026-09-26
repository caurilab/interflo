import { useTranslation } from 'react-i18next'

const languages = ['fr', 'en'] as const

/**
 * Sélecteur de langue simple FR/EN (convention PO §2).
 *
 * Réutilisable : placé dans l'en-tête des deux consoles et de l'accueil.
 * Le choix persiste dans localStorage (voir src/i18n/index.ts).
 * Identité visuelle : _language-switcher.scss (D-001).
 */
export default function LanguageSwitcher() {
  const { t, i18n } = useTranslation()

  return (
    <div
      role="group"
      aria-label={t('languageSwitcher.label')}
      className="language-switcher flex overflow-hidden rounded-lg"
    >
      {languages.map((language) => (
        <button
          key={language}
          type="button"
          onClick={() => void i18n.changeLanguage(language)}
          aria-pressed={i18n.language === language}
          className={`language-switcher__option px-3 py-2 text-sm font-semibold uppercase ${
            i18n.language === language ? 'is-active' : ''
          }`}
        >
          {t(`languageSwitcher.${language}`)}
        </button>
      ))}
    </div>
  )
}
