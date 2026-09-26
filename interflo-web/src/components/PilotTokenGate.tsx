import { useState } from 'react'
import type { FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import BrandLogo from './BrandLogo'
import LanguageSwitcher from './LanguageSwitcher'

interface PilotTokenGateProps {
  /** Appelé avec le jeton saisi — la console le validera au premier appel. */
  onToken: (token: string) => void
}

/**
 * Écran d'entrée du jeton pilote (`X-Pilot-Token`).
 *
 * ⚠️ PROVISOIRE : l'auth animateur n'est spécifiée nulle part — le backend
 * expose un token opaque par session de jeu (game_sessions.pilot_token,
 * affiché en lecture seule dans Filament). Cet écran est à remplacer dès
 * arbitrage de l'auth animateur.
 *
 * Règles :
 * - le jeton est stocké en sessionStorage, jamais réaffiché après saisie
 *   (champ de type password, valeur effacée du DOM après envoi) ;
 * - la validité n'est PAS testée ici (aucune route de validation n'existe
 *   dans le contrat) : sans jeton valide, les contrôles de la console
 *   restent inactifs et le badge indique « non connecté » (401/404).
 */
export default function PilotTokenGate({ onToken }: PilotTokenGateProps) {
  const { t } = useTranslation()
  const [value, setValue] = useState('')

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault()
    const token = value.trim()
    if (token === '') return
    onToken(token)
    // La saisie ne reste pas dans le DOM une fois le jeton transmis.
    setValue('')
  }

  return (
    <main className="flex min-h-full flex-col items-center justify-center gap-6 p-4">
      <div className="absolute right-4 top-4">
        <LanguageSwitcher />
      </div>

      <BrandLogo className="h-10" />

      <form
        onSubmit={handleSubmit}
        className="token-gate console-panel flex w-full max-w-xl flex-col gap-5 rounded-2xl p-8"
      >
        <div>
          <h1 className="text-2xl font-bold">{t('pilotTokenGate.title')}</h1>
          <p className="mt-2 text-neutral-300">{t('pilotTokenGate.intro')}</p>
        </div>

        {/* Avertissement assumé : ce mécanisme est provisoire, à remplacer
            dès que l'auth animateur sera arbitrée (hypothèse backend n°1). */}
        <p className="token-gate__provisional rounded-xl px-4 py-3 text-sm">
          {t('pilotTokenGate.provisionalWarning')}
        </p>

        <label className="flex flex-col gap-2">
          <span className="console-panel__title text-sm font-semibold uppercase tracking-widest">
            {t('pilotTokenGate.label')}
          </span>
          <input
            type="password"
            value={value}
            onChange={(event) => setValue(event.target.value)}
            placeholder={t('pilotTokenGate.placeholder')}
            autoComplete="off"
            autoFocus
            className="console-input min-h-16 rounded-xl px-4 text-xl"
          />
        </label>

        <button
          type="submit"
          disabled={value.trim() === ''}
          className="token-gate__submit min-h-16 rounded-xl text-xl font-bold"
        >
          {t('pilotTokenGate.submit')}
        </button>

        <p className="text-sm text-neutral-400">{t('pilotTokenGate.storageHint')}</p>
      </form>
    </main>
  )
}
