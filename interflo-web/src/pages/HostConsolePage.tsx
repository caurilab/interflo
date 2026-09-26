import { useCallback, useEffect, useRef, useState } from 'react'
import type { CSSProperties, FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import {
  PilotApiError,
  clearPilotThemeId,
  clearPilotToken,
  closeRound,
  createRound,
  createTheme,
  finishTheme,
  getPilotThemeId,
  getPilotToken,
  getThemeState,
  listQuestions,
  openRound,
  setPilotThemeId,
  setPilotToken,
} from '../api/pilot'
import type { PilotQuestion, PilotThemeState, PilotWinner, Population } from '../api/pilot'
import { apiConfig } from '../config/apiConfig'
import { consoleConfig } from '../config/consoleConfig'
import { createEcho, pilotChannelName } from '../realtime/echo'
import BigActionButton from '../components/BigActionButton'
import BrandLogo from '../components/BrandLogo'
import ConfirmDialog from '../components/ConfirmDialog'
import ConnectionBadge from '../components/ConnectionBadge'
import type { ConnectionStatus } from '../components/ConnectionBadge'
import LanguageSwitcher from '../components/LanguageSwitcher'
import PilotTokenGate from '../components/PilotTokenGate'

/**
 * Actions à effet sur le direct. Chacune passe par une confirmation (F-4) :
 * aucune action destructive n'est atteignable par un toucher accidentel.
 *
 * ⚠️ Écart assumé avec le squelette initial : « RELANCER LE TOUR » était
 * pensé pour le format buzzer (I-7). Le format élimination n'a pas de
 * relance — c'est le buzzer qui en a une. Le bouton est remplacé par
 * « MANCHE SUIVANTE » (POST pilot/rounds).
 */
type PendingAction = 'openWindow' | 'closeWindow' | 'nextRound' | 'finishGame'

/**
 * CONSOLE ANIMATEUR (tablette) — câblée sur l'API de pilotage réelle.
 *
 * Contrat consommé : docs/rapports/2026-09-26-backend-moteur-elimination.md
 * (frontière pilotage, auth PROVISOIRE par X-Pilot-Token).
 *
 * Invariants de direct appliqués :
 * - F-1 : l'état de la fenêtre affiché est l'ÉTAT SERVEUR (polling sobre de
 *   themes/{id}/state, intervalle apiConfig.statePollIntervalMs) — aucune
 *   transition n'est affichée sur la base d'un état local optimiste : après
 *   chaque action, l'écran attend le prochain état lu sur le serveur ;
 * - F-2 : le badge passe au rouge dès qu'une requête échoue, vert au retour ;
 * - F-4 : ouvrir / fermer / manche suivante / terminer passent par une
 *   confirmation, et un bouton désactivé ne l'ouvre jamais ;
 * - F-6 : jamais de liste de répondants — compteurs + gagnants uniquement.
 */
export default function HostConsolePage() {
  const [token, setToken] = useState<string | null>(() => getPilotToken())

  if (token === null) {
    return (
      <PilotTokenGate
        onToken={(newToken) => {
          setPilotToken(newToken)
          setToken(newToken)
        }}
      />
    )
  }

  return (
    <HostConsoleLive
      token={token}
      onResetToken={() => {
        clearPilotToken()
        setToken(null)
      }}
    />
  )
}

function HostConsoleLive({ token, onResetToken }: { token: string; onResetToken: () => void }) {
  const { t } = useTranslation()

  // Thème piloté : mémorisé en sessionStorage pour survivre à un refresh de
  // la tablette en plein direct (le contrat n'a aucune route de liste).
  const [themeId, setThemeId] = useState<number | null>(() => getPilotThemeId())
  const [themeState, setThemeState] = useState<PilotThemeState | null>(null)

  // F-2 : tant qu'aucune requête n'a réussi, la console est « non connectée ».
  const [linkStatus, setLinkStatus] = useState<ConnectionStatus>('unauthorized')
  const [actionError, setActionError] = useState<string | null>(null)

  const [pendingAction, setPendingAction] = useState<PendingAction | null>(null)
  const [actionInFlight, setActionInFlight] = useState(false)
  const [nextQuestionId, setNextQuestionId] = useState('')
  const [winners, setWinners] = useState<PilotWinner[] | null>(null)

  // Formulaire de création de partie.
  const [newTitle, setNewTitle] = useState('')
  const [newPopulation, setNewPopulation] = useState<Population>('home')
  const [newFirstQuestionId, setNewFirstQuestionId] = useState('')
  const [createInFlight, setCreateInFlight] = useState(false)

  // Banque de questions validées (sélecteur, remplace le champ d'id manuel).
  const [questions, setQuestions] = useState<PilotQuestion[]>([])

  const inFlightRef = useRef(false)

  /** Traduit un échec d'appel en état de lien + message d'alerte. */
  const handleApiFailure = useCallback(
    (error: unknown) => {
      if (error instanceof PilotApiError) {
        if (error.isAuthFailure) {
          // 401/404 : jeton refusé ou ressource hors session — console non connectée.
          setLinkStatus('unauthorized')
          setActionError(t('hostConsole.errors.auth'))
        } else if (error.status === null) {
          setLinkStatus('offline')
          setActionError(t('hostConsole.errors.network'))
        } else {
          // Erreur métier (422…) : le lien est bon, on affiche le message serveur.
          setLinkStatus('online')
          setActionError(error.message)
        }
      } else {
        setLinkStatus('offline')
        setActionError(t('hostConsole.errors.unknown'))
      }
    },
    [t],
  )

  /** Lecture de l'état serveur — seule source de vérité de l'écran (F-1). */
  const refreshState = useCallback(async () => {
    if (themeId === null || inFlightRef.current) return
    inFlightRef.current = true
    try {
      const state = await getThemeState(token, themeId)
      setThemeState(state)
      setLinkStatus('online')
      setActionError(null)
    } catch (error) {
      if (error instanceof PilotApiError && error.isAuthFailure) {
        setLinkStatus('unauthorized')
      } else {
        // F-2 : rouge immédiat — le dernier état connu reste affiché mais
        // le badge rouge l'invalide visuellement.
        setLinkStatus('offline')
      }
    } finally {
      inFlightRef.current = false
    }
  }, [themeId, token])

  /** Recharge la banque de questions validées (confort de sélection). */
  const reloadQuestions = useCallback(async () => {
    try {
      setQuestions(await listQuestions(token))
    } catch {
      setQuestions([])
    }
  }, [token])

  // Polling sobre de l'état du thème — transport PROVISOIRE en attendant
  // l'ADR temps réel (intervalle paramétrable, point de départ non validé).
  useEffect(() => {
    if (themeId === null) return
    // Première lecture différée d'un tick : hors du corps synchrone de
    // l'effet (le poll pose l'état serveur dans le state React).
    const first = setTimeout(() => void refreshState(), 0)
    const timer = setInterval(() => void refreshState(), apiConfig.statePollIntervalMs)
    return () => {
      clearTimeout(first)
      clearInterval(timer)
    }
  }, [themeId, refreshState])

  // Recharge la banque de questions quand le formulaire de création s'affiche.
  useEffect(() => {
    if (themeId !== null) return
    // Lecture différée d'un tick : hors du corps synchrone de l'effet.
    const timer = setTimeout(() => void reloadQuestions(), 0)
    return () => clearTimeout(timer)
  }, [themeId, reloadQuestions])

  const connected = linkStatus === 'online'
  const currentRound = themeState?.current_round ?? null
  const themeFinished = themeState?.theme.status === 'finished'

  // Push temps réel (D-002 §4.2) : dès que la session est connue, on s'abonne
  // au canal pilote. À la réception de `pilot.state`, l'état affiché est mis
  // à jour immédiatement (le polling reste le transport dégradé D-1).
  useEffect(() => {
    const sessionId = themeState?.session_id
    if (sessionId === undefined) return

    let echo: ReturnType<typeof createEcho> | null = null
    try {
      echo = createEcho()
      echo.channel(pilotChannelName(sessionId)).listen('.pilot.state', (state: PilotThemeState) => {
        setThemeState(state)
        setLinkStatus('online')
      })
    } catch {
      // D-1 : le push échoue → on reste sur le polling (transport dégradé).
      // Le push est un accélérateur de latence, jamais un requis : ne pas
      // faire tomber la console si le WebSocket est injoignable.
      echo = null
    }

    return () => {
      echo?.leaveChannel(pilotChannelName(sessionId))
      echo?.disconnect()
    }
  }, [themeState?.session_id])

  // Disponibilité des actions : état SERVEUR uniquement (jamais optimiste).
  const canOpen = connected && !themeFinished && currentRound?.status === 'pending'
  const canClose = connected && !themeFinished && currentRound?.status === 'open'
  // Manche suivante : grisée tant que la fenêtre courante n'est pas fermée.
  const canNextRound = connected && !themeFinished && currentRound?.status === 'closed'
  const canFinish = connected && themeState !== null && !themeFinished

  /** Exécute l'action confirmée puis relit l'état serveur (F-1). */
  const runAction = async (action: () => Promise<unknown>) => {
    setActionInFlight(true)
    setActionError(null)
    try {
      await action()
      await refreshState()
    } catch (error) {
      handleApiFailure(error)
    } finally {
      setActionInFlight(false)
      setPendingAction(null)
    }
  }

  const handleConfirm = () => {
    if (actionInFlight) return
    switch (pendingAction) {
      case 'openWindow':
        if (currentRound) void runAction(() => openRound(token, currentRound.id))
        break
      case 'closeWindow':
        if (currentRound) void runAction(() => closeRound(token, currentRound.id))
        break
      case 'nextRound': {
        const questionId = Number.parseInt(nextQuestionId, 10)
        if (themeId !== null && Number.isInteger(questionId) && questionId > 0) {
          void runAction(() => createRound(token, themeId, questionId))
          setNextQuestionId('')
          void reloadQuestions()
        }
        break
      }
      case 'finishGame':
        if (themeId !== null) {
          void runAction(async () => {
            setWinners(await finishTheme(token, themeId))
          })
        }
        break
      case null:
        break
    }
  }

  const handleCreateTheme = (event: FormEvent) => {
    event.preventDefault()
    const firstQuestionId = Number.parseInt(newFirstQuestionId, 10)
    if (newTitle.trim() === '' || !Number.isInteger(firstQuestionId) || firstQuestionId <= 0) return
    setCreateInFlight(true)
    setActionError(null)
    createTheme(token, {
      title: newTitle.trim(),
      population: newPopulation,
      firstQuestionId,
    })
      .then((theme) => {
        // Première requête réussie : le jeton est validé de fait.
        setLinkStatus('online')
        setWinners(null)
        setPilotThemeId(theme.id)
        setThemeId(theme.id)
        void reloadQuestions()
      })
      .catch(handleApiFailure)
      .finally(() => setCreateInFlight(false))
  }

  const handleNewGame = () => {
    clearPilotThemeId()
    setThemeId(null)
    setThemeState(null)
    setWinners(null)
    setNewTitle('')
    setNewFirstQuestionId('')
  }

  const windowStateKey = themeFinished
    ? 'finished'
    : (currentRound?.status ?? 'none')

  return (
    <main className="flex min-h-full flex-col gap-4 p-4">
      {/* En-tête : état du lien visible en permanence (F-2) */}
      <header className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <BrandLogo className="h-8" />
          <div>
            <h1 className="text-2xl font-bold">{t('hostConsole.title')}</h1>
            <p className="text-sm text-neutral-400">{t('hostConsole.subtitle')}</p>
          </div>
        </div>
        <ConnectionBadge status={linkStatus} />
        <LanguageSwitcher />
        <button
          type="button"
          onClick={onResetToken}
          className="console-back rounded-lg px-4 py-2 text-sm"
        >
          {t('hostConsole.changeToken')}
        </button>
        <Link to="/" className="console-back rounded-lg px-4 py-2 text-sm">
          {t('common.back')}
        </Link>
      </header>

      {/* Rappel F-5 : enveloppe à meubler — valeur lue depuis la config, jamais en dur.
          Bandeau cream plein : l'élément le plus visible après les actions. */}
      <aside className="filler-banner rounded-xl px-6 py-3 text-center">
        <p className="text-xl font-semibold">
          {t('hostConsole.fillerReminder.title', {
            seconds: consoleConfig.fillerEnvelopeSeconds,
          })}
        </p>
        <p className="filler-banner__detail text-sm">{t('hostConsole.fillerReminder.detail')}</p>
      </aside>

      {actionError && (
        <div
          role="alert"
          className="offline-alert rounded-xl px-6 py-3 text-center text-lg font-semibold"
        >
          {actionError}
          <button
            type="button"
            onClick={() => setActionError(null)}
            className="offline-alert__dismiss ml-4 rounded-lg px-3 py-1 text-sm"
          >
            {t('common.ok')}
          </button>
        </div>
      )}

      {themeId === null ? (
        /* ─── Création de partie (POST pilot/themes) ─── */
        <section className="mx-auto flex w-full max-w-2xl flex-col gap-4">
          <form
            onSubmit={handleCreateTheme}
            className="console-panel flex flex-col gap-5 rounded-2xl p-8"
          >
            <div>
              <h2 className="text-xl font-bold">{t('hostConsole.createTheme.title')}</h2>
              <p className="mt-1 text-sm text-neutral-400">
                {t('hostConsole.createTheme.intro')}
              </p>
            </div>

            <label className="flex flex-col gap-2">
              <span className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.createTheme.fieldTitle')}
              </span>
              <input
                type="text"
                value={newTitle}
                onChange={(event) => setNewTitle(event.target.value)}
                maxLength={255}
                className="console-input min-h-14 rounded-xl px-4 text-lg"
              />
            </label>

            {/* I-1 : une partie concerne UNE population — jamais les deux. */}
            <fieldset>
              <legend className="console-panel__title mb-2 text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.createTheme.fieldPopulation')}
              </legend>
              <div className="grid grid-cols-2 gap-3">
                <label className="population-option flex min-h-16 cursor-pointer items-center justify-center rounded-xl text-xl font-semibold">
                  <input
                    type="radio"
                    name="population"
                    value="home"
                    className="sr-only"
                    checked={newPopulation === 'home'}
                    onChange={() => setNewPopulation('home')}
                  />
                  {t('hostConsole.population.home')}
                </label>
                <label className="population-option flex min-h-16 cursor-pointer items-center justify-center rounded-xl text-xl font-semibold">
                  <input
                    type="radio"
                    name="population"
                    value="studio"
                    className="sr-only"
                    checked={newPopulation === 'studio'}
                    onChange={() => setNewPopulation('studio')}
                  />
                  {t('hostConsole.population.studio')}
                </label>
              </div>
            </fieldset>

            <label className="flex flex-col gap-2">
              <span className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.createTheme.fieldFirstQuestionId')}
              </span>
              <select
                value={newFirstQuestionId}
                onChange={(event) => setNewFirstQuestionId(event.target.value)}
                className="console-input min-h-14 rounded-xl px-4 text-lg"
              >
                <option value="">
                  {questions.filter((q) => q.round_number === 1).length === 0
                    ? t('hostConsole.questionPicker.empty')
                    : t('hostConsole.questionPicker.placeholder')}
                </option>
                {questions
                  .filter((q) => q.round_number === 1)
                  .map((q) => (
                    <option key={q.id} value={String(q.id)}>
                      {q.body}
                    </option>
                  ))}
              </select>
            </label>

            <button
              type="submit"
              disabled={createInFlight || newTitle.trim() === '' || newFirstQuestionId.trim() === ''}
              className="token-gate__submit min-h-16 rounded-xl text-xl font-bold"
            >
              {createInFlight
                ? t('hostConsole.createTheme.submitting')
                : t('hostConsole.createTheme.submit')}
            </button>
          </form>
        </section>
      ) : (
        /* ─── Pilotage du thème en cours ─── */
        <div className="grid flex-1 grid-cols-1 gap-4 lg:grid-cols-2">
          {/* Colonne gauche : état SERVEUR et compteurs réels */}
          <section className="flex flex-col gap-4">
            {/* État de la fenêtre : état serveur, jamais optimiste (F-1).
                La classe window-panel--* ne change qu'avec l'état SERVEUR —
                le sweep lumineux ne rejoue jamais sur un tick de polling stable. */}
            <div className={`console-panel window-panel window-panel--${windowStateKey} rounded-2xl p-6`}>
              <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.windowState.title')}
              </h2>
              {themeState === null ? (
                <p className="host-console__placeholder-value mt-2 text-4xl font-extrabold">
                  {t('common.notConnected')}
                </p>
              ) : (
                <>
                  <p
                    className={`window-state window-state--${windowStateKey} mt-2 text-4xl font-extrabold`}
                  >
                    {t(`hostConsole.windowState.values.${windowStateKey}`)}
                  </p>
                  <p className="mt-1 text-lg text-neutral-300">
                    {currentRound
                      ? t('hostConsole.windowState.round', { number: currentRound.round_number })
                      : t('hostConsole.windowState.noRound')}
                    {' · '}
                    {themeState.theme.title}
                    {' · '}
                    {t(`hostConsole.population.${themeState.theme.population}`)}
                  </p>
                </>
              )}
            </div>

            {/* Compteurs réels : participations et survivants (EX-33) —
                jamais la liste des répondants (F-6) */}
            <div className="grid grid-cols-2 gap-4">
              <div className="console-panel rounded-2xl p-6 text-center">
                <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                  {t('hostConsole.counters.participations')}
                </h2>
                {/* key = valeur : le remount déclenche le tick UNIQUEMENT au
                    changement de valeur, jamais sur un poll stable (D-001 §3) */}
                <p
                  key={themeState === null ? 'idle' : themeState.participants_count}
                  className="counter-tick mt-2 text-5xl font-extrabold"
                >
                  {themeState === null ? '—' : themeState.participants_count}
                </p>
              </div>
              <div className="console-panel rounded-2xl p-6 text-center">
                <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                  {t('hostConsole.counters.survivors')}
                </h2>
                {/* EX-33 : le chiffre qui descend à l'écran après chaque manche —
                    tick animé au changement de valeur (key = valeur) */}
                <p
                  key={themeState === null ? 'idle' : themeState.survivors_count}
                  className="counter-survivors counter-tick mt-2 text-5xl font-extrabold"
                >
                  {themeState === null ? '—' : themeState.survivors_count}
                </p>
              </div>
            </div>

            {/* Résultat de la partie (I-28). F-6 : les ids retournés par finish
                ne sont PAS une liste de répondants — affichage sobre du compte
                et des rangs, choix documenté dans le rapport. */}
            <div className="console-panel flex-1 rounded-2xl p-6">
              <h2 className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.gameResult.title')}
              </h2>
              {winners !== null ? (
                <>
                  <p className="counter-survivors counter-tick mt-2 text-4xl font-extrabold">
                    {t('hostConsole.gameResult.winnersCount', { count: winners.length })}
                  </p>
                  <ul className="mt-3 flex flex-wrap gap-2">
                    {winners.map((winner, index) => (
                      <li
                        key={winner.player_id}
                        className="winner-chip rounded-lg px-3 py-1 text-sm"
                        // Cascade d'entrée : index lu par _host-console.scss
                        // (animation-delay). Les chips n'existent qu'après
                        // finish — le polling ne les remounte jamais.
                        style={{ '--winner-chip-index': index } as CSSProperties}
                      >
                        {winner.rank !== null
                          ? t('hostConsole.gameResult.winnerRank', {
                              rank: winner.rank,
                              id: winner.player_id,
                            })
                          : t('hostConsole.gameResult.winnerNoRank', { id: winner.player_id })}
                      </li>
                    ))}
                  </ul>
                </>
              ) : (
                <p className="host-console__placeholder-value mt-2 text-lg">
                  {themeFinished
                    ? t('hostConsole.gameResult.finishedElsewhere')
                    : t('hostConsole.gameResult.placeholder')}
                </p>
              )}
              {themeFinished && (
                <button
                  type="button"
                  onClick={handleNewGame}
                  className="console-back mt-4 rounded-lg px-4 py-2 text-sm"
                >
                  {t('hostConsole.gameResult.newGame')}
                </button>
              )}
            </div>
          </section>

          {/* Colonne droite : actions — gros boutons, toutes confirmées (F-3, F-4) */}
          <section className="flex flex-col justify-center gap-5">
            <BigActionButton
              label={t('hostConsole.actions.openWindow.label')}
              hint={t('hostConsole.actions.openWindow.hint')}
              tone="energy"
              disabled={!canOpen || actionInFlight}
              onPress={() => setPendingAction('openWindow')}
            />
            <BigActionButton
              label={t('hostConsole.actions.closeWindow.label')}
              hint={t('hostConsole.actions.closeWindow.hint')}
              tone="firm"
              disabled={!canClose || actionInFlight}
              onPress={() => setPendingAction('closeWindow')}
            />
            {/* Format élimination : pas de relance (c'est le buzzer) —
                « MANCHE SUIVANTE » remplace « RELANCER LE TOUR ». */}
            <BigActionButton
              label={t('hostConsole.actions.nextRound.label')}
              hint={t('hostConsole.actions.nextRound.hint')}
              tone="distinct"
              disabled={!canNextRound || actionInFlight}
              onPress={() => setPendingAction('nextRound')}
            />
            <BigActionButton
              label={t('hostConsole.actions.finishGame.label')}
              hint={t('hostConsole.actions.finishGame.hint')}
              tone="firm"
              disabled={!canFinish || actionInFlight}
              onPress={() => setPendingAction('finishGame')}
            />
          </section>
        </div>
      )}

      {/* Modale de confirmation systématique sur les actions à effet (F-4) */}
      {pendingAction && (
        <ConfirmDialog
          open
          title={t(`hostConsole.confirm.${pendingAction}.title`)}
          confirmLabel={
            actionInFlight
              ? t('hostConsole.confirm.inFlight')
              : t(`hostConsole.confirm.${pendingAction}.confirm`)
          }
          confirmDisabled={actionInFlight || (pendingAction === 'nextRound' && nextQuestionId.trim() === '')}
          onConfirm={handleConfirm}
          onCancel={() => {
            if (!actionInFlight) setPendingAction(null)
          }}
        >
          <p>{t(`hostConsole.confirm.${pendingAction}.body`)}</p>
          {pendingAction === 'nextRound' && (
            <label className="mt-4 flex flex-col gap-2">
              <span className="console-panel__title text-sm font-semibold uppercase tracking-widest">
                {t('hostConsole.confirm.nextRound.questionIdLabel')}
              </span>
              <select
                value={nextQuestionId}
                onChange={(event) => setNextQuestionId(event.target.value)}
                autoFocus
                className="console-input min-h-14 rounded-xl px-4 text-lg"
              >
                <option value="">
                  {questions.filter((q) => q.round_number === (currentRound?.round_number ?? 0) + 1).length === 0
                    ? t('hostConsole.questionPicker.empty')
                    : t('hostConsole.questionPicker.placeholder')}
                </option>
                {questions
                  .filter((q) => q.round_number === (currentRound?.round_number ?? 0) + 1)
                  .map((q) => (
                    <option key={q.id} value={String(q.id)}>
                      {q.body}
                    </option>
                  ))}
              </select>
            </label>
          )}
        </ConfirmDialog>
      )}
    </main>
  )
}
