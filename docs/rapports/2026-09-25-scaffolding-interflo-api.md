# Rapport — Scaffolding interflo-api (2026-09-25)

> Étape : scaffolding de l'API Laravel + Filament. **Aucune logique métier, aucune
> migration métier, aucune route de jeu.**

## Ce qui a été fait

- Projet Laravel créé dans `interflo-api/` (README pré-existant préservé et fusionné
  dans le nouveau README projet).
- Filament installé avec un panel **admin** (`/admin`, `AdminPanelProvider`), page de
  connexion activée (`->login()` — absente du squelette généré, corrigé).
- Pest configuré (`tests/Pest.php`, tests d'exemple convertis, `phpunit.xml` : SQLite
  en mémoire) et laravel/pint installé.
- Base de développement **SQLite** (`database/database.sqlite`). Bloc PostgreSQL prêt
  mais commenté dans `.env.example` et `.env`, avec avertissement « à confirmer, pas à
  recopier par défaut ».
- Migrations exécutées : **uniquement** les trois tables du framework
  (`users`, `cache`, `jobs`). Aucune table métier.
- `config/interflo.php` créé : toutes les valeurs de réglage pilotées par
  l'environnement (préfixe `INTERFLO_`), avec commentaires français et références de
  décisions. Les trois valeurs scellées (I-4, I-27, I-28) sont en config sous la clé
  `sealed`, non exposées à l'environnement.
- Un utilisateur admin Filament de développement créé en non-interactif
  (`admin@interflo.local`, identifiants locaux documentés dans le README).
  **Aucune donnée métier seedée.**
- Assets compilés (`npm run build`), `php artisan serve` vérifié : `/` → 200,
  `/admin` → 302 vers `/admin/login` → 200. Serveur arrêté, aucun processus résiduel.
- Les dossiers `interflo-app`, `interflo-web`, `interflo-firmware` n'ont pas été touchés.

## Tests

4 verts (5 assertions) : page d'accueil, santé Pest, redirection invité du panel,
page de connexion du panel. Pint : 30 fichiers conformes.

## Versions installées

Laravel 13.33.0 · Filament 5.8.4 · Pest 4.7.8 · Pint 1.32.1 · PHP 8.4.21 · Node 24.

## Valeurs paramétrables introduites (points de départ NON validés)

| Clé `config/interflo.php` | Env | Défaut | Réf. |
|---|---|---|---|
| `anti_automation_floor_ms` | `INTERFLO_ANTI_AUTOMATION_FLOOR_MS` | 800 ms | I-30 / EX-18 |
| `answer_window_seconds` | `INTERFLO_ANSWER_WINDOW_SECONDS` | 10 s | I-31 / EX-19 |
| `server_envelope_seconds` | `INTERFLO_SERVER_ENVELOPE_SECONDS` | 15 s | EX-20 |
| `pairing_code_rotation_seconds` | `INTERFLO_PAIRING_CODE_ROTATION_SECONDS` | 20 s | I-18 |
| `short_code_length` | `INTERFLO_SHORT_CODE_LENGTH` | 6 | I-14 |
| `short_code_alphabet` | `INTERFLO_SHORT_CODE_ALPHABET` | `ABCDEFGHJKMNPQRTUVWXYZ2346789` (sans 0/O, 1/I/L, 5/S) | EX-03 |
| `buzzer_max_relaunches` | `INTERFLO_BUZZER_MAX_RELAUNCHES` | 3 | I-7 |
| `general_culture_percent` | `INTERFLO_GENERAL_CULTURE_PERCENT` | **null** (non arrêté) | I-32 |

Valeurs **scellées par décision PO** (en config, non paramétrables) :
`sealed.propositions_per_question` = 4 (I-4), `sealed.elimination_rounds` = 5 (I-27),
`sealed.winner_count_options` = [1, 3, 5] (I-28).

## ⚠️ Hypothèses (section la plus importante)

1. **Laravel 13 installé au lieu de Laravel 12.** La consigne demandait « dernière
   version, Laravel 12 », mais la dernière version stable est la **13.33** (Filament
   5.8 s'installe dessus sans contrainte). La doc projet mentionne d'ailleurs BOS sur
   Laravel 13 (`04-architecture.md` §3.1). J'ai retenu l'intention « dernière version ».
   **À confirmer par le PO** — un retour à Laravel 12 resterait peu coûteux à ce stade.
2. **Filament 5** (et non 4) installé : c'est la version résolue par Composer pour
   Laravel 13. Même remarque.
3. **PostgreSQL laissé en hypothèse** : SQLite en dev, bloc pgsql commenté avec
   avertissement. Aucune décision prise.
4. **Identifiants admin locaux choisis par moi** (`admin@interflo.local` /
   `interflo-admin-dev`) pour satisfaire l'exigence non-interactive ; documentés dans
   le README comme identifiants de développement uniquement. **À changer** dès le
   premier environnement partagé.
5. **Alphabet du code court composé par moi** (22 lettres + 7 chiffres, sans
   caractères ambigus EX-03) : point de départ non validé, comme toutes les valeurs.
6. **Une seule session utilisateur locale** : pas de rôles/permissions (Spatie
   Permission non installé) — non demandé au scaffolding, et le cloisonnement tenant
   (I-38) dépend du schéma bloqué.
7. Le `.gitkeep` d'`interflo-api/` a été conservé ; le dossier temporaire de
   scaffolding a été supprimé après rsync.

## Ce qui reste bloqué, et par quoi

- **Migrations métier** : `07-modele-de-donnees.md` est un squelette bloqué par trois
  inconnues — aucune table métier écrite, conformément à la consigne.
- **Couche temps réel** : non conçue (`04-architecture.md` §5) — pas commencée.
- **Tableau de bord métier Filament** (tenants, seuils, banque de questions) : attend
  le schéma de données.
- **Questions PO en suspens** (PROMPT-DEMARRAGE §« Par où commencer ») : monorepo ou
  dépôt séparé ; stylisation React Native (NativeWind). Elles ne bloquent pas l'API,
  mais restent sans réponse.
