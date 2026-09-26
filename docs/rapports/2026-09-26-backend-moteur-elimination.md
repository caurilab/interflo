# Rapport — Moteur du format élimination

> **Date** : 2026-09-26 — **Agent** : interflo-backend
> **Périmètre** : mission « moteur du format élimination » (cœur du jeu). Mode mesuré DÉSACTIVÉ (EX-36) : aucune mesure de décalage, aucun audio. Transport joueur = **polling HTTP sobre PROVISOIRE** (commenté « en attente de l'ADR temps réel ») ; la mécanique est définitive. Tables PROVISOIRES marquées « PROVISOIRE — docs/07 §5 ». Persistance des réponses PROVISOIRE (docs/07 §5 : « ne pas modéliser la réponse comme une simple ligne insérée » — correcte fonctionnellement, ne prétend pas tenir le pic de ~100 000 écritures).

---

## Fichiers créés / modifiés

**Créés**
- Migrations (7, PROVISOIRES) : `players.population` (+ CHECK studio/home), `game_sessions.pilot_token` (unique), `game_themes`, `questions` (CHECK round_number 1..5, correct_index 0..3, source), `game_rounds` (unique theme+round_number), `round_player_states` (unique round+player, timestamps ms), `game_theme_winners` (unique theme+player)
- Modèles : `GameTheme`, `Question`, `GameRound`, `RoundPlayerState`, `GameThemeWinner`
- Factories + seeders idempotents (garde-fou production) pour chaque modèle ; `RoundPlayerStateSeeder` / `GameThemeWinnerSeeder` volontairement vides (données d'exécution du direct, no-op documentés)
- Exception : `App\Exceptions\AnswerRejectedException` (codes d'erreur stables + statut HTTP + clé de traduction ; rendue en JSON `{message, error, server_time}` dans `bootstrap/app.php`)
- Services : `EliminationSurvivorService` (survivants EX-33, verrouillage EX-32 **déduit**), `EliminationThemeService` (cycle de vie thème/manches, fin de partie I-28), `AnswerService` (contrôles dans l'ordre documenté), `PlayerGameStateService` (état joueur + service de la question, EX-20)
- Middleware : `EnsurePilotToken` (alias `pilot.token`) — ⚠️ auth animateur PROVISOIRE
- Form Requests : `Game/AnswerRequest`, `Pilot/CreatePilotThemeRequest`, `Pilot/CreatePilotRoundRequest` (authorize() non vide)
- Controllers : `Api/PlayStateController`, `Api/RoundAnswerController`, `Api/Pilot/PilotThemeController`, `Api/Pilot/PilotRoundController` (5-15 lignes, logique dans les services)
- Resources : `PlayStateResource`, `Pilot/ThemeResource`, `Pilot/RoundResource`, `Pilot/ThemeStateResource`, `Pilot/WinnerResource`
- Filament : `QuestionResource` (banque de questions) + form/table/pages — action « Valider » explicite (EX-40), badge « NON VALIDÉE », correct_index visible ici uniquement (INV-2)
- Tests : `tests/Feature/Elimination/{EndToEndEliminationTest, AnswerWindowTest, ClockFalsificationTest, LockingTest, EndgameTest, TenantIsolationTest, PilotRoutesTest, NoAnswerLeakTest}`, `tests/Feature/QuestionResourceTest`

**Modifiés**
- `app/Models/Player.php` (+ `population`, `belongsToPopulation()`), `app/Models/GameSession.php` (+ `pilot_token`, génération automatique à la création, `gameThemes()`)
- `config/interflo.php` : + `clock_tolerance_ms`, `pilot_token_length`
- `routes/api.php` : routes joueur (`play/state`, `rounds/{round}/answer`) + pilotage (`pilot/*`)
- `bootstrap/app.php` : alias `pilot.token`, rendu JSON d'`AnswerRejectedException`
- `lang/fr` + `lang/en` (`interflo.php` : `answer.*`, `pilot.*` ; `panel.php` : `questions.*`, `game_sessions.fields.pilot_token`) — les deux locales partent ensemble
- `GameSessionForm` (Filament) : affichage lecture seule du `pilot_token`
- `database/seeders/DatabaseSeeder.php` : chaînage des nouveaux seeders
- `tests/Pest.php` : helpers partagés (`pilotHeaders`, `attachPlayer`, `actingAttachedPlayer`, `playableTheme`, `assertNoCorrectIndexLeak`)

## Tests

**105 verts, 0 régression, 1264 assertions** (`php artisan test --compact`). `vendor/bin/pint` exécuté (⚠️ `--dirty` indisponible : le projet n'est pas un dépôt Git ; Pint ciblé sur les dossiers touchés). Seeders validés sur la base de test (`migrate:fresh --seed`, idempotence vérifiée au second passage).

Couverture mission :
- **CA-01** : partie complète de bout en bout, mode mesuré désactivé — 4 joueurs, 5 manches via les routes pilote et joueur, éliminations par erreur ET par silence, compteurs EX-33 exacts après chaque manche (3, 3, 2, 1, 1), fin de partie, bit `winner` par joueur.
- **CA-02** : refus hors fenêtre (manche jamais ouverte, manche refermée) y compris requête brute d'un client modifié — `403 WINDOW_CLOSED`, rien d'enregistré.
- **CA-03** : horodatage futur lointain et antérieur à `served_at` → `422 IMPOSSIBLE_TIMESTAMP`, rejeté, pas classé ; horodatage dans la tolérance accepté.
- **CA-07** : test qui parcourt **récursivement** toutes les charges utiles joueur (idle, question, answer, answered, erreurs 403/409) et échoue à la moindre clé `correct_index` ; la question servie ne porte que `body` + `propositions`, la réponse que `{correct, server_time}`.
- **Plancher I-30** : réponse à served_at + 100 ms → `422 TOO_FAST` (le droit de répondre n'est pas consommé) ; à floor + marge → acceptée.
- **Verrouillage EX-32** : erreur OU silence dans la fenêtre → verrouillé ; manches suivantes refusées (`403 PLAYER_LOCKED`) même en « répondant juste » ; jamais déverrouillé jusqu'à la fin du thème (état `finished`, `winner: false`).
- **Tirage I-28** : 1/3/5 gagnants parmi les survivants (rang distinct, sans remise, `random_int`), idempotent au second appel, survivants < winners_count → tous gagnent, `winners_count` hors {1,3,5} impossible en base (CHECK), fin refusée tant que les 5 manches ne sont pas clôturées.
- **CA-04** : joueur d'un autre tenant → `403 NOT_ATTACHED` sur les manches voisines, état `idle` ; token pilote d'une session → `404` sur les thèmes d'une autre session.
- **EX-40** : question non validée → impossible de créer un thème ou une manche dessus (`422`, contrôle service), badge « NON VALIDÉE », action « Valider » trace `validated_by`/`validated_at` et disparaît ensuite.

## Contrat exposé (pour le mobile et le web)

### Frontière joueur — Bearer Sanctum + téléphone vérifié (I-8)

Toutes les réponses joueur incluent `server_time` (ISO 8601, sync horloge EX-16 — le client calcule son offset).

**`GET /api/v1/play/state`** → `200 {data: …}` selon l'état :

| `state` | Charge |
|---|---|
| `idle` | `{state, server_time}` |
| `waiting` | `{state, server_time, theme:{id,title}, round_number}` — entre deux manches |
| `question` | `{state, server_time, theme:{id,title}, round:{id, round_number, served_at, window_seconds, question:{body, propositions[4]}}}` — ⚠️ jamais `correct_index` (INV-2). Le 1er poll qui sert la question pose `served_at` : **la fenêtre personnelle démarre là** (EX-20), et n'est jamais réinitialisée aux polls suivants |
| `answered` | `{state, server_time, theme:{id,title}, round_number, correct: bool}` — son propre bit (R-5) |
| `locked` | `{state, server_time, theme:{id,title}}` — verrouillé jusqu'à la fin du thème (EX-32) |
| `finished` | `{state, server_time, theme:{id,title}, winner: bool}` — SON bit, pas la liste (I-28) |

**`POST /api/v1/rounds/{round}/answer`** — body `{answer_index: int 0..3, client_timestamp: int (ms epoch, horodatage du geste, I-6)}`
→ `200 {data: {correct: bool, server_time}}` — UN BIT (R-5), jamais la bonne réponse.

Erreurs — forme `{message, error, server_time}` :

| `error` | HTTP | Cause |
|---|---|---|
| `WINDOW_CLOSED` | 403 | fenêtre non ouverte ou refermée (I-2 / INV-8 / CA-02) |
| `SESSION_ENDED` | 403 | émission terminée (I-16) |
| `NOT_ATTACHED` | 403 | joueur non attaché à la session du thème |
| `POPULATION_MISMATCH` | 403 | studio/domicile — jamais mélangées (I-1 / EX-23) |
| `PLAYER_LOCKED` | 403 | verrouillé jusqu'à la fin du thème (EX-32) |
| `NOT_SERVED` | 403 | question jamais servie à ce joueur (poller `play/state` d'abord) |
| `ALREADY_ANSWERED` | 409 | une seule réponse par manche |
| `IMPOSSIBLE_TIMESTAMP` | 422 | futur au-delà de la tolérance, ou antérieur à served_at (EX-17 / CA-03) |
| `TOO_FAST` | 422 | sous le plancher anti-automatisation (I-30) — **ne consomme pas** le droit de répondre |
| `WINDOW_EXCEEDED` | 403 | fenêtre personnelle dépassée, ou enveloppe serveur couverte (EX-20) |
| — | 401/403/422 | non authentifié / téléphone non vérifié / validation (`answer_index`, `client_timestamp`) |

### Frontière pilotage — ⚠️ auth PROVISOIRE : en-tête `X-Pilot-Token` (token de session, voir Hypothèses)

Une ressource hors de la session pilotée répond **404** (pas d'oracle — R-10). Erreurs métier : `422` avec clé de champ (`first_question_id`, `question_id`, `theme_id`, `round`, `theme`, `winners_count`).

| Route | Body | Succès |
|---|---|---|
| `POST /api/v1/pilot/themes` | `{title, population: 'studio'\|'home', first_question_id}` | `201 {data: {id, game_session_id, title, population, status, current_round: RoundResource}}` — crée le thème + sa manche 1 (question validée exigée, EX-40) |
| `POST /api/v1/pilot/rounds` | `{theme_id, question_id}` | `201 {data: RoundResource}` — manche suivante (numéro déduit, question validée visant cette manche) |
| `POST /api/v1/pilot/rounds/{round}/open` | — | `200 {data: RoundResource}` — ouvre la fenêtre (I-2, état serveur) ; exige les manches précédentes clôturées |
| `POST /api/v1/pilot/rounds/{round}/close` | — | `200 {data: RoundResource}` |
| `GET /api/v1/pilot/themes/{theme}/state` | — | `200 {data: {theme:{id,title,population,status}, current_round:{id,round_number,status,window_opened_at,window_closed_at}\|null, survivors_count, participants_count, server_time}}` — compteur studio (EX-33) |
| `POST /api/v1/pilot/themes/{theme}/finish` | — | `200 {data: [{player_id, rank\|null}]}` — fin de partie (I-28), **idempotent** |

`RoundResource` = `{id, theme_id, round_number, question_id, status: 'pending'|'open'|'closed', window_opened_at, window_closed_at}`. La console pilote **ne reçoit pas non plus** `correct_index` : la bonne réponse n'apparaît que dans le back-office Filament.

## Valeurs paramétrables introduites (points de départ NON VALIDÉS)

| Clé (`config/interflo.php`, env `INTERFLO_*`) | Défaut | Rôle |
|---|---|---|
| `clock_tolerance_ms` | 2000 | Tolérance d'horloge sur l'horodatage client (EX-16/EX-17) |
| `pilot_token_length` | 48 | Longueur du token de pilotage (⚠️ PROVISOIRE) |

Réutilisées, déjà présentes : `anti_automation_floor_ms` (800, I-30), `answer_window_seconds` (10, I-31 — surchargeable par tenant via `tenant_game_configs`), `server_envelope_seconds` (15, EX-20). Scellées PO, non paramétrables : 4 propositions (I-4), 5 manches (I-27), 1/3/5 gagnants (I-28). ⚠️ **Plancher et fenêtre restent deux paramètres distincts** — les deux sont implémentés, aucun ne se substitue à l'autre.

## Hypothèses (à arbitrer — section la plus importante)

1. **⚠️ Auth animateur PROVISOIRE, non spécifiée nulle part** : token opaque par session (`game_sessions.pilot_token`, généré à la création, affiché en lecture seule dans Filament), porté par l'en-tête `X-Pilot-Token`, middleware dédié `pilot.token`. Limites assumées : token en clair en base, aucune rotation, aucune révocation, aucun lien avec l'auth BOS de l'opérateur (I-37). **À signaler au PO** — à remplacer dès arbitrage.
2. **⚠️ Population du joueur (inconnue n°2, recouvrement Voxflo non instruit)** : colonne `players.population` avec défaut `'home'` et CHECK base. **Rien ne permet aujourd'hui de devenir `studio`** — l'attribution reste à concevoir avec l'instruction Voxflo.
3. **Verrouillage EX-32 DÉDUIT, pas matérialisé** : un joueur est verrouillé dès qu'une manche clôturée du thème ne porte pas de verdict correct pour lui (erreur OU silence). Choix documenté dans `EliminationSurvivorService` — l'état ne peut pas diverger des réponses enregistrées. Conséquence assumée : **personne ne rejoint une partie en cours de route** (la manche suivante est « réservée aux survivants »).
4. **Persistance des réponses PROVISOIRE** (docs/07 §5) : une ligne `round_player_states` par joueur/manche, mise à jour au geste. Correcte fonctionnellement ; **ne prétend pas tenir le pic d'écriture** — en attente de l'ADR temps réel. `rejected_reason` = audit minimal du dernier rejet.
5. **Polling HTTP sobre PROVISOIRE** côté joueur (`GET play/state`) — la mécanique est définitive, le transport attend l'ADR temps réel. Effet de bord documenté : le poll sert la question (pose `served_at`).
6. **`questions.theme_id` nullable** : une question peut être rédigée en banque sans thème, puis rattachée quand l'animateur la programme. La validation humaine (EX-40) reste exigée dans tous les cas avant diffusion.
7. **`client_timestamp` en millisecondes epoch entières** (non spécifié) — borne le calcul de fenêtre/floor sans ambiguïté de fuseau.
8. **Rejets non létaux** : une réponse rejetée (TOO_FAST, IMPOSSIBLE_TIMESTAMP, WINDOW_EXCEEDED) ne consomme pas le droit de répondre — seule une réponse acceptée (`answered_at`) verrouille la manche pour le joueur.
9. **Programmation des manches** : autorisée tant qu'aucune fenêtre n'est ouverte (pré-programmation des 5 manches possible) ; seule l'**ouverture** exige la clôture des manches précédentes. Une manche porte la question dont le `round_number` correspond (cohérence banque/manche).
10. **Fenêtre personnelle** : mesurée sur l'horodatage du geste (I-6, on ne pénalise pas les mauvaises connexions) + tolérance d'horloge ; **l'enveloppe serveur** (fenêtre + `server_envelope_seconds` depuis l'ouverture animateur) coupe même un horodatage nominalement dans la fenêtre — les deux contrôles sont implémentés et commentés (EX-20).
11. **Rappel réglementaire (cadrage §10.4)** : le tirage au sort fait basculer le jeu de l'adresse vers le hasard — commenté en migration, modèle et service. À faire figurer chez le juriste.
12. **`winners_count` hors {1,3,5} impossible** : garanti par la contrainte CHECK existante sur `tenant_game_configs` + doublon applicatif dans `finishTheme` (défense en profondeur).
