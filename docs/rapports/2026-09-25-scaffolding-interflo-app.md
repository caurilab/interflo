# Rapport — Scaffolding application joueur (interflo-app)

> **Date** : 2026-09-25 (session en cours) — **Agent** : interflo-mobile
> **Périmètre** : scaffolding uniquement. Aucune logique de jeu connectée, aucun appel réseau.

## Ce qui a été fait

- Projet React Native **CLI bare** initialisé dans `interflo-app/` : RN **0.87.1**, React 19.2.3, package `com.interflo.app`, nom « Interflo ». Le repli Expo n'a **pas** été nécessaire.
- **NativeWind v4 (4.2.7)** installé et configuré selon la doc officielle (mode framework-less) : `tailwind.config.js` (preset `nativewind/preset`, Tailwind 3.4), `babel.config.js` (`jsxImportSource: "nativewind"` + preset `nativewind/babel` + plugin `react-native-worklets/plugin`), `metro.config.js` (`withNativeWind`, `input: ./global.css`), `global.css`, `nativewind-env.d.ts`.
- **React Navigation 7** (`native-stack`) avec pile racine typée (`RootStackParamList`).
- **5 écrans squelettes** (commentaires français, identifiants anglais, TODO aux points de branchement) :
  - `PairingScreen` — QR et code court **toujours visibles côte à côte** (I-14, M-5). Zone caméra = placeholder (aucun SDK caméra installé). Saisie du code court bornée à la longueur paramétrable.
  - `WaitingScreen` — hors fenêtre, application inerte (I-2, M-2) ; TODO pour l'ouverture signifiée par l'application (I-3).
  - `GameScreen` — 4 propositions (I-4, valeur scellée), chacune un gros bouton (I-5, M-1) : zone haute (`min-h-24`), espacée (`gap-5`), verrouillage après le premier geste. TODO horodatage au geste (I-6).
  - `FeedbackScreen` — juste/faux sans la bonne réponse (I-29, M-4), ton encourageant pour le cas « faux ».
  - `SessionEndScreen` — placeholder avec **TODO explicite** : non spécifié côté produit (06-ux-ui.md §2.3).
- `src/config/gameConfig.ts` : valeurs **scellées** (4 propositions I-4, 5 manches I-27, 1/3/5 gagnants I-28) séparées des **points de départ paramétrables**.
- Test de fumée Jest sur les constantes scellées et l'alphabet sans caractères ambigus (EX-03). Remplace le test de template (rendu de l'écran de démo, supprimé).
- README fusionné : contenu utile de l'ancien README (docs à lire, public visé) + instructions du template + stack réelle.

## Vérifications

- `npx tsc --noEmit` : **OK** (mode `strict` confirmé dans `@react-native/typescript-config`).
- Metro : démarre sans erreur ; **bundle iOS complet servi (HTTP 200, ~7,5 Mo)** — la chaîne Babel NativeWind + worklets compile. Process arrêté après test.
- `npm test` : 2 tests verts.
- Aucun autre dossier du monorepo touché.

## Contrat consommé

**Aucun** — le contrat d'API n'existe pas (attendu). Aucune route, aucun champ inventé.

## Ce que j'ai dû supposer (section la plus importante)

1. **Versions choisies sans arbitrage** : RN 0.87.1 (dernière stable npm), React Navigation 7, reanimated **4.7.0** (+ `react-native-worklets`, pair obligatoire) plutôt que le 3.x épinglé dans d'anciennes docs NativeWind — compatibilité RN 0.87. À re-valider au premier build natif réel (CocoaPods/Gradle non testés, pas de simulateur ici).
2. **Longueur du code court = 6** et **alphabet sans 0/O/1/I/L/5/S** : points de départ paramétrables (`DEFAULTS` dans `gameConfig.ts`), cohérents avec l'ordre de grandeur d'I-14 et EX-03, **non validés** — l'alphabet exact sera arrêté avec le serveur qui génère les codes (§16 q15).
3. **Disposition « côte à côte » de l'appairage** : interprétée en `flex-row` deux colonnes égales. Sur les très petits écrans (public visé), deux colonnes peuvent devenir étroites — le rendu réel n'a pas pu être vérifié (pas de simulateur). À valider au premier run sur appareil d'entrée de gamme.
4. **Palette provisoire** (fond sombre `#101418`, accent `#FFC531`) : justifiée par l'usage « salon sombre éclairé par la télé » (06-ux-ui.md §2.2), mais l'**identité visuelle n'est pas arbitrée** et la personnalisation par tenant est ouverte (§6) — couleurs isolées dans `tailwind.config.js` pour être remplacées.
5. **Écran de fin de session** : placeholder minimal (« La session est terminée ») car le contenu n'est pas spécifié — TODO signalé, à ne pas faire évoluer sans arbitrage PO.
6. **Verdict placeholder `false`** dans `GameScreen` pour démontrer le parcours vers `Feedback` : choix assumé (le cas majoritaire, M-4), clairement commenté comme placeholder — le verdict viendra du serveur.
7. **Aucun SDK caméra installé** pour le scan QR (zone placeholder + TODO) : installer `react-native-vision-camera` (ou équivalent) sans contrat ni test device aurait alourdi le scaffolding sans validation possible.
8. **Navigation manuelle linéaire** : les transitions sont pilotées par des callbacks locaux ; quand la couche temps réel existera, c'est l'état de session serveur qui devra piloter (commenté dans `RootNavigator`).
9. **`name` du package npm corrigé en `interflo`** (le flag `--package-name` du CLI avait mis `com.interflo.app` dans le champ `name` de `package.json` ; l'`applicationId` natif reste bien `com.interflo.app`).
10. **`.gitkeep` supprimé** et ancien README remplacé après fusion (contenu préservé dans le nouveau README).

## Valeurs paramétrables introduites

| Valeur | Défaut | Statut |
|---|---|---|
| `DEFAULTS.shortCodeLength` | 6 | ⚠️ point de départ (I-14 donne l'ordre de grandeur) |
| `DEFAULTS.shortCodeAlphabet` | `ABCDEFGHJKMNPQRTUVWXYZ2346789` | ⚠️ point de départ (EX-03) |

Les trois valeurs scellées (4 / 5 / 1-3-5) sont dans `SEALED`, verrouillées par un test.

## Ce qui reste bloqué, et par quoi

- **Logique de jeu, appairage réseau, verdicts** : contrat d'API inexistant (backend non démarré).
- **Ouverture de fenêtre en direct** : couche temps réel non conçue (cadrage §11).
- **Empreinte audio / mode mesuré** : SDK ACRCloud à intégrer plus tard ; désactivé au démarrage (I-25) ; formulation de la permission micro non spécifiée.
- **Scan QR réel** : choix du SDK caméra + test sur appareil d'entrée de gamme requis.
- **Build natif iOS/Android non vérifié** : pas de simulateur dans cet environnement — CocoaPods/Gradle restent à exécuter sur une machine de dev équipée.

## Consommation de données estimée

Nulle à ce stade (aucun appel réseau). Rappel de conception : pas de polling naïf quand le temps réel arrivera — la consommation data est un critère de conception (06-ux-ui.md §2.2).
