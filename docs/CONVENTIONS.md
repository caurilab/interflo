# Conventions de code — Interflo

> **Statut** : ✅ TRANCHÉ par le PO le 2026-09-25 (session de développement initiale).
> **Portée** : tous les composants du dépôt (`interflo-api`, `interflo-app`, `interflo-web`, `interflo-firmware`).
> Ce document complète `PROMPT-DEMARRAGE.md`. En cas de divergence avec un agent ou un rapport, c'est lui qui fait foi pour les conventions ci-dessous.

---

## 1. Langues

| Élément | Langue |
|---|---|
| Noms de fichiers, dossiers, fonctions, variables, classes, routes, clés de config | **Anglais** |
| Commentaires et docblocks dans le code | **Français** |
| Documentation (`docs/`, README, rapports, décisions) | **Français** |
| Textes affichés à l'utilisateur | **Jamais en dur** — voir §2 |

---

## 2. Internationalisation (i18n)

L'application aura **une version française et une version anglaise**. L'i18n se prépare dès le scaffolding, pas « plus tard ».

| Surface | Outil | Décision |
|---|---|---|
| Application mobile (`interflo-app`) | **react-i18next** (+ i18next) | Validé PO |
| Consoles web (`interflo-web`) | **react-i18next** (+ i18next) | Validé PO |
| API (`interflo-api`) | Localisation Laravel (`lang/fr`, `lang/en`) | Messages serveur et panel Filament |

Règles :

- **Aucun texte utilisateur en dur** dans le code. Tout passe par une clé de traduction.
- **Français = locale par défaut** (le public visé est francophone), anglais en seconde locale, fallback anglais.
- Les fichiers de locale vivent dans `src/i18n/locales/{fr,en}.json` (mobile et web) et `lang/{fr,en}/` (API).
- Une clé absente de la locale `en` est un défaut bloquant à la revue — les deux locales partent ensemble ou pas du tout.

---

## 3. Styles

### Web (`interflo-web`)

- **Tailwind CSS**, dernière version (v4 à la date de ces conventions), pour les utilitaires.
- **SCSS organisé en partials par composant** pour les styles propres :
  - un point d'entrée (`src/styles/main.scss`),
  - des partials séparés par composant ou surface (`_host-console.scss`, `_question-agent.scss`, `_connection-badge.scss`…), préfixés `_` et importés depuis le point d'entrée,
  - variables communes (couleurs, espacements, points de rupture) dans `_variables.scss`.
- Objectif : **maintenabilité**. Un composant = un partial. Pas de feuille fourre-tout.

### Mobile (`interflo-app`)

- **NativeWind** (grammaire Tailwind sur React Native). ⚠️ **SCSS n'existe pas côté React Native** : la règle « partials SCSS » ne s'y applique pas. L'équivalent de la séparation y est : styles utilitaires NativeWind dans les composants, tokens communs (palette, espacements) centralisés dans `tailwind.config.js`.

### Gestionnaire de paquets JavaScript

- **pnpm** (tranché par le PO le 2026-09-25), via `corepack pnpm` (11.5.2) tant que le pnpm Homebrew du PATH est en 10.x. Champ `packageManager` épinglé dans chaque `package.json`, `node-linker=hoisted` côté mobile (Metro ne supporte pas les node_modules en liens symboliques).

---

## 4. Routage des modèles d'agents (consigne PO, 2026-09-25)

| Nature de la tâche | Modèle |
|---|---|
| Tâches fastidieuses / rébarbatives (scaffolding, génération répétitive, renommages, extraction de chaînes i18n) | Modèle **léger** |
| Audits, sécurité, tâches exigeant de la prudence (invariants INV-1 à INV-8, non-fuite de la bonne réponse, isolation tenant, refus hors fenêtre) | Modèle le plus prudent (**K3**) |

---

## 5. Rappels hérités du cadrage (inchangés)

- Aucune valeur numérique validée hors les trois scellées : **4 propositions** (I-4), **5 manches** (I-27), **1, 3 ou 5 gagnants** (I-28). Tout le reste est paramétrable, jamais en dur.
- La bonne réponse ne sort jamais du serveur (CA-07).
- Aucun appel synchrone vers BOS pendant une fenêtre de jeu (I-36).
