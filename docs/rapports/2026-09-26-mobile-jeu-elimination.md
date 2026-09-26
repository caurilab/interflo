# Rapport — Application mobile : jeu élimination (parcours joueur)

> **Date** : 2026-09-26 — **Agent** : interflo-mobile
> **Périmètre** : mission « câbler le jeu élimination dans l'app mobile » (interflo-app), selon le contrat réellement livré par le backend ([rapport moteur élimination](2026-09-26-backend-moteur-elimination.md)). Transport = **polling HTTP sobre PROVISOIRE** (commenté « en attente de l'ADR temps réel » côté code). Toute valeur numérique non scellée est un point de départ paramétrable, conforme à PROMPT-DEMARRAGE.md §2.

---

## Fichiers créés / modifiés

**Créés**
- `src/game/clockSync.ts` — `ServerClock` : offset serveur↔appareil (I-6 / EX-16)
- `src/game/usePlayStateMachine.ts` — machine à états de jeu pilotée par `GET /api/v1/play/state`
- `src/screens/PlayScreen.tsx` — conteneur unique qui rend la vue selon l'état serveur
- `src/screens/LockedScreen.tsx` — écran « Éliminé pour ce thème » (EX-32, ton encourageant M-4)
- `__tests__/clockSync.test.ts` — tests de l'estimation d'offset

**Modifiés**
- `src/api/types.ts` — types `PlayState` (union des 6 états : `idle`, `waiting`, `question`, `answered`, `locked`, `finished`), `PlayRound`, `AnswerResponse`, codes d'erreur stables — calqués sur `PlayerGameStateService` et `AnswerRejectedException`
- `src/api/client.ts` — `ApiError` enrichi : `code?` et `serverTime?` parsés du corps d'erreur `{message, error, server_time}` (même une erreur étalonne l'horloge, EX-16)
- `src/api/endpoints.ts` — + `getPlayState(token)`, `submitRoundAnswer(roundId, answerIndex, clientTimestampMs, token)`
- `src/config/gameConfig.ts` — + `DEFAULTS.pollIntervalMs: 2000` (point de départ non validé, commenté), `pollFailureThreshold: 3`
- `src/screens/WaitingScreen.tsx` — couvre `idle` ET `waiting` (entre-deux-manches), bouton quitter
- `src/screens/GameScreen.tsx` — question réelle + 4 propositions (I-4), notices de rejet, verrouillage des boutons
- `src/screens/FeedbackScreen.tsx` — verdict juste/faux (I-29) + « La suite arrive »
- `src/screens/SessionEndScreen.tsx` — fin de partie sur le bit `winner` (I-28)
- Navigation : route unique `Play` remplace les routes Waiting/Game/Feedback/SessionEnd ; `PairingConfirmationScreen` et `PhoneCodeScreen` naviguent vers `Play`
- i18n `fr` + `en` synchronisées (les deux locales partent ensemble) ; anciennes clés placeholder supprimées

## Tests

- **`tsc --noEmit` vert**, **10/10 tests Jest verts** (`clockSync`, `gameConfig`, `i18n`), **lint 0 erreur**.
- Couverture : estimation d'offset (meilleur échantillon au plus petit RTT, rejet d'un `server_time` illisible, repli sur l'horloge brute sans échantillon), cohérence des clés i18n fr/en, constantes scellées (4 propositions, 5 manches, 1/3/5 gagnants).

## Contrat consommé

- `GET /api/v1/play/state` (Bearer joueur) : `{state, server_time, theme?, round?{id, round_number, served_at, window_seconds, question{body, propositions}}, round_number?, correct?, winner?}`. `correct_index` ne transite jamais (INV-2 vérifié côté payloads reçus).
- `POST /api/v1/rounds/{round}/answer` : `{answer_index, client_timestamp}` → `{correct, server_time}` ; erreurs `{message, error, server_time}` avec codes stables (`TOO_FAST`, `IMPOSSIBLE_TIMESTAMP`, `WINDOW_EXCEEDED`, `WINDOW_CLOSED`, …).
- Routes pilote utilisées pour le scénario E2E : `POST /api/v1/pilot/rounds/{id}/open|close`, `POST /api/v1/pilot/themes/{id}/finish` (X-Pilot-Token).

