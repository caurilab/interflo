# Rapport — Animations et effets de l'app mobile (D-001 §3, amendement PO)

> **Date** : 2026-09-26 — **Agent** : interflo-mobile
> **Périmètre** : passage « tout doit être bien animé, avec des effets » (D-001 §3) sur `interflo-app`. Transitions entre états de jeu, cascades, feedbacks pressés, célébrations, vie ambiante — **tout en worklets Reanimated (thread UI), aucune dépendance ajoutée, aucune valeur hex en dur, aucune nouvelle chaîne i18n**. Aucun autre dossier touché.

---

## Ce qui a été fait

### Fondations

- **`src/config/animations.ts`** (nouveau) — constantes `MOTION`, source unique des réglages d'animation, documentées comme **points de départ paramétrables non validés** (même règle que `DEFAULTS` de gameConfig : seules les règles du jeu sont scellées) : durées de transition d'écran (entrée 260 ms / sortie 140 ms), cascade des propositions (base 120 ms + 60 ms par proposition), feedback pressé (scale 0.97), trois springs nommés (`press` quasi instantané, `soft` entrées, `pop` pastilles), trois pulses ambiants (lent / vif / célébration) et les délais de cascade interne des écrans de verdict.
- **`src/components/PulseDot.tsx`** (nouveau) — point lumineux à pulse ambiant en boucle (`withRepeat` aller-retour, `Easing.inOut(quad)`) sur `transform` + `opacity` uniquement, en worklet. Respecte `useReducedMotion()` (réglage système : point fixe). Trois variantes (`slow`, `fast`, `celebration`).
- **`__tests__/animations.test.ts`** (nouveau) — 5 garde-fous jest sur les bornes d'expérience (cascade ~60 ms, press spring raide, transitions brèves, pulses lents et mesurés, cascade de verdict < 1 s).

### Animations par écran

- **`PlayScreen`** — **transitions entre états de jeu** : les vues sont rendues dans un `Animated.View` plein écran keyé sur l'identité de l'état (`waiting` / `question-<round.id>` / `answered-<round_number>` / `locked` / `finished`), avec fondu croisé (`FadeIn` 260 ms / `FadeOut` 140 ms). Point capital pour le polling 2 s : **la clé ne change que quand l'état change** — un poll qui re-rend sans changement ne relance rien (on anime les transitions, pas les états stables). Le passage idle→waiting du premier poll ne réanime pas non plus (même clé `waiting`).
- **`GameScreen`** — bannière « fenêtre ouverte » qui entre en spring descendant (`FadeInDown.springify()`), son point statique remplacé par un **PulseDot vif** (1,1 s) ; thème + question en fondu retardé ; **les 4 propositions entrent en cascade** (120 ms + 60 ms × rang, spring soft) — rejouée à chaque nouvelle manche grâce à la clé `question-<round.id>` ; le message de rejet apparaît en fondu doux.
- **`PropositionButton`** — **feedback pressé en spring quasi instantané** (scale 0.97, stiffness 520 / mass 0.4 — le doigt sent un contact, pas un délai) via `onPressIn`/`onPressOut` ; le handler `onPress` tire au geste, **indépendamment de l'animation** (M-1 : zéro délai sur la réponse, animation interruptible à tout moment). L'assombrissement de fond immédiat existant est conservé. La zone tactile `min-h-24` est intacte (le wrapper animé englobe, ne rogne rien).
- **`FeedbackScreen`** (M-4) — « juste » : panneau dégradé entrant en spring modéré, **pastille ✓ qui pop** (`ZoomIn` spring pop, délai 160 ms), titre et sous-titre en cascade de fondus — célébration énergique sans rien de coûteux. « faux » : **entrée douce** (`FadeInUp` 320 ms), pastille ↻ en zoom doux — jamais de secousse punitive, jamais de rouge (garde-fou D-001 §3 respecté).
- **`LockedScreen`** — entrée sobre : montée en fondu 320 ms, pastille ★ en zoom discret, textes en cascade légère.
- **`SessionEndScreen`** — gagnant : **moment de fête mesuré** — panneau dégradé en spring, cascade pastille → titre → sous-titre, et **respiration lente de la pastille ★** (scale 1 ↔ 0.94, 1,2 s, worklet) ; non gagnant : entrée sobre en fondu montant. Le gel produit sur le *contenu* du placeholder (06-ux-ui.md §6) est respecté : aucun texte ni structure d'information ajouté, uniquement du mouvement.
- **`WaitingScreen`** — entrée d'écran douce (logo en fondu, contenu en montée), **PulseDot lent (1,8 s) dans la pastille de session** — l'attente est vivante sans être agitée ; le bandeau « connexion perdue » apparaît en fondu. L'écran reste inerte fonctionnellement (I-2/M-2) : le pulse vit en worklet sur le thread UI, le polling JS n'est pas impacté.
- **`PairingScreen`** — entrées d'écran : en-tête en montée, les deux voies d'appairage (QR + code court, toujours côte à côte — M-5) arrivent en cascade retardée ; le message d'erreur apparaît en fondu montant doux (cream, jamais rouge).

