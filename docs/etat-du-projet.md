# État du projet

> **Dernière mise à jour** : 2026-09-25
> **Statut global** : 🔧 DÉVELOPPEMENT INITIAL. Scaffolding des trois surfaces logicielles fait le 2026-09-25 (rapport `rapports/2026-09-25-session-1-scaffolding-et-conventions.md`). **Aucune logique métier.**

---

## 1. Où en est le projet

Le cadrage produit est **abouti sur la mécanique de jeu** et **ouvert sur l'infrastructure**.

| Domaine | État |
|---|---|
| Concept et formats de jeu | ✅ Tranché |
| Mécanique d'un tour | ✅ Tranché |
| Identité, triche, présence à l'antenne | ✅ Tranché |
| Principes d'architecture | ✅ Tranché |
| Stack | ✅ Tranché (une réserve : stylisation React Native) |
| Chaîne de mesure — fournisseur | ✅ Choisi (ACRCloud premium) |
| Chaîne de mesure — performances réelles | ❌ **Aucun test** |
| Couche temps réel | ❌ **Non conçue** |
| Modèle de données | ❌ Squelette seulement |
| Contrat d'API | ❌ Aucune route définie |
| Boîtier de plateau | ❌ Rien d'arbitré |
| Cadre légal | ❌ Non instruit |

---

## 2. Les quatre blocages, par ordre de priorité

### 2.1 🔴 L'architecture temps réel

**Bloque** : le modèle de données, le contrat d'API, et tout dimensionnement.

Rien ne répond aujourd'hui à « cent mille personnes qui répondent en même temps ». Deux profils de charge différents cohabitent (buzzer et élimination). Le mode dégradé en plein direct n'est pas pensé.

> Un produit de direct sans mode dégradé n'est pas un produit de direct.

### 2.2 🟠 Le test terrain de la chaîne de mesure

**Bloque** : l'activation du format buzzer.

Deux inconnues qui ne se referment que par un test : la durée de fenêtre nécessaire à une reconnaissance fiable dans un salon réel, et la précision atteignable sur le décalage.

⚠️ La fenêtre actuellement envisagée (I-22, « quelques secondes ») est **probablement sous-dimensionnée** — une source tierce évoque dix à quinze secondes.

### 2.3 🟠 Le recouvrement avec Voxflo

**Bloque** : le modèle de données côté studio, et l'appairage des boîtiers.

Voxflo enrôle déjà le public studio avec son identité et sa place. Interflo fait jouer ce même public. Réemploi ou duplication ? **Demande de lire le dépôt.**

### 2.4 🟡 Le cadre légal

**Ne bloque pas le développement**, mais bloque la mise en production avec lots réels.

Point le plus sensible : le **tirage au sort** (I-28) fait basculer le jeu de l'adresse vers le hasard, ce qui peut changer de régime réglementaire.

---

## 3. Ce qui peut avancer dès maintenant

Ces chantiers ne dépendent d'aucun des quatre blocages :

1. **Scaffolding** des quatre composants (`interflo-api`, `interflo-app`, `interflo-web`, `interflo-firmware`). ⚠️ Attend les deux réponses du §5 — monorepo et stylisation. Elles ne bloquent que lui.
2. **Format élimination** — mécanique complète, avec le mode mesuré désactivé. C'est le chemin qui ne dépend d'aucune inconnue d'infrastructure.
3. **Appairage** : QR, code court, rotation.
4. **Vérification du numéro de téléphone.**
5. **Tableau de bord Filament** : tenants, configuration, seuils.
6. **Test ACRCloud en conditions réelles** — à lancer en parallèle, c'est ce qui débloque 2.2. Étapes dans `13-integration-acrcloud.md` §5.

> ⚠️ Le point 6 ne demande **aucun développement** et débloque l'activation du format buzzer. C'est le meilleur rapport effort/déblocage du projet aujourd'hui.

---

## 4. Décisions récentes (2026-09-25)

Session de cadrage, décisions I-26 → I-43. Les plus structurantes :

- **Deux formats de jeu**, développés tous deux en V1, avec activation progressive — élimination d'abord (I-26).
- **Le retour au joueur non-premier est tranché** (I-29) : juste ou faux, sans la bonne réponse, aucun resserrement. C'était le verrou du cadrage depuis plusieurs semaines.
- **Séparation complète d'avec BOS** (I-35) : point d'entrée, service et base.
- **Couplage unidirectionnel et anticipé** (I-36) : rien de synchrone pendant le direct.
- **Production assistée des questions** (I-33), avec validation humaine obligatoire.
- **Sponsoring télécom abandonné** (I-34).
- **Aucun numéro de voie** (I-42) : Claude Code gère son découpage en local.

