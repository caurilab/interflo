# Rapport — Reprise (après Kimi) : versionnement, consolidation, effets « très stylé »

> **Date** : 2026-09-26
> **Auteur** : agent principal (reprise de Kimi Work)
> **Contexte** : reprise du projet après la session 4 (format élimination jouable de bout en bout). Feu vert du PO pour reprendre et apporter des améliorations sur tous les aspects.

---

## 1. Ce qui a été fait

### 1.1 Versionnement (aucun dépôt git existait)

- `git init` du monorepo (branche `main`), commit initial de tout l'existant (442 fichiers).
- `.gitignore` racine déjà correct : `vendor/`, `node_modules/`, `Pods/`, `build/`, `.env` exclus. Vérifié : aucun secret ni dossier volumineux versionné (seul `interflo-app/ios/.xcode.env` — template sûr).

### 1.2 Consolidation des fichiers racine périmés

Les fichiers racine disaient encore « zéro code » et portaient d'anciens noms de variables (`GAME_MIN_ANSWER_MS`, `GAME_WINDOW_MS`, `PAIRING_CODE_ROTATION_S`) qui n'existaient plus dans le code :

- `README.md` — statut réel + commandes de lancement.
- `Makefile` — cibles réelles (`api-test`, `web-dev`, `app-ios`, `test`…). Corrigé un bug : `web-i18n` (chiffres dans le nom) invisible dans `make help` (regex sans `0-9`).
- `.env.example` — aligné sur `config/interflo.php` (les vrais `INTERFLO_*`). Sections ACRCloud/BOS marquées « pas encore consommées ».
- `docker-compose.yml` — `postgres:17` + `redis:7` (Redis préparé pour D-002, non consommé), avertissement sur le conflit de port avec le PostgreSQL Homebrew local.
- `docs/etat-du-projet.md` — statut et journal mis à jour.

### 1.3 Effets « très stylé » (mobile, D-001 §3)

Direction « fun, gaming, mais corporate », garde-fous respectés (worklets Reanimated, uniquement `transform`/`opacity`, aucun flou ni ombre, `prefers-reduced-motion`, entrée de gamme) :

- **`EnergyBackground`** — halo d'énergie ambiant : deux nappes magenta/orange hors-champ qui respirent et dérivent lentement derrière le contenu. Intensités `calm` (attente/éliminé), `live` (fenêtre ouverte), `celebrate` (gain). Câblé sur les 5 écrans de jeu (Waiting, Game, Feedback, SessionEnd, Locked).
- **`ShockwaveRings`** — ondes de choc concentriques qui s'étendent et s'estompent autour des pastilles « juste ✓ » et « gagnant ★ ». Coup unique, pas de boucle.

### 1.4 Vérification (tout vert)

| Surface | Résultat |
|---|---|
| API | 105 tests / 1264 assertions |
| Mobile (Jest) | 17 tests (15 + 2 nouveaux garde-fous MOTION) |
| Mobile (lint) | 0 erreur, 18 warnings préexistants (no-inline-styles) |
| Mobile (tsc) | aucune erreur |
| Web (build prod) | 135 modules, 0 erreur |
| Web (i18n + lint) | 95 clés fr/en synchro, 0 erreur |
| Simulateur | app installée + lancée (iPhone 17, iOS 26.5), bundle Metro servi sans erreur JS |

---

## 2. Ce que j'ai dû supposer (section la plus importante)

1. **Je ne peux pas voir les captures d'écran** : mon modèle n'accepte pas l'entrée image. Les captures sont produites dans `docs/rapports/assets/` pour le PO, mais les effets sont vérifiés par compilation (tsc), tests, bundle Metro et lancement — **pas par inspection visuelle**. Une passe manuelle de quelques secondes est recommandée pour valider le ressenti des halos et des ondes de choc.
2. **Les effets ne s'affichent pas sur l'écran d'appairage** : ils vivent sur les écrans de jeu (waiting → question → feedback → fin). Les voir exige une session réelle (appairage → OTP → attachement), que je ne peux pas piloter sans automation UI du simulateur.
3. **`interflo-app/ios/.xcode.env.local` pointe vers le node de Kimi.app** (`/Applications/Kimi.app/.../node`, v24.15). Ça marche, mais c'est fragile ; fichier local (gitignoré), laissé tel quel — à remplacer par le node nvm si Kimi.app disparaît.
4. **Valeurs paramétrables introduites** (points de départ NON validés, à arbitrer au ressenti) :
   - `MOTION.energy.calm/live/celebrate` : périodes 8 s / 6 s / 4 s, opacité 0.05–0.10 / 0.08–0.15 / 0.10–0.18, dérive 24/32/40 px.
   - `MOTION.shockwave` : 2 anneaux, 1000 ms, stagger 240 ms, échelle max 2.2, opacité max 0.55.
   - Taille des nappes 420 px, positionnement des coins — géométrie fixe dans `EnergyBackground`, pas paramétrée.

---

## 3. Ce qui reste bloqué (inchangé)

1. **D-002 (architecture temps réel)** — PROPOSÉE, non validée par le PO. C'est elle qui « termine » le produit de direct.
2. **Auth animateur** (`X-Pilot-Token` provisoire) — à trancher.
3. **Format buzzer** — bloqué par le test terrain ACRCloud (identifiants + URL de flux à fournir par le PO).

---

## 4. Prochaine étape

Valider D-002 et l'implémenter (Reverb + Redis autoritaire + Octane + mode dégradé 4 niveaux), ou continuer l'audit qualité de l'existant (i18n mobile, code de l'auth provisoire, invariants INV-1→8).