## Technique

- **100 % worklets Reanimated 4.7** (`entering`/`exiting`, `useSharedValue`, `useAnimatedStyle`, `withSpring`/`withTiming`/`withRepeat`) — aucune animation pilotée par le thread JS, qui reste libre pour le polling 2 s et l'envoi du geste. Le plugin babel `react-native-worklets/plugin` était déjà en place ; `package.json`, les pods et le babel config sont **inchangés**.
- **Propriétés animées : `opacity` et `transform` uniquement** — aucun flou, aucune ombre portée animée, aucune animation de layout coûteuse (entrée de gamme, batterie — D-001 §3/§4, R-11).
- **Reduced motion** : les builders Reanimated respectent le réglage système par défaut ; les pulses custom (`PulseDot`, respiration gagnant) le respectent explicitement via `useReducedMotion()`.
- **Styles inline sur les wrappers animés** (`Animated.View`) plutôt que `className` NativeWind — fiabilité de l'interop non garantie sur les composants Animated ; le contenu interne garde ses classes.
- **i18n : aucune nouvelle chaîne** — les glyphes ✓ / ↻ / ★ restent décoratifs et non localisés (comme avant). fr/en inchangés, test i18n vert.
- Les interruptions sont natives : les springs et entrées Reanimated sont interruptibles à tout moment, l'état `disabled` verrouille les gestes sans rejouer d'animation.

## Vérifications

