import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

interface ConfirmDialogProps {
  open: boolean
  title: string
  /** Description de l'effet de l'action, affichée avant confirmation. */
  children: ReactNode
  confirmLabel: string
  /** Confirmation bloquée (action en vol, saisie incomplète). */
  confirmDisabled?: boolean
  onConfirm: () => void
  onCancel: () => void
}

/**
 * Modale de confirmation (F-4 / EXA-4).
 *
 * Toute action à effet sur le direct passe par ce composant : aucune action
 * destructive ne doit être atteignable par un seul toucher accidentel.
 * Le bouton de confirmation est exigeant (grand, distinct) ; l'annulation
 * est le geste par défaut. Identité visuelle : _confirm-dialog.scss (D-001).
 */
export default function ConfirmDialog({
  open,
  title,
  children,
  confirmLabel,
  confirmDisabled,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  const { t } = useTranslation()

  if (!open) return null

  return (
    <div
      role="alertdialog"
      aria-modal="true"
      aria-label={title}
      className="confirm-dialog__backdrop fixed inset-0 z-50 flex items-center justify-center p-6"
      onClick={onCancel}
    >
      <div
        className="confirm-dialog__panel w-full max-w-lg rounded-2xl p-8"
        onClick={(event) => event.stopPropagation()}
      >
        <h2 className="confirm-dialog__title text-2xl font-bold">{title}</h2>
        <div className="mt-4 text-lg text-neutral-200">{children}</div>
        <div className="mt-8 flex gap-4">
          <button
            type="button"
            onClick={onCancel}
            className="confirm-dialog__cancel min-h-16 flex-1 rounded-xl text-xl font-semibold"
          >
            {t('common.cancel')}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={confirmDisabled}
            className="confirm-dialog__confirm min-h-16 flex-1 rounded-xl text-xl font-bold"
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
