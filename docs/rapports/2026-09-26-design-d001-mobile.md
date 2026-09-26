# Rapport — Application de la direction visuelle D-001 à l'app mobile

> **Date** : 2026-09-26 — **Agent** : interflo-mobile
> **Périmètre** : refonte visuelle « fun, gaming, mais corporate » (D-001) de `interflo-app`. Tokens centralisés, 7 écrans refondus, composant dégradé **sans dépendance native**. Aucune route API inventée, aucun autre dossier touché. `SessionEndScreen` volontairement inchangé (placeholder gelé en attente d'arbitrage PO — 06-ux-ui.md §6).

---

## Tokens

- `src/config/colors.js` — **refondé** sur la palette D-001, source unique de vérité :
  - `brand` : `#D6007D` (magenta, actions principales), `light #FF4DAB` (liserés), `dark #7A0047` (état pressé / inactif) ;
  - `energy` : dégradé `#D96034` → `#D6007D` (boutons de jeu, moments forts) ;
  - `cream` : `#FCEAAC` (highlights, éléments « gain »), `dim #B9AC78` (secondaire sur accent) ;
  - `surface` : `#101418` (fond mobile), `deep #0B0F14` (puits/champs), `raised #1A2129` (cartes), `overlay #242E38` (pressé neutre) ;
  - `white #FFFFFF`.
- `tailwind.config.js` — **inchangé** : il consomme l'objet `colors` en entier, la compat est structurelle. Classes disponibles : `bg-brand`, `text-cream`, `bg-surface-deep`, `border-brand/40`, etc.
- `App.tsx` — thème React Navigation : `primary` passe de l'ancien `accent` jaune au magenta `brand.DEFAULT`. Anciens tokens `accent*` supprimés partout (aucune valeur hex en dur hors `colors.js` ; les styles inline des composants dégradés consomment les tokens, jamais de littéral).

## Composants créés

- `src/components/GradientView.tsx` — **dégradé horizontal en superposition de 24 bandes interpolées** (compromis documenté, cf. hypothèses). Recouvrement d'1 px entre bandes pour masquer les interstices d'arrondi du moteur de layout (défaut constaté en capture, corrigé). Vues statiques, aucune animation — compatible entrée de gamme.
- `src/components/PrimaryButton.tsx` — CTA en dégradé d'énergie (min-h-14, spinner, état inactif en magenta profond atténué) + variante `ghost` pour les actions secondaires.

## Écrans / composants refondus

- `PropositionButton.tsx` — cœur du jeu : **liseré lumineux en dégradé orange → magenta** autour d'une carte sombre, badge lettre (A–D) cream sur magenta, état pressé franc (fond magenta profond immédiat, sans animation), état verrouillé atténué mais visible. **`min-h-24` conservé, zone tactile jamais réduite par le liseré (M-1 intangible)** ; espacement `gap-5` côté parent conservé.
- `GameScreen.tsx` — bannière « fenêtre ouverte » (point magenta + texte cream capitales, affordance I-3), titre question plus assertif, 4 propositions refondues. Rien d'autre à l'écran pendant la fenêtre (06-ux-ui.md §2.3).
- `FeedbackScreen.tsx` (M-4) — « juste » : panneau en dégradé d'énergie, pastille ✓ cream/magenta, titre cream 4xl — célébration énergique sans animation coûteuse. « faux » : carte sombre à liseré cream, pastille ↻, titre cream chaleureux — encourageant, **jamais grisé triste, jamais de rouge punitif**. Libellés i18n inchangés (déjà au bon ton).
- `PairingScreen.tsx` — titre 3xl, zone QR à liseré magenta en pointillés, champ code bordé magenta sur puits `surface-deep` avec saisie cream, CTA dégradé. QR + code court toujours côte à côte (M-5).
- `PairingConfirmationScreen.tsx` — carte émission bordée magenta, labels cream, « Participer » en dégradé, « Retour » en ghost.
- `PhoneEntryScreen.tsx` / `PhoneCodeScreen.tsx` — carte bordée, champs cream sur puits profond, CTA dégradé ; « Renvoyer » reste un bouton secondaire discret.
- `WaitingScreen.tsx` — session courante en pastille à liseré magenta (texte cream), titre 3xl, bouton quitter en secondaire discret. L'écran reste **inerte** (I-2, M-2) : aucune animation, aucun polling ajouté.
- Messages d'erreur : `text-accent` (jaune) → `text-cream` partout — chaleureux plutôt qu'alarmiste, lisible sur fond sombre.

## Dépendances ajoutées

**Aucune.** Le dégradé est réalisé en superposition de vues (`GradientView`) précisément pour éviter `react-native-linear-gradient` (pod install + rebuild). `package.json` et les pods sont inchangés.

## i18n

- 45 → **46 clés**, fr/en synchronisées (garde-fou jest vert) : ajout de `game.windowOpen` (« Fenêtre ouverte — à toi de jouer ! » / « Window open — your move! »). Glyphes ✓ et ↻ de FeedbackScreen : décoratifs, `accessibilityElementsHidden`, volontairement non localisés. Aucun autre texte en dur.

## Vérification sur device — ✅

- `npx tsc --noEmit` vert, `pnpm test` 5/5 verts.
- Metro lancé sur le port **8088** (le 8081 du PO, `yagaz-app`, non touché), app relancée sur l'iPhone 16 (15DEE2DF-…) via le réglage persisté `RCT_jsLocation=localhost:8088`.
- Captures produites (rendu réel vérifié visuellement, fr) :
  - `docs/rapports/assets/2026-09-26-design-pairing.png` — PairingScreen ;
  - `docs/rapports/assets/2026-09-26-design-game.png` — GameScreen (navigation forcée temporairement via `initialRouteName`, **révertée immédiatement** — vérifiée par une capture post-réversion).
- Metro 8088 tué après les captures.

## Ce que j'ai supposé (section la plus importante)

1. **Dégradé en superposition de vues plutôt que dépendance native.** La mission autorisait `react-native-linear-gradient` uniquement avec rebuild réussi ; j'ai préféré le compromis sans dépendance (pods inchangés, aucun risque build, coût entrée de gamme nul). Limite assumée : 24 bandes interpolées — lisse à l'œil sur les tailles utilisées, une striation peut se deviner de très près sur un très grand panneau. Si le PO veut un dégradé parfait, la bascule vers la dépendance native est un changement isolé dans `GradientView`.
2. **Déclinaisons de palette non spécifiées par D-001** (`brand.light/dark`, `cream.dim`, `surface.raised/overlay/deep`) : D-001 dit « dérivés sombres » pour les surfaces sans donner les valeurs. J'ai dérivé des teintes cohérentes avec les fonds existants (`#101418` conservé). **À faire valider par le PO** — D-001 précise que les tokens sont « des points de départ ajustables ».
3. **Erreurs en cream plutôt qu'en rouge.** Aucune couleur d'erreur n'est définie dans D-001 ; le rouge punitif contredirait l'esprit M-4. Si une couleur d'erreur dédiée est arbitrée, elle s'ajoutera à `colors.js`.
4. **Bannière « fenêtre ouverte » sur GameScreen** : nouvelle chaîne i18n non demandée explicitement. Justification : l'ouverture doit être signifiée par l'application (I-3, M-3) et la hiérarchie gaming demandait un état de fenêtre lisible. À confirmer (wording compris).
5. **Badge lettre A–D sur les propositions** : choix gaming assumé ; les lettres viennent du rang (déjà utilisées dans le libellé placeholder), pas d'un nouveau contenu.
6. **`SessionEndScreen` non retouché** : sa propre documentation le gèle en attente d'arbitrage PO (« ce que voit le joueur quand la session meurt » non spécifié). La refonte visuelle ne lève pas un gel produit.
7. **Warning LogBox préexistant** : une pastille d'avertissement (toast blanc « ! ») apparaît en bas d'écran en dev — **déjà présente sur la capture du rapport précédent** (`2026-09-26-mobile-pairing-screen.png`), donc non introduite par cette refonte. Son texte n'a pas pu être lu (toast vide à la capture, rien dans le log Metro ni le syslog) — à investiguer séparément.
8. **Metro orphelin sur le port 8123** démarré à 15:46 depuis `interflo-app` (avant ma session, ni le 8081 du PO ni mon 8088) : **laissé tourner**, n'ayant pas été lancé par moi. À nettoyer par qui l'a démarré.

## Consommation de données estimée

Inchangée (~0,5 Ko de corps pour le parcours d'appairage complet) : la refonte est purement visuelle, aucune requête ajoutée, aucune image de fond (R-11). Le dégradé coûte 24 vues statiques par élément, aucun asset réseau.
