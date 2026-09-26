import type { ReactNode } from 'react'

interface BigActionButtonProps {
  /** Libellé affiché — l'animateur lit vite, parfois sans regarder (F-3). */
  label: string
  /** Détail secondaire, plus discret. */
  hint?: string
  /**
   * Variante visuelle selon la nature de l'action (hiérarchie D-001) :
   * - energy   : action qui lance le jeu (OUVRIR) — dégradé officiel ;
   * - firm     : action neutre affirmée (FERMER) — surface sombre, bord franc ;
   * - distinct : action d'appoint (MANCHE SUIVANTE) — contour cream.
   */
  tone: 'energy' | 'firm' | 'distinct'
  onPress: () => void
  /**
   * Action indisponible : état serveur incompatible ou lien non établi.
   * Un bouton désactivé ne passe JAMAIS par la confirmation (F-4).
   */
  disabled?: boolean
  icon?: ReactNode
}

/**
 * Gros bouton d'action de la console animateur (F-3).
 *
 * Zone tactile très large, texte très lisible : l'animateur agit en direct,
 * parfois sans regarder l'écran. Ne jamais réduire la hauteur minimale.
 * L'identité visuelle vit dans _big-action-button.scss (D-001) ; le JSX ne
 * porte que le layout utilitaire.
 */
export default function BigActionButton({ label, hint, tone, onPress, disabled }: BigActionButtonProps) {
  return (
    <button
      type="button"
      onClick={onPress}
      disabled={disabled}
      className={`big-action big-action--${tone} min-h-28 w-full rounded-2xl px-6 py-4 text-left`}
    >
      <span className="block text-3xl font-extrabold tracking-wide">{label}</span>
      {hint ? <span className="big-action__hint">{hint}</span> : null}
    </button>
  )
}
