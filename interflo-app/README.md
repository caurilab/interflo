# interflo-app

Application joueur **Interflo** — React Native (CLI bare).

> ⚠️ **Scaffolding uniquement.** Aucune logique de jeu connectée, aucun appel
> réseau : le contrat d'API n'existe pas encore. Les écrans sont des squelettes
> avec des `TODO` en français aux points de branchement futurs.

## Stack

| Brique | Version | Note |
|---|---|---|
| React Native | 0.87.1 | CLI bare, `com.interflo.app`, nom « Interflo » |
| React | 19.2.3 | |
| TypeScript | ^6 | mode `strict` (hérité de `@react-native/typescript-config`) |
| NativeWind | 4.2.7 | stylisation Tailwind — validée par le PO |
| Tailwind CSS | 3.4.x | requis par NativeWind v4 |
| React Navigation | 7.x | `native-stack` |
| react-native-reanimated | 4.x | pair de NativeWind (plugin babel `react-native-worklets/plugin`) |

## Structure

```
App.tsx                     # entrée : SafeAreaProvider + NavigationContainer + thème sombre
global.css                  # directives Tailwind (NativeWind v4)
src/
  config/gameConfig.ts      # constantes scellées (I-4, I-27, I-28) + valeurs paramétrables
  navigation/
    types.ts                # RootStackParamList
    RootNavigator.tsx       # pile racine
  components/
    PropositionButton.tsx   # une proposition = un buzzer (I-5)
  screens/
    PairingScreen.tsx       # QR + code court, toujours les deux (I-14)
    WaitingScreen.tsx       # hors fenêtre : inerte (I-2)
    GameScreen.tsx          # 4 propositions, un seul geste (I-4, I-5)
    FeedbackScreen.tsx      # juste/faux, jamais la bonne réponse (I-29)
    SessionEndScreen.tsx    # ⚠️ placeholder — non spécifié côté produit
```

## Avant de coder

- `../docs/INTERFLO_PRODUCT.md` — source d'autorité
- `../docs/06-ux-ui.md` §2 — l'application joueur
- `../docs/08-chaine-de-mesure.md` — le SDK d'empreinte audio
- `../docs/13-integration-acrcloud.md` — comment le SDK se branche

## Le public visé commande la conception

Téléphones d'entrée de gamme, connexions limitées : c'est la cible, pas un cas
limite. Consommation de données = critère de conception, pas optimisation
tardive. Le micro ne s'écoute jamais en continu (I-22) ; son refus n'empêche
jamais de jouer (I-23).

## Développement

```sh
npm install
npm start          # Metro
npm run android    # nécessite un émulateur/appareil Android
npm run ios        # nécessite CocoaPods : cd ios && bundle install && bundle exec pod install
npx tsc --noEmit   # typecheck (strict)
npm test           # test de fumée sur les constantes scellées
```

## Points en attente (ne pas combler par supposition)

- Contrat d'API inexistant — aucune route, aucun champ n'est consommé.
- Transport temps réel non conçu (cadrage §11).
- Ce que voit le joueur quand la session meurt : non spécifié (06-ux-ui.md §2.3).
- Formulation de la demande de permission micro : non spécifiée.
- Identité visuelle et personnalisation par tenant : non arbitrées.
- SDK caméra pour le scan QR : non installé à ce stade.
- SDK ACRCloud : viendra plus tard, mode mesuré désactivé au démarrage (I-25).
