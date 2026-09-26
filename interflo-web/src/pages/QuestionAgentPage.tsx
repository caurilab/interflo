import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import BrandLogo from '../components/BrandLogo'
import ConnectionBadge from '../components/ConnectionBadge'
import LanguageSwitcher from '../components/LanguageSwitcher'

/**
 * CONSOLE AGENT QUESTIONS — squelette visuel uniquement.
 *
 * Rôle (I-33) : le direct est transcrit avec horodatage, un modèle de langage
 * PROPOSE des questions, l'agent humain VALIDE ou corrige.
 * **Rien ne part à l'antenne sans validation humaine.** La validation est un
 * geste explicite, jamais un défaut : elle ne doit pas pouvoir se faire par
 * accident, et « valider » et « corriger » sont deux actions bien distinctes.
 *
 * Utilisée pendant l'émission, sous pression de temps : rapide avant d'être
 * complète (docs/06-ux-ui.md §5). Hiérarchie D-001 : VALIDER = dégradé
 * d'énergie plein (verrou d'antenne), CORRIGER = contour cream (geste
 * d'atelier) — voir _question-agent.scss.
 *
 * ⚠️ NON CONNECTÉ : aucune donnée réelle. La liste de questions et la
 * transcription sont des placeholders vides.
 *
 * TODO (branchements futurs, tous bloqués par l'absence de contrat) :
 * - flux de transcription horodatée du direct ;
 * - file des questions proposées par le modèle ;
 * - clic sur un time code → retrouver le passage dans la transcription →
 *   remonter au son enregistré à cet instant (lecteur audio à intégrer) ;
 * - envoi de la validation / de la correction au serveur ;
 * - articulation avec l'animateur : qui pousse la question validée à
 *   l'antenne ? (non tranché — docs/10 §5).
 */
export default function QuestionAgentPage() {
  const { t } = useTranslation()

  return (
    <main className="question-agent flex min-h-full flex-col gap-4 p-4">
      <header className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <BrandLogo className="h-8" />
          <div>
            <h1 className="text-2xl font-bold">{t('questionAgent.title')}</h1>
            <p className="text-sm text-neutral-400">{t('questionAgent.subtitle')}</p>
          </div>
        </div>
        <ConnectionBadge />
        <LanguageSwitcher />
        <Link to="/" className="console-back rounded-lg px-4 py-2 text-sm">
          {t('common.back')}
        </Link>
      </header>

      <div className="grid flex-1 grid-cols-1 gap-4 lg:grid-cols-2">
        {/* Transcription horodatée du direct */}
        <section className="console-panel flex flex-col rounded-2xl p-6">
          <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
            {t('questionAgent.transcription.title')}
          </h2>
          {/* TODO : clic sur un time code → retrouver le passage → remonter au son
              enregistré à cet instant. Nécessite un lecteur audio et le flux de
              transcription horodatée — aucun contrat n'existe encore. */}
          <div className="agent-well mt-4 flex flex-1 items-center justify-center rounded-xl p-8">
            <p className="text-center">
              {t('questionAgent.transcription.placeholder')}
              <br />
              {t('questionAgent.transcription.placeholderHint')}
            </p>
          </div>
        </section>

        {/* Questions proposées par le modèle + actions valider / corriger */}
        <section className="flex flex-col gap-4">
          <div className="console-panel flex flex-1 flex-col rounded-2xl p-6">
            <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
              {t('questionAgent.questions.title')}
            </h2>
            {/* TODO : file des questions proposées (4 propositions — I-4) depuis le serveur */}
            <div className="agent-well mt-4 flex flex-1 items-center justify-center rounded-xl p-8">
              <p className="text-center">
                {t('questionAgent.questions.placeholder')}
                <br />
                {t('questionAgent.questions.placeholderHint')}
              </p>
            </div>
          </div>

          {/* Actions : deux gestes BIEN distincts (I-33).
              « Valider » est le verrou d'antenne : il doit rester un geste
              explicite, jamais un défaut, jamais un effet de bord d'un autre clic.
              TODO : modale de confirmation sur « Valider » quand une question
              réelle sera sélectionnée — valider envoie potentiellement à l'antenne. */}
          <div className="grid grid-cols-2 gap-4">
            <button
              type="button"
              disabled
              className="agent-action agent-action--correct min-h-24 rounded-2xl px-5 py-3"
            >
              <span className="block text-2xl font-bold">{t('questionAgent.actions.correct')}</span>
              <span className="agent-action__hint">{t('questionAgent.actions.correctHint')}</span>
            </button>
            <button
              type="button"
              disabled
              className="agent-action agent-action--validate min-h-24 rounded-2xl px-5 py-3"
            >
              <span className="block text-2xl font-bold">{t('questionAgent.actions.validate')}</span>
              <span className="agent-action__hint">{t('questionAgent.actions.validateHint')}</span>
            </button>
          </div>
          <p className="text-center text-sm text-neutral-500">
            {t('questionAgent.actions.disabledReason')}
          </p>
        </section>
      </div>
    </main>
  )
}