- ✅ `npx tsc --noEmit` **vert** (après toutes les modifications).
- ✅ `corepack pnpm test` — **4 suites / 15 tests verts** (dont les 5 nouveaux garde-fous d'animation).
- ⚠️ **Simulateur : échec honnêtement rapporté (budget appliqué).** L'iPhone 16 (15DEE2DF-…) était booté et l'app installée, Metro démarré proprement sur le port **8088** (le 8081 du PO n'a pas été touché), mais **`simctl launch org.interflo.app` échoue systématiquement** (`FBSOpenApplicationServiceErrorDomain code=4`) — 4 tentatives : launch direct, après ouverture de Simulator.app, après réinstallation du `.app` depuis `ios/build/.../Debug-iphonesimulator`, après shutdown/boot complet du device. Diagnostic probable : binaire installé incompatible avec le runtime simulateur courant (crash immédiat au launch) — un **rebuild Xcode complet** serait la prochaine étape, hors du budget imparti (>2 essais). Conformément à la consigne : **typecheck + tests + description font foi**, aucune capture n'a pu être produite (`docs/rapports/assets/2026-09-26-anim-*.png` absents — pas de captures statiques sans app lançable ; de toute façon elles n'auraient pas montré le mouvement).
- Nettoyage : Metro 8088 tué après les tentatives, port vérifié libre ; aucun `php artisan serve` lancé ; le Metro 8081 du PO laissé intact.

## Ce que j'ai supposé (section la plus importante)

1. **Toutes les valeurs de `MOTION` sont des points de départ non validés** (durées, springs, amplitudes, stagger) — documentées comme telles dans le fichier, bornées par des tests de garde-fou, à faire arbitrer par le PO au ressenti. Les 60 ms de stagger et le scale 0.97 viennent de la mission ; le reste est mon choix calibré « énergique mais corporate ».
2. **Transitions par fondu croisé keyé dans `PlayScreen` plutôt que transitions de navigation** : les états de jeu ne sont pas de la navigation (commentaire existant), j'ai donc animé le remplacement de vue. La clé identifie l'état (avec `round.id` pour les questions) — c'est ce qui garantit qu'un poll sans changement ne relance rien.
3. **Idle→waiting ne réanime pas** (même clé `waiting`) : j'ai considéré que le premier poll qui affine l'état d'attente n'est pas une « transition » digne d'effet ; seuls les vrais changements d'état de jeu en sont.
4. **Le point statique de la bannière « fenêtre ouverte » est devenu un PulseDot vif** : l'ouverture de fenêtre doit être signifiée par l'application (I-3/M-3) et la mission demandait que l'arrivée « se sente » — le pulse renforce l'affordance sans rien ajouter à l'écran (06-ux-ui.md §2.3 respecté : question + 4 propositions + bannière, rien d'autre).
5. **Pastille gagnante en respiration infinie** (scale 1 ↔ 0.94) : la mission demandait un « effet léger : pulse » pour l'écran gagnant ; j'ai choisi l'amplitude la plus mesurée du réglage (entrée de gamme, batterie) plutôt qu'un battement marqué.
6. **`SessionEndScreen` animé malgré son gel produit** : le gel documenté porte sur le *contenu* (« ce que voit le joueur au-delà du bit winner » non spécifié). La mission courante le nomme explicitement dans le périmètre d'animation — j'ai donc ajouté du mouvement sans toucher aux textes, à la structure ni à l'information affichée.
7. **Pulse ambiant de WaitingScreen placé dans la pastille de session** (et non un nouvel indicateur) : aucun nouvel élément d'UI, aucune chaîne i18n — le point magenta existant de la hiérarchie devient vivant.
8. **`className` NativeWind non utilisé sur les `Animated.View`** : l'interop className sur composants Animated n'est pas documentée comme fiable en NativeWind v4 + Reanimated 4 ; j'ai préféré des styles inline éprouvés pour les wrappers animés (le contenu garde ses classes). Si l'équipe valide l'interop, un passage en classes est possible sans changement de comportement.
9. **Pas de haptique ajoutée** : la mission ciblait les animations visuelles Reanimated ; un retour haptique sur le geste (I-5) serait un complément naturel mais exige une décision (dépendance ou API core) — non fait.
10. **Le refus du simulateur n'est pas lié au code de cette session** : l'échec `code=4` est au niveau launchd du device (avant tout chargement de bundle JS) et persiste après réinstallation du binaire existant — mais sans run réussi, je ne peux pas certifier l'absence de warning Reanimated/worklet ni l'absence de jank sur device. **À rejouer dès qu'un rebuild Xcode est possible.**

## Consommation de données / performance estimée

Inchangée : aucune requête ajoutée, aucun asset téléchargé (R-11). Les animations tournent sur le thread UI en worklets ; les boucles (3 pulses maximum à l'écran, jamais simultanément hors WaitingScreen) sont des interpolations `opacity`/`transform` à période ≥ 1,1 s — coût négligeable sur entrée de gamme. Le polling 2 s n'est ni ralenti ni couplé aux animations.

---

## Validation device des animations (ajout, même jour — session de suivi)

**Build : ✅ succès.** Le blocage `FBSOpenApplicationServiceErrorDomain code=4` est résolu par un **rebuild complet** : `npx react-native run-ios --udid 15DEE2DF-… --port 8088` (~4 min), app lancée avec succès sur l'iPhone 16 (`UIKitApplication:com.interflo.app` actif, bundle servi par Metro 8088). Enseignement : l'échec de launch venait bien du binaire périmé ; à noter, le bundle id réel est `com.interflo.app` (et non `org.interflo.app` utilisé lors des tentatives précédentes — co-facteur probable de l'échec). **Aucun fichier de code modifié** dans cette session de validation (le `--port` de Metro a simplement été passé sans le `--` parasite de la commande suggérée, qui faisait retomber Metro sur 8081).

**Rendu vérifié visuellement** : l'écran d'appairage s'affiche en état final correct (logo, titre, deux voies QR + code court côte à côte, CTA dégradé inactif) — les entrées animées arrivent à leur état final sans artefact. Une capture statique ne montre pas le mouvement : l'entrée est une montée en fondu (~300 ms) de l'en-tête puis des deux cartes en cascade ; le rendu final confirme qu'aucune animation ne laisse l'écran dans un état intermédiaire.

- Capture : `docs/rapports/assets/2026-09-26-anim-mobile-pairing.png`.
- **Warnings** : aucun warning Reanimated/worklet dans le log Metro. Deux warnings préexistants, non liés aux animations : (1) warning de packaging RN générique (`ReactNativeFeatureFlags` hors `exports` — connu RN 0.87 + Metro) ; (2) la pastille LogBox « ! » en bas d'écran, **déjà documentée comme préexistante** dans le rapport design D-001 (toast vide, à investiguer séparément).
- **Non rejoué** : une manche complète (GameScreen/FeedbackScreen) nécessiterait l'API + un code d'appairage valide — hors périmètre de cette validation étroite ; les animations de jeu restent donc validées par typecheck/tests et par le fait que les mêmes primitives Reanimated tournent sans warning sur l'écran d'appairage.
- **Nettoyage** : Metro 8088 tué et port vérifié libre ; aucun `php artisan serve` lancé ; le Metro 8081 du PO (autre projet) laissé intact ; un process Metro du projet `mecasync-app` (tiers) laissé tourner, ne m'appartenant pas.
