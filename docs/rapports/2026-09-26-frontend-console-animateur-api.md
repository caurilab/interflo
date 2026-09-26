# Rapport — Console animateur câblée sur l'API de pilotage

> **Date** : 2026-09-26 — **Agent** : interflo-frontend
> **Périmètre** : mission « câbler la console animateur sur l'API réelle ». Contrat consommé tel quel : [2026-09-26-backend-moteur-elimination.md](2026-09-26-backend-moteur-elimination.md), section « Contrat exposé — frontière pilotage ». Console agent questions : **inchangée** (son contrat n'existe pas encore). Design D-001 appliqué ; i18n fr+en complet ; aucun texte en dur ; styles en partials SCSS (un composant = un partial).

---

## Écrans créés / modifiés

**Créés**
- `src/api/pilot.ts` — client HTTP typé de la frontière pilotage (les 6 routes du contrat, enveloppe `{data}`, erreur typée `PilotApiError` avec `isAuthFailure` = 401/404), stockage du jeton et du thème courant en `sessionStorage`.
- `src/config/apiConfig.ts` — **point unique** de la base URL (`http://127.0.0.1:8000`, commenté « point de départ dev ») et de l'intervalle de polling (2 s, commenté « point de départ NON VALIDÉ »). Aucune URL en dur ailleurs.
- `src/components/PilotTokenGate.tsx` + `src/styles/_pilot-token-gate.scss` — écran de saisie du `X-Pilot-Token` (champ `password`, jamais réaffiché, avertissement « auth PROVISOIRE » visible à l'écran).

**Modifiés**
- `src/pages/HostConsolePage.tsx` — de squelette visuel à console connectée : gate jeton → création de partie → pilotage (état serveur, compteurs, 4 actions confirmées, résultat).
- `src/components/ConnectionBadge.tsx` + `_connection-badge.scss` — 3 états mesurés sur des requêtes réelles : `online` (vert), `offline` (rouge), `unauthorized` (rouge, défaut — la console agent questions conserve son comportement actuel sans modification).
- `src/components/BigActionButton.tsx` + `_big-action-button.scss` — prop `disabled` (bouton inactif visiblement inactif, ne passe jamais à la confirmation — F-4).
- `src/components/ConfirmDialog.tsx` + `_confirm-dialog.scss` — prop optionnelle `confirmDisabled` (action en vol / saisie incomplète).
- `src/styles/_variables.scss` — tokens `$color-success` / `$color-success-deep` (lien établi, fenêtre ouverte).
- `src/styles/_host-console.scss` — états de fenêtre colorés, compteur survivants cream (EX-33), chips gagnants, champs de formulaire.
- `src/i18n/locales/{fr,en}.json` — 95 clés synchronisées (les chaînes buzzer « RELANCER » / « gagnant du tour » ont disparu avec leurs composants).
- `src/styles/main.scss` — import du nouveau partial.

## Contrat consommé (routes exactes, toutes sous `http://127.0.0.1:8000/api/v1`)

| Route | Usage console |
|---|---|
| `POST /pilot/themes` | Création de partie : `{title, population: 'studio'\|'home', first_question_id}` → 201 |
| `GET /pilot/themes/{theme}/state` | Polling sobre (2 s paramétrable) : état fenêtre, `survivors_count`, `participants_count`, `server_time` |
| `POST /pilot/rounds` | « Manche suivante » : `{theme_id, question_id}` → 201 |
| `POST /pilot/rounds/{round}/open` | OUVRIR (confirmation F-4, résultat relu serveur) |
| `POST /pilot/rounds/{round}/close` | FERMER (idem) |
| `POST /pilot/themes/{theme}/finish` | TERMINER LA PARTIE → `[{player_id, rank\|null}]` |

En-tête `X-Pilot-Token` sur chaque requête. Aucune route inventée ; la console ne reçoit jamais `correct_index` (INV-2 préservé).

## Comportement en perte de lien (F-2, vérifié en direct)

- **Toute requête qui échoue** (réseau coupé, serveur mort, 5xx) → badge rouge `LIEN PERDU — requête en échec` au tick de polling suivant (≤ 2 s) ; **tous les contrôles passent inactifs** (boutons grisés, confirmation impossible).
- **401/404** (jeton refusé ou ressource hors session) → badge rouge `NON CONNECTÉ — jeton absent ou refusé`, contrôles inactifs, bandeau d'erreur explicite. Sans jeton (premier accès) : l'écran de saisie bloque la console.
- **Dernier état serveur connu conservé à l'écran** pendant la coupure (invalidé visuellement par le badge rouge) — choix assumé : en direct, savoir « où on en était » aide ; l'ambiguïté est levée par le badge, pas en masquant les données.
- **Retour du lien** : le premier poll réussi remet le badge vert et ré-active les contrôles — prouvé par le scénario E2E (coupure `artisan serve` → capture rouge → redémarrage → ouverture de fenêtre réussie).
- **Erreurs métier** (422, ex. finish avant les 5 manches clôturées) : le lien reste vert, le message serveur s'affiche en bandeau rouge dismissible.

## Vérification de bout en bout (API réelle + Chrome headless piloté en CDP)

Scénario joué sur `php artisan serve` + Vite temporaire (tous process tués après) :
smoke `curl` (401 sans jeton / 401 mauvais jeton / 404 hors session / 422 champs manquants) → saisie du jeton → création de partie → état serveur `pending` → **coupure API** (badge rouge, contrôles morts) → **retour API** (badge vert) → ouverture de fenêtre avec confirmation → 4 joueurs servis (3 corrects, 1 erreur, via factories) → compteurs **4 participations / 3 survivants** (EX-33) → fermeture → programmation des manches 2-5 (une question validée par manche) → partie complète → **finish → 3 gagnants**, idempotent au second appel (vérifié curl).

Captures (`docs/rapports/assets/`) : jeton, création, état serveur, lien perdu, lien rétabli, confirmation d'ouverture, fenêtre ouverte (compteurs), fenêtre fermée (MANCHE SUIVANTE active), modale manche suivante, manche 2 prête, gagnants :
`2026-09-26-console-live-01-jeton.png` → `…-11-gagnants.png`.

`build` (tsc strict + Vite), `lint` (oxlint, 0 warning), `check:i18n` : **verts**.

## Ce que j'ai dû supposer (section la plus importante)

1. **⚠️ Jeton pilote PROVISOIRE, auth animateur non spécifiée** (hypothèse backend n°1, reprise telle quelle) : écran de saisie du token opaque de session, stocké en `sessionStorage`, champ `password`, **jamais réaffiché** ; l'écran porte l'avertissement « PROVISOIRE » à destination de l'animateur. **Aucune route de validation de jeton n'existe** : la validité est établie par la première requête réelle (création ou premier poll) — avant cela, badge « non connecté » et contrôles inactifs. Bouton « Changer de jeton » dans l'en-tête. **À signaler au PO** comme le backend l'a déjà fait.
2. **⚠️ RELANCER LE TOUR supprimé — écart avec le squelette initial, signalé** : la relance appartient au format buzzer (I-7), le format élimination n'en a pas. Remplacé par **MANCHE SUIVANTE** (`POST pilot/rounds`), grisée tant que la fenêtre courante n'est pas fermée (consigne de mission), avec saisie du `question_id` validé dans la modale de confirmation. De même, le panneau « Gagnant du tour (buzzer) » est remplacé par « Résultat de la partie ».
3. **⚠️ Manque du contrat : aucune route ne liste les questions validées.** `POST pilot/themes` EXIGE `first_question_id` — impossible de créer un thème sans question (422), donc « créer sans questions » n'était pas une option. Choix : champ numérique sobre + avertissement visible dans l'UI (« l'id se lit dans Filament — manque du contrat signalé »). **À arbitrer : une route de liste des questions validées rendrait la console autonome.**
4. **Persistance du `theme_id` en `sessionStorage`** : le contrat n'a aucune route de liste des thèmes — sans cette mémoire locale, un refresh de la tablette en plein direct perdrait la partie en cours. Documenté dans `src/api/pilot.ts`.
5. **Gagnants affichés = compte + ids avec rang** (`Joueur #14`, pastilles sobres) : les ids retournés par `finish` ne sont pas une liste de répondants (F-6 respecté) ; l'animateur peut ainsi désigner les gagnants en régie. Le compte est l'élément dominant ; les chips sont secondaires. Choix documenté, réversible.
6. **TERMINER LA PARTIE disponible dès qu'une partie est active** (pas de garde client sur les 5 manches) : le serveur refuse proprement (422) tant que les manches ne sont pas clôturées, et le message serveur est affiché — pas de règle métier dupliquée côté client.
7. **Population déplacée à la création de partie** (I-1 : la population appartient au thème, pas à l'ouverture de fenêtre) — le fieldset radio du squelette a quitté la colonne d'actions pour le formulaire de création ; la population du thème courant est rappelée sous l'état de la fenêtre.
8. **CORS non configuré explicitement côté API** mais le middleware `HandleCors` par défaut de Laravel répond au preflight (`Access-Control-Allow-Origin: *` constaté en 204). Suffisant en dev ; **à durcir lors du déploiement** (origines autorisées), côté backend.
9. Intervalle de polling 2 s et base URL `127.0.0.1:8000` : points de départ paramétrables dans `src/config/apiConfig.ts`, commentés comme tels — le transport temps réel attend l'ADR (même statut que le polling joueur côté backend).

## Non traité (hors mission)

- Console agent questions : **inchangée** (contrat inexistant).
- `docs/10-contrat-api-console-animateur.md` (« AUCUNE ROUTE N'EST DÉFINIE ») est désormais **périmé pour la frontière pilotage** — à mettre à jour par le PO ou à pointer vers le contrat du rapport backend.
