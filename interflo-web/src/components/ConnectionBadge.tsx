import { useTranslation } from 'react-i18next'

/**
 * État du lien avec le serveur, tel que mesuré par les requêtes réelles :
 * - online       : la dernière requête a réussi ;
 * - offline      : la dernière requête a échoué (réseau, 5xx…) — F-2 ;
 * - unauthorized : jeton absent ou refusé (401/404) — console non connectée.
 */
export type ConnectionStatus = 'online' | 'offline' | 'unauthorized'

interface ConnectionBadgeProps {
  /**
   * État réel du lien. Par défaut « unauthorized » : jamais « en ligne »
   * sur la base d'un état local (F-1/F-2). La console agent questions, non
   * connectée, utilise ce défaut.
   */
  status?: ConnectionStatus
}

/**
 * Indicateur de lien avec le serveur (F-2 / EXA-2).
 *
 * La perte de lien doit être visible immédiatement : un silence ambigu en
 * direct est pire qu'une erreur affichée. Le badge passe au rouge dès qu'une
 * requête échoue et ne revient au vert qu'au retour d'une requête réussie.
 * Bloc plein haut contraste, lisible à distance (voir _connection-badge.scss).
 */
export default function ConnectionBadge({ status = 'unauthorized' }: ConnectionBadgeProps) {
  const { t } = useTranslation()

  return (
    <div
      role="status"
      className={`connection-badge connection-badge--${status} flex items-center gap-3 rounded-xl px-5 py-3`}
    >
      <span className="connection-badge__dot h-4 w-4 rounded-full" aria-hidden="true" />
      <span className="connection-badge__label text-lg font-bold">
        {t(`connectionBadge.${status}`)}
      </span>
    </div>
  )
}