## Polling et offset d'horloge (ce que le code fait)

- **Polling** : `setTimeout` récursif à `pollIntervalMs` (2 s) — jamais deux requêtes en vol. Ne tourne que si l'écran de jeu est monté (session attachée) **et** l'app au premier plan (`AppState`) ; arrêt propre en arrière-plan, **poll immédiat au retour** (la fenêtre peut s'être ouverte entre-temps). Après `pollFailureThreshold` (3) échecs consécutifs : bandeau « connexion perdue » non bloquant, les tentatives continuent.
- **Effet de bord connu du contrat** : le premier poll qui trouve une fenêtre ouverte **sert** la question (pose `served_at`, EX-20) — le poll est donc aussi le signal d'ouverture (M-3).
- **Offset (I-6/EX-16)** : à chaque réponse portant `server_time`, offset = `server_time − milieu(t0, t1)` ; on conserve l'échantillon au **plus petit aller-retour** (un seul échantillon de référence, pas de moyenne, pas de compensation de dérive). `nowMs()` horodate le geste avec l'horloge corrigée ; sans échantillon, repli sur l'horloge appareil brute. La tolérance (`clock_tolerance_ms`, 2 000 ms) est gérée côté serveur (EX-17) : l'estimation n'a pas à être parfaite.
- **Réponse** : horodatage capturé **au geste** (jamais à l'arrivée, I-6) ; verdict local posé dès le 200 pour un feedback immédiat, confirmé par le poll suivant (`answered`).
- **Rejets** : non létaux (`TOO_FAST`, `IMPOSSIBLE_TIMESTAMP`, réseau coupé — le droit de répondre n'est pas consommé côté serveur) → notice + déverrouillage, le joueur retente ; définitifs (`WINDOW_EXCEEDED`, `ALREADY_ANSWERED`, `PLAYER_LOCKED`, …) → notice + boutons verrouillés jusqu'au poll qui fait avancer l'état. `401/403` **sans** code métier = perte d'authentification → token local effacé, retour au flux d'appairage.

## Captures (simulateur iPhone 16, API locale réelle)

Scénario : appairage complet dans l'UI (code RNPTPQ → téléphone → OTP lu dans les logs) → attache session « Émission de démonstration » → pilotage des manches par curl avec le pilot_token.

- [Attente avant ouverture](assets/2026-09-26-gameplay-waiting.png) — `waiting`, pastille chaîne/émission
- [Question servie manche 1](assets/2026-09-26-gameplay-question.png) — `question`, 4 propositions réelles
- [Rejet fenêtre dépassée](assets/2026-09-26-gameplay-rejection-window.png) — notice `window_exceeded`, boutons verrouillés, sans dramatiser
- [Éliminé pour ce thème](assets/2026-09-26-gameplay-locked.png) — `locked` (EX-32) après clôture de la manche sans verdict correct
- [Bonne réponse](assets/2026-09-26-gameplay-feedback-correct.png) — feedback immédiat, `is_correct=true` confirmé en base
- [Raté pour celle-ci](assets/2026-09-26-gameplay-feedback-wrong.png) — verdict faux, `is_correct=false` confirmé en base
- [Tu es gagnant !](assets/2026-09-26-gameplay-finished.png) — `finished` avec `winner=true` (I-28)

La bannière jaune visible en bas des captures est le LogBox Metro (warning connu `ReactNativeFeatureFlags` sous pnpm) — sans rapport avec le code livré.

## Démontré de bout en bout (app réelle ↔ API réelle)

- Appairage complet (code court → confirmation → téléphone → OTP → attache) aboutissant à l'écran de jeu.
- Transition `waiting → question` par polling après `open` (la question réelle arrive sans action utilisateur).
- Geste → `POST answer` horodaté au geste → verdict serveur `correct`/`false` en base (`round_player_states`) → feedback immédiat.
- Rejet `WINDOW_EXCEEDED` : notice affichée + `rejected_reason` en base + boutons verrouillés.
- Élimination (EX-32) : clôture d'une manche sans verdict correct → état `locked` au poll suivant.
- Fin de partie (I-28) : `finish` → état `finished` + bit `winner` → écran de fin.

## Non démontré / limites du test

- **Variante « perdant » de l'écran de fin** non capturée (le joueur de démo a fini gagnant) ; le composant est le même, piloté par le bit `winner`.
- **Rejets TOO_FAST / IMPOSSIBLE_TIMESTAMP / réseau** non rejoués en E2E (chemins câblés, non exercés sur device).
- **Bandeau « connexion perdue »** non déclenché en conditions réelles.
- **Timing du geste** : les taps ont été injectés par CGEvent (clics synthétiques calibrés) — pas un doigt humain ; la latence perceptible réelle reste à mesurer.
- ⚠️ **Rewind de démo** : la fenêtre serveur est de **10 s** (défaut `answer_window_seconds`, config tenant vide) avec une **enveloppe comptée depuis l'ouverture de la manche** (`window_opened_at + window + server_envelope_seconds`, AnswerService §10bis) — mes taps hors délai ont été rejetés `window_exceeded`, ce qui a éliminé le joueur (règle EX-32 réelle). Pour poursuivre le scénario, j'ai marqué en base (tinker) des verdicts corrects sur les manches 1, 3 et 4 du Thème Démo. C'est une manipulation du harnais de test, **consignée** ; les parcours application (question → geste → verdict → feedback, rejet, locked, finished) sont tous passés par l'API réelle sans modification.

## Consommation de données (R-11)

Estimation sur les payloads observés : `play/state` `idle`/`waiting` ≈ 150-250 B, `question` ≈ 400-600 B (corps JSON). À 2 s : ~30 polls/min ≈ 5-18 Ko/min en corps, ~35-70 Ko/min avec en-têtes HTTP (Bearer). Un `answer` ≈ 250 B aller + ~200 B retour. Le polling ne tourne que session attachée **et** premier plan. Chiffres à revalider lors de l'ADR temps réel.

## Ce que j'ai dû supposer

1. **Conteneur d'état plutôt que navigation** : les écrans de jeu sont rendus par une route unique `Play` pilotée par l'état serveur — la navigation classique créerait des courses avec le polling (un poll qui change d'état pendant une transition de navigation). C'est le choix le plus défendable, mais il fige l'architecture d'écrans.
2. **Pas de compte à rebours local de fenêtre affiché** : l'horloge appareil n'est pas fiable et le serveur borne de toute façon (EX-17). Le joueur voit « fenêtre ouverte » sans décompte. **À arbitrer PO** : la maquette D-001 suggère une pression temporelle visible ; si un décompte est voulu, il doit être rendu depuis `served_at + window_seconds` servis par le contrat (déjà disponibles dans le payload `question`).
3. **Offset = meilleur échantillon au plus petit RTT, sans lissage** : simple, robuste aux pics de latence ; ne compense pas une dérive lente de l'horloge appareil entre deux sessions. L'ADR temps réel remplacera probablement cette heuristique.
4. **Rejets TOO_FAST / IMPOSSIBLE_TIMESTAMP / réseau → déverrouillage local** : je me fie à la documentation backend « le droit de répondre n'est pas consommé » ; si ce n'était pas vrai pour un de ces codes, le joueur déverrouillé recevrait un rejet définitif à la retente — dégradation gracieuse, pas de crash.
5. **`WaitingScreen` couvre `idle` et `waiting`** : les deux états signifient « rien à jouer maintenant » ; le sous-titre différencie « pas encore de partie » / « entre deux manches » (avec numéro de manche quand fourni).
6. **`SessionEndScreen` = placeholder `finished`** : un bit `winner` seulement, pas de liste des gagnants (I-28 « un bit, pas la liste ») ; le cadrage final de l'écran de fin est un TODO produit (§16 q17).
7. **401/403 sans code métier = perte d'auth** : un rejet de jeu porte toujours un code stable ; sans code, c'est le token qui est mort → retour appairage. Distinction documentée dans le code.
8. **E2E piloté par clics CGEvent (Swift)** : System Events se bloque par intermittence sur cette machine ; les coordinates ont été calibrées par mesure de pixels (fenêtre simulateur à (396,93), échelle ≈0,995).
9. **Fenêtre de 10 s supposée non représentative du direct** : le défaut `answer_window_seconds` (10 s) + enveloppe courte rendent le jeu quasi injouable à cadence humaine en l'état — le réglage par tenant (Filament) est prévu côté backend ; valeur réelle à arrêter avec le PO.
