import { useTranslation } from 'react-i18next'
import logoHorizontal from '../assets/brand/logo-horizontal.svg'

/**
 * Logo horizontal Interflo (icône + wordmark).
 *
 * ⚠️ Le wordmark est blanc (#fff) : le logo n'est lisible que sur fond sombre.
 * Les consoles sont en thème sombre (#0b0f14), ne pas l'utiliser sur fond clair.
 */
export default function BrandLogo({ className = 'h-10' }: { className?: string }) {
  const { t } = useTranslation()

  return <img src={logoHorizontal} alt={t('common.logoAlt')} className={className} />
}