Deux corrections ont été versées au cadrage le même jour :

- ✅ La réserve « l'empreinte prouve qu'on entend, pas quand » **tombe** : le champ `timestamps_ms` donne le décalage directement.
- ⚠️ L'affirmation « l'empreinte ne demande rien au diffuseur » était **trop forte** : il faut ingérer le flux de chaque chaîne.

---

## 5. En attente du PO

| Sujet | Depuis |
|---|---|
| ~~Monorepo ou dépôt séparé ?~~ ✅ **Tranché le 2026-09-25** : monorepo + interdiction d'import direct BOS (feu vert du PO en session — voir rapport session 1, §3.1). | — |
| ~~Stylisation React Native~~ ✅ **Tranché le 2026-09-25** : **NativeWind** (même session). | — |
| **Répartition questions-plateau / culture générale** — « majoritairement plateau », sans chiffre. | 2026-09-25 |
| **Dotation type** envisagée par les premières chaînes — c'est elle qui décide du niveau de contrôle à construire. | — |
| **Version Laravel** — 13.33 installée (« dernière version », I-41) ; retour à la 12 possible si le PO préfère. | 2026-09-25 |

---

## 6. Journal

| Date | Événement |
|---|---|
| 2026-08-30 | Cadrage initial. I-1 → I-17. |
| 2026-09-01 | Empreinte audio, classement au temps de réaction. I-18 → I-25. |
| 2026-09-25 | Deux formats, §7 tranché, architecture, production des questions. I-26 → I-43. Recherche ACRCloud. Souscription premium. |
| 2026-09-25 | Création de l'arborescence projet et de la documentation. |
| 2026-09-25 | Scaffolding des trois surfaces logicielles (API Laravel 13 + Filament 5, app RN 0.87 + NativeWind, consoles web Vite + React 19 + Tailwind 4). Conventions PO consignées dans `CONVENTIONS.md` : nomenclature EN / commentaires et docs FR, i18n react-i18next (fr défaut + en), SCSS en partials par composant (web), routage des modèles (léger = fastidieux, K3 = audits). Rapports dans `rapports/`. |
| 2026-09-25 | **Session 2** : bascule PostgreSQL 17 (bases `interflo`/`interflo_test`), vérification téléphone par OTP (Sanctum, provider SMS non choisi — défaut log), appairage QR/code court avec rotation (I-14/I-15/I-18), une session active garantie en base (I-17). 7 tables PROVISOIRES (docs/07 §5 respecté : aucune entité de jeu). Bascule pnpm (web + mobile). App mobile buildée et lancée sur le simulateur iPhone 16 du PO, parcours d'appairage validé de bout en bout. 37 tests API verts. Rapport `rapports/2026-09-25-session-2-appairage-otp-postgresql.md`. |
| 2026-09-25 | **Session 3** : tableau de bord Filament (tenants + config de jeu I-25/I-28/I-31/I-40, émissions, sessions avec cycle de vie I-16, page seuils en lecture seule — 58 tests verts). Logo officiel intégré sur les trois surfaces (wordmark blanc → thème sombre). **D-001 : direction visuelle « fun, gaming, mais corporate »** tranchée et appliquée (mobile + consoles web + primaire magenta Filament). Abonnement ACRCloud confirmé actif — identifiants à demander au moment de la chaîne de mesure. Rapport `rapports/2026-09-25-session-3-filament-logo-design.md`. |
| 2026-09-25 | **Session 4** : **format élimination jouable de bout en bout** — moteur API (fenêtre personnelle EX-20, plancher I-30, verrouillage EX-32, fin de partie I-28, CA-07 prouvé, 105 tests verts), app mobile câblée (E2E complet sur simulateur), console animateur câblée (pilotage réel, F-1/F-2 démontrés par coupure). Amendement D-001 §3 : animations et effets partout (mobile : worklets Reanimated ; web : CSS pur, zéro délai sur les gestes de direct). ⚠️ Auth animateur provisoire (X-Pilot-Token) à trancher. Rapport `rapports/2026-09-25-session-4-elimination-animations.md`. |
