import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import BrandLogo from '../components/BrandLogo'
import LanguageSwitcher from '../components/LanguageSwitcher'

/**
 * Page d'accueil : sélection de la console.
 *
 * Vitrine du produit — le versant « gaming » de D-001 est assumé ici
 * (halo d'énergie, dégradés du logo, cartes à accents), contrairement aux
 * consoles de direct qui restent sobres. Identité visuelle : _home.scss.
 *
 * Scaffolding uniquement — aucune authentification n'est implémentée.
 * TODO : brancher l'authentification quand le contrat d'auth existera
 * (les deux chemins d'auth sont distincts, I-37 — à clarifier pour ces surfaces).
 */
export default function HomePage() {
  const { t } = useTranslation()

  return (
    <main className="home flex min-h-full flex-col items-center justify-center gap-10 p-8">
      <div className="absolute top-4 right-4">
        <LanguageSwitcher />
      </div>

      <header className="text-center">
        <h1 className="flex justify-center">
          <BrandLogo className="brand-logo--hero h-16" />
        </h1>
        <p className="home__subtitle mt-3 text-lg">{t('home.subtitle')}</p>
      </header>

      <nav className="flex w-full max-w-3xl flex-col gap-6 sm:flex-row">
        <Link to="/animateur" className="console-card console-card--host flex-1 rounded-2xl p-8">
          <span className="console-card__badge">{t('home.hostConsole.badge')}</span>
          <span className="console-card__title mt-4 block text-2xl font-bold">
            {t('home.hostConsole.title')}
          </span>
          <span className="console-card__description mt-2 block text-sm">
            {t('home.hostConsole.description')}
          </span>
        </Link>

        <Link
          to="/agent-questions"
          className="console-card console-card--agent flex-1 rounded-2xl p-8"
        >
          <span className="console-card__badge">{t('home.questionAgent.badge')}</span>
          <span className="console-card__title mt-4 block text-2xl font-bold">
            {t('home.questionAgent.title')}
          </span>
          <span className="console-card__description mt-2 block text-sm">
            {t('home.questionAgent.description')}
          </span>
        </Link>
      </nav>
    </main>
  )
}
