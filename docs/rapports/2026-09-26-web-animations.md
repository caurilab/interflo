# Rapport — Animations et effets web (D-001 §3)

> **Date** : 2026-09-26 — **Agent** : interflo-frontend
> **Périmètre** : mission « passage animations et effets » sur `interflo-web`. Direction : [D-001-direction-visuelle.md](../decisions/D-001-direction-visuelle.md) §3 (amendement PO : tout doit être bien animé, avec des effets) et ses garde-fous. **CSS uniquement — aucune lib d'animation JS ajoutée, aucune dérogation.** i18n : aucune nouvelle chaîne. Console animateur câblée API : comportement de pilotage inchangé (handlers, polling, F-1/F-2/F-4).

---

## Animations ajoutées (par surface)

### Accueil — gaming assumé (`_home.scss`)

- **Halo d'énergie animé** : les deux nappes radiales (magenta haut, orange bas) sont passées de `background` statique à des pseudo-éléments `::before`/`::after` en **dérive lente** (`home-halo-drift-a` 14 s, `-b` 18 s, alternate infinite) — transform uniquement (`translate3d` + scale léger), `will-change: transform`, aucun repaint de background. `isolation: isolate` + `z-index: -1` : halos derrière le contenu, au-dessus du fond.
- **Entrée en cascade** : hero (`home-rise`, 480 ms) puis cartes (`console-card-in`, 480 ms, deuxième carte décalée de 120 ms) — fade + montée + scale 0.98→1, cubic-bezier(0.22, 1, 0.36, 1).
- **Survols vivants** : soulèvement + scale 1.015, barre d'accent qui s'épaissit (6→10 px), lueur colorée (`box-shadow` magenta / cream selon la carte) — transitions 160 ms, statiques une fois jouées.

### Console animateur (`_host-console.scss`, `_connection-badge.scss`, `_confirm-dialog.scss`)

- **Transition d'état de fenêtre** : classe `window-panel--{état}` posée sur le panneau d'état dans le JSX. Passage → `open` : **sweep lumineux dégradé** (success + cream, 700 ms, une traversée) sur le bandeau ; passage → `finished` : sweep cream « gain ». Le texte d'état transite en couleur sur 240 ms.
- **Compteurs qui s'animent au changement de valeur** : `<p key={valeur} class="counter-tick">` — le remount React déclenche `counter-tick` (scale 1→1.18→1, 340 ms) **uniquement quand la valeur change**. Appliqué aux participations, aux survivants (EX-33) et au compte de gagnants.
- **Badge de lien** : transition de couleur 240 ms posée **uniquement sur `.connection-badge--online`** — entrer en ligne est doux ; les états `offline`/`unauthorized` n'ont aucune transition, donc le **rouge reste immédiat à la perte** (F-2).
- **Arrivée des gagnants** : chips en **cascade légère** (`winner-chip-in`, 300 ms, délai = `min(index, 12) × 55 ms` via var CSS inline `--winner-chip-index` — plafonné pour qu'un long tableau ne soit pas interminable).
- **ConfirmDialog** : entrée animée — backdrop en fondu 160 ms, panneau fade + scale 0.94→1 + montée 190 ms. **Sortie volontairement instantanée** (voir hypothèse 2).
- **Alerte d'erreur d'action** : entrée animée (montée courte 200 ms) — une alerte qui apparaît doit attirer l'œil.
- **Boutons d'action** : press feedback existant (80 ms, `:active` scale 0.99) **inchangé** — déjà dans la borne ≤ 150 ms.

### Console agent questions (`_question-agent.scss`)

- Entrées de surface sobres : en-tête, panneaux, boutons d'action en cascade légère (`agent-surface-in`, 260 ms, délais 0→200 ms) — fade + montée 8 px, une fois au chargement.
- Classe réutilisable **`.agent-entry`** (220 ms) prête pour les futures entrées de liste (transcription horodatée, file de questions) quand leurs contrats existeront — documentée dans le partial.
- Classe de surface `question-agent` ajoutée au `main` (seul changement JSX de cette page).

## Respect des garde-fous (D-001 §3)

**Zéro délai sur les gestes de direct — comment c'est garanti :**

1. **Aucun handler modifié** : `onPress`, `handleConfirm`, `runAction` sont byte-identiques en logique ; aucune animation n'est dans le chemin clic → confirmation → requête. Les animations sont toutes déclaratives (CSS) et attachées à des *conséquences* d'état, jamais à des *causes*.
2. **Press feedback ≤ 150 ms** : les transitions `:active`/`:hover` des boutons d'action restent à 80 ms (pré-existant, vérifié).
3. **L'entrée de la ConfirmDialog ne bloque rien** : elle s'ouvre *après* le clic sur le bouton d'action (l'animation est le changement d'état) ; ses boutons sont interactifs dès le premier frame (l'animation est en `both`, sans délai).
4. **F-2 préservé par construction** : la transition douce du badge n'existe que *vers* `online` ; la sortie vers le rouge n'a pas de `transition` → application immédiate.
5. **Sortie de modale instantanée** : la modale disparaît dès que l'action part — l'écran reflète l'état serveur sans latence (F-1 prime).

**Polling 2 s sans re-déclenchement :**

