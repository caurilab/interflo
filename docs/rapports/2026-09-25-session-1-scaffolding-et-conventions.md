# Rapport — Session 1 : scaffolding des trois surfaces + conventions PO

> **Date** : 2026-09-25
> **Auteur** : agent principal (Kimi Work), avec sous-agents issus de `.claude/agents/` (backend, mobile, frontend)
> **Périmètre** : `interflo-api`, `interflo-app`, `interflo-web`. `interflo-firmware` non touché (rien d'arbitré — interdit par le cadrage).
> Rapports détaillés par composant : `2026-09-25-scaffolding-interflo-api.md`, `2026-09-25-scaffolding-interflo-app.md` (le web est couvert ici, §2.3 et §3).

---

## 1. Ce qui a été fait

### 1.1 Scaffolding (trois chantiers en parallèle)

| Composant | Stack livrée | Vérifications |
|---|---|---|
| `interflo-api` | Laravel **13.33.0**, Filament **5.8.4** (panel `/admin`), Pest 4.7.8, Pint, SQLite en dev | 4 tests verts, Pint propre, `serve` OK |
| `interflo-app` | React Native **0.87.1** (bare, `com.interflo.app`), TypeScript strict, NativeWind 4.2.7, React Navigation 7 | `tsc` OK, Metro sert le bundle iOS (200), 5 tests verts |
| `interflo-web` | Vite 8, React 19, TypeScript strict, Tailwind CSS 4, React Router 8 | `build` OK, 3 routes 200, lint propre |

- **API** : `config/interflo.php` centralise tous les réglages du jeu, pilotés par l'environnement (`INTERFLO_*`). Les trois valeurs scellées (4 propositions I-4, 5 manches I-27, gagnants [1,3,5] I-28) sont isolées sous `sealed`, non exposées à l'env. Aucune migration métier (le modèle de données est bloqué par trois inconnues — respecté). Localisation Laravel publiée, `APP_LOCALE=fr`, repli `en`.
- **Mobile** : 5 écrans squelettes (appairage QR + code court côte à côte I-14, attente inerte I-2, jeu 4 gros boutons I-5/M-1, retour juste/faux au ton soigné I-29/M-4, fin de session en TODO — non spécifié). Zéro appel réseau, zéro route inventée.
- **Web** : console animateur (gros boutons F-3, confirmations F-4, badge de lien F-2, état « NON CONNECTÉ » plutôt qu'état simulé F-1, aucune liste de répondants F-6, rappel d'enveloppe F-5 lu depuis `consoleConfig.ts`) et console agent questions (valider/corriger comme gestes distincts I-33). Zéro appel réseau.

### 1.2 Conventions PO (tranchées en session le 2026-09-25)

Consignées dans **`docs/CONVENTIONS.md`** et rappelées dans les trois chartes d'agents (`.claude/agents/`) :

1. **Nomenclature anglaise** (fichiers, fonctions, variables) ; **commentaires et documentation en français**.
2. **i18n par react-i18next** (web + mobile), français par défaut + anglais, aucun texte utilisateur en dur. API : localisation Laravel.
3. **Styles web** : Tailwind v4 + **SCSS en partials par composant** (`src/styles/`, un composant = un partial, `@use`). Mobile : NativeWind (SCSS non applicable à React Native — tokens centralisés).
4. **Routage des modèles** : tâches fastidieuses → modèle léger ; audits et sécurité → modèle le plus prudent (K3). Appliqué dès cette session : le scaffolding a été délégué, l'audit des invariants (§2.4) a été fait par l'agent principal.

Application concrète : 54 clés i18n (web) et 18 clés (mobile), fr/en synchronisées et vérifiées par scripts/tests de cohérence ; 9 partials SCSS web ; extraction de toutes les chaînes en dur.

---

## 2. Audit des invariants (agent principal, post-livraison)

| Contrôle | Résultat |
|---|---|
| Aucune route de jeu exposée (`route:list`) | ✅ 18 routes, framework + Filament uniquement |
| Aucune migration métier | ✅ `users`, `cache`, `jobs` seulement |
| Aucun appel synchrone sortant (BOS ou autre) dans le code applicatif | ✅ aucun (`Http::`, `api.tvflo.test` absents hors `vendor/`) |
| Bonne réponse côté serveur (INV-2) | ✅ trivial à ce stade — aucune logique de jeu n'existe encore |
| Valeurs en dur | ✅ tout passe par `config/interflo.php` / `consoleConfig.ts` / `gameConfig.ts` |
| Tests | ✅ 4 API + 5 mobile + checks i18n web, aucune régression |
| Processus résiduels (serve, Metro, Vite) | ✅ tous arrêtés après vérification |

---

## 3. Ce que nous avons dû supposer (section la plus importante)

1. **Monorepo et NativeWind tranchés par le feu vert du PO en session** (« je te fais confiance, avance ») — les deux questions en attente du `PROMPT-DEMARRAGE.md` sont levées par délégation. À confirmer formellement si le PO veut les verser aux décisions.
2. **Laravel 13 installé, pas 12** : la « dernière version » (I-41) est la 13.33. Retour à la 12 possible et peu coûteux à ce stade — **à trancher**.
3. **Filament 5.8** : version résolue par Composer pour Laravel 13.
4. **SQLite en dev, PostgreSQL 17 en hypothèse** (bloc prêt, commenté) — le choix BOS n'est pas recopié par défaut, conformément à `04-architecture.md` §3.1.
5. **Identifiants admin locaux** : `admin@interflo.local` / `interflo-admin-dev` — développement uniquement, documentés dans le README de `interflo-api`, à changer avant tout environnement partagé.
6. **Tailwind v4 hors pipeline Sass** : la doc officielle v4 déconseille Sass dans la chaîne Tailwind — entrée Tailwind conservée en CSS, partials SCSS pour les styles propres.
7. **Traductions anglaises rédigées par les agents** (aucune source validée) — à faire relire.
8. **Place du sélecteur de langue mobile non spécifiée** : fonction `changeLanguage()` livrée sans UI.
9. Valeurs paramétrables introduites (toutes « point de départ non validé ») : plancher anti-automatisation 800 ms (I-30), fenêtre 10 s (I-31), enveloppe serveur 15 s (EX-20), rotation du code 20 s (I-18), code court 6 caractères / alphabet 29 signes sans ambigus (I-14/EX-03), relances buzzer 3 (I-7), enveloppe à meubler 22 s côté console (F-5). Culture générale : null tant que non arrêté (I-32).

---

## 4. Ce qui reste bloqué, et par quoi

| Chantier | Bloqué par |
|---|---|
| Migrations / modèle de données | Les trois inconnues de `07-modele-de-donnees.md` |
| Couche temps réel | Non conçue (§11 cadrage) — ADR dédié requis avant toute ligne |
| Format buzzer | Chaîne de mesure non testée (test ACRCloud terrain, `13-integration-acrcloud.md` §5) |
| Classement persistant, détection comportementale, sélecteur de langue mobile | Mécaniques non spécifiées (EX-45, EX-46) |
| Traduction fr des messages framework Laravel | Package `laravel-lang` à ajouter quand nécessaire |

## 5. Prochaine étape

Le format élimination (mode mesuré désactivé) reste le chemin attendu par le PO — précédé d'une lecture de `07-modele-de-donnees.md` pour identifier précisément ce qui bloque les migrations minimales nécessaires, et de l'appairage (QR, code court, rotation), qui n'en dépend qu'en partie.