- Les classes d'état (`window-panel--open`, `window-state--*`, badge) sont **stables entre les ticks** : quand l'état serveur ne change pas, la string de classe est identique, React ne mute pas le DOM, l'animation CSS ne rejoue pas.
- Les compteurs sont **remountés par `key={valeur}`** : l'animation ne joue que sur une vraie transition de valeur, jamais sur un poll stable.
- Les chips gagnants vivent dans le state `winners`, **hors du polling** (posé une fois par `finishTheme`).

**`prefers-reduced-motion` respecté partout :**

- Garde globale dans `_base.scss` : `@media (prefers-reduced-motion: reduce)` neutralise toutes les animations/transitions du projet (`animation-duration: 0.01ms !important`, `iteration-count: 1`, `transition-duration: 0.01ms`) — les changements d'état restent instantanés, rien ne casse.
- Ajustement spécifique dans `_home.scss` : dérive infinie des halos explicitement figée, survol sans déplacement.
- **Vérifié dans le CSS compilé** (`dist/assets/index-*.css`) : les deux blocs `@media (prefers-reduced-motion:reduce)` sont présents, ainsi que les 8 keyframes (`home-halo-drift-a/b`, `console-card-in`, `window-state-sweep`, `counter-tick`, `winner-chip-in`, `confirm-backdrop-in`, `confirm-panel-in`, `host-alert-in`, `agent-surface-in`).

## Vérifications

- `corepack pnpm run build` (tsc strict + Vite) : **vert**.
- `corepack pnpm run lint` (oxlint) : **vert**, 0 warning.
- `corepack pnpm run check:i18n` : **vert** — 95 clés fr+en synchronisées, aucune nouvelle chaîne.
- Dev server temporaire (port 5199) + Chrome headless : 3 captures relues visuellement, pages intègres avec les nouveaux styles. ⚠️ **Les captures ne montrent pas le mouvement** (images fixes) — elles vérifient le rendu final des états animés, pas la cinétique. Serveur et Chrome tués après (PID vérifié).
  - `assets/2026-09-26-anim-web-01-accueil.png` — halo magenta visible, cartes rendues ;
  - `assets/2026-09-26-anim-web-02-console-animateur.png` — gate jeton (pas d'API lancée : scénario de pilotage non rejoué, voir hypothèse 4) ;
  - `assets/2026-09-26-anim-web-03-agent-questions.png` — cascade de surfaces terminée, badge rouge F-2 intact.

## Ce que j'ai dû supposer (section la plus importante)

1. **⚠️ Le halo animé de l'accueil tourne en boucle infinie** — c'est la seule animation permanente ajoutée, et elle est strictement confinée à la vitrine (transform-only, GPU-friendly). D-001 §3 réserve la sobriété aux *consoles de direct* ; l'accueil est explicitement le versant « gaming assumé » dans le code existant. Si le PO veut une accueil plus calme, couper les deux `animation` des pseudo-éléments suffit.
2. **Sortie de ConfirmDialog instantanée (pas d'animation de sortie)** : une sortie animée exigerait de garder la modale montée après la confirmation — un état visuel local découplé de l'état serveur, contraire à F-1. L'entrée est animée, la sortie est immédiate. Arbitrage réversible si le PO préfère une sortie en fondu.
3. **Compteurs animés par remount (`key = valeur`)** : la mission disait « clés React et classes conditionnelles bien gérées » — le remount est la seule méthode CSS-pure fiable pour rejouer une animation au changement de valeur sans observer le DOM en JS. Coût négligeable (un `<p>` de texte), aucune logique ajoutée.
4. **Les transitions d'état live (sweep, tick, cascade gagnants) ne sont pas capturées en mouvement** : rejouer le scénario E2E complet (API + jeton + 5 manches) pour figer des frames intermédiaires était hors de portée des captures headless — la cinétique est vérifiée par construction (CSS déclaratif, classes stables) et les états finaux par les captures. **Recommandation : une passe manuelle de 5 minutes en conditions réelles (API lancée) pour validation PO du « feel ».**
5. **Durées et courbes sont des points de départ ajustables** (statut des tokens D-001) : sweep 700 ms, tick 340 ms, cascades 55–120 ms de pas, halo 14/18 s — choisis dans la gamme « vivant mais pas distrayant », centralisées dans les partials, sans token dédié (pas de système de tokens d'animation existant — non créé pour rester dans le périmètre).
6. **`.agent-entry` est une classe prête mais non utilisée dans le JSX** : la console agent questions n'a aucune donnée réelle (contrat inexistant — rapport précédent) ; la classe documente le pattern d'entrée de liste à appliquer lors du câblage futur. Suppression trivialement simple si jugée prématurée.
7. **Aucune animation ajoutée au `PilotTokenGate`, `LanguageSwitcher`, `BrandLogo`** : surfaces utilitaires, transitions de focus/hover existantes suffisantes — le §3 demande des effets, pas du mouvement gratuit sur des formulaires de connexion.

## Non traité (hors mission)

- Mobile (`interflo-app`) : les transitions Reanimated du §3 relèvent d'une mission app séparée.
- Console agent questions : toujours non connectée (contrat inexistant) — seules les entrées de surface et le pattern `.agent-entry` sont posés.
- Pas de système de tokens de durée/easing dans `_variables.scss` (voir hypothèse 5) — à créer si une troisième surface animée arrive.
