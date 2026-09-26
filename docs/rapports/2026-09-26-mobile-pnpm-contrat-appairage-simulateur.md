# Rapport — Bascule pnpm, câblage du contrat d'appairage, lancement simulateur

> **Date** : 2026-09-26 — **Agent** : interflo-mobile
> **Périmètre** : mission du 2026-09-26 en trois volets (bascule pnpm, câblage du contrat réel du rapport backend 2026-09-26, lancement sur simulateur iOS). Aucune route inventée : seules les 5 routes du contrat exposé sont consommées.

---

## Écrans créés / modifiés

**Créés**
- `src/api/client.ts` — client HTTP minimal (fetch nu, timeout 10 s, `ApiError` portant le statut ; aucun texte utilisateur, les écrans traduisent en i18n).
- `src/api/endpoints.ts` — les 5 routes du contrat, typées.
- `src/api/types.ts` — `PairingResolution` et `PhoneVerification`, calqués sur `PairingResolutionResource` / la réponse de `verify`.
- `src/config/api.ts` — URL de base centralisée (`http://127.0.0.1:8000`, commentaire « point de départ dev ») — jamais en dur ailleurs.
- `src/storage/authToken.ts` — persistance du token Sanctum.
- `src/session/attach.ts` — attachement commun (token frais > token persisté ; 401/403 → oubli du token + retour au flux téléphone).
- `src/screens/PairingConfirmationScreen.tsx` — confirmation « c'est bien cette émission ? » (chaîne + titre du pointeur public) avant attachement.
- `src/screens/PhoneEntryScreen.tsx` — saisie du numéro E.164 (I-8, étape 1).
- `src/screens/PhoneCodeScreen.tsx` — saisie OTP + renvoi de code (I-8, étape 2).

**Modifiés**
- `src/screens/PairingScreen.tsx` — la saisie du code court appelle réellement `POST /api/v1/pairing/resolve` ; 404 → message i18n « code invalide ou expiré » ; spinner pendant la requête. QR : placeholder inchangé (pas de SDK caméra). Le code reste traité comme pointeur public (I-15) : corps de requête uniquement, jamais stocké, jamais en Bearer.
- `src/screens/WaitingScreen.tsx` — affiche chaîne + émission de la session attachée ; bouton « Quitter la session » → `DELETE /api/v1/sessions/attach` → retour à l'appairage. En cas d'échec du DELETE, on RESTE sur l'écran (la session resterait attachée côté serveur — unicité I-17) et on le dit au joueur.
- `src/navigation/types.ts`, `src/navigation/RootNavigator.tsx` — 3 routes ajoutées (`PairingConfirmation`, `PhoneEntry`, `PhoneCode`), flux : Pairing → Confirmation → (token ? attach direct : flux téléphone) → attach → Waiting.
- `src/config/gameConfig.ts` — `DEFAULTS.otpLength: 6` (point de départ aligné sur `otp_length` serveur).
- `src/i18n/locales/{fr,en}.json` — 18 → 45 clés, fr/en synchronisées (le garde-fou jest passe).
- `package.json` — `packageManager: pnpm@11.5.2`, + `@react-native-async-storage/async-storage` 3.1.1, + `react-native-css-interop` 0.2.7 (voir hypothèses).
- `jest.config.js` — `transformIgnorePatterns` adapté au layout pnpm (voir hypothèses).
- Créés : `.npmrc` (`node-linker=hoisted`). Supprimé : `package-lock.json`.

## Contrat consommé (routes exactes, rapport backend 2026-09-26)

| Route | Usage dans l'app |
|---|---|
| `POST /api/v1/players/phone/request-code` `{phone}` → 204 | PhoneEntryScreen, bouton « Renvoyer » de PhoneCodeScreen |
| `POST /api/v1/players/phone/verify` `{phone, code}` → `200 {token, player}` | PhoneCodeScreen → token persisté |
| `POST /api/v1/pairing/resolve` `{code}` → `200 {data:{tenant{name}, emission{title}, session{id,status}}}` | PairingScreen (404 → « code invalide ou expiré ») |
| `POST /api/v1/sessions/attach` `{session_id}` → 200 (forme resolve) | PairingConfirmationScreen / PhoneCodeScreen (401/403 → flux téléphone) |
| `DELETE /api/v1/sessions/attach` → 204 | WaitingScreen, bouton « Quitter » |

Erreurs couvertes en i18n : 401/403 (attach → flux téléphone), 404 (resolve), 422 (phone / code OTP), 429 (throttle), réseau coupé ou timeout (`ApiError.status === 0`).

## Ce que j'ai dû supposer (section la plus importante)

1. **Écran de confirmation intercalé** entre resolve et attach : la mission le décrit (« nom chaîne + titre émission »), mais son wording et la présence d'un bouton retour ne sont pas spécifiés dans `06-ux-ui.md`. Point de départ à faire valider par le PO.
2. **`session.id` typé `number`** : la ressource backend renvoie la PK Eloquent brute (bigint). Si les identifiants deviennent des UUID, le type devra changer.
3. **AsyncStorage pour le token** : accepté par la mission « en dev », signalé en commentaire dans le code — Keychain/Keystore avant production (TODO sécurité explicite).
4. **Validation client E.164 minimale** (`^\+\d{8,15}$`) : la validation qui fait foi reste serveur (422). Le regex client ne sert qu'à griser le bouton.
5. **`DEFAULTS.otpLength = 6`** calé sur `otp_length` serveur ; si la config serveur change, l'écran doit suivre (pas de route de config exposée).
6. **Bouton « Renvoyer le code »** sur l'écran OTP : non demandé explicitement, mais la route existe et le besoin est évident (SMS perdu). À confirmer.
7. **WaitingScreen nécessite désormais des paramètres** (sessionId/tenant/emission). Les routes Game/Feedback/SessionEnd restent des placeholders hors flux réseau (contrat de jeu inexistant — charte : arrêt, pas de simulation).
8. **Timeout réseau 10 s** : point de départ non validé, dans `API_CONFIG`.

## État du lancement simulateur — ✅ SUCCÈS (avec preuves)

- **pnpm** : `corepack pnpm install` OK (11.5.2), `npx tsc --noEmit` vert, `pnpm test` 5/5 verts.
- **Pods** : `pod install` OK (90 pods, 228 s) après contournement du bug Ruby système 2.6 (`LANG=en_US.UTF-8`).
- **Build** : `xcodebuild -workspace Interflo.xcworkspace -scheme Interflo -configuration Debug -destination id=15DEE2DF-…` → **BUILD SUCCEEDED** en ~7 min.
- **Lancement** : install + launch sur l'iPhone 16 (iOS 18.5) OK ; écran d'appairage rendu en français, QR + code court côte à côte (M-5). Capture : `docs/rapports/assets/2026-09-26-mobile-pairing-screen.png`.
- **E2E réel au niveau API** (le parcours complet tapé dans l'UI n'est pas automatisable via `simctl`) : rotation d'un code frais → resolve 200 (`Chaîne Démo` / `Émission de démonstration`, session 1) → request-code 204 → OTP lu dans `storage/logs/laravel.log` (LogSmsSender) → verify 200 (token Sanctum) → attach 200 → DELETE 204 → re-attach 200 (idempotence) → resolve mauvais code 404 → verify faux code 422. Tout conforme au contrat.
- **Propreté** : Metro (8088) et `php artisan serve` tués après le test. Le packager du PO (yagaz-app, port 8081) n'a **pas** été touché ; seule l'app Interflo a été installée sur le simulateur.

## Incidents rencontrés et résolutions

1. **jest cassé par pnpm** : le `transformIgnorePatterns` du preset RN ignore les chemins réels `node_modules/.pnpm/…`. Motif corrigé dans `jest.config.js` (segment `.pnpm` inclus DANS le lookahead — hors du lookahead, le backtracking l'annule). Tests de nouveau verts.
2. **Metro : `react-native-css-interop/jsx-runtime` introuvable** : la résolution se fait depuis la racine du projet où pnpm ne place pas les dépendances transitives. Ajouté en dépendance directe (`0.2.7`, version exacte exigée par nativewind 4.2.7).
3. **Port Metro 8081 occupé par un autre projet du PO** (`yagaz-app`, Expo CLI, en cours depuis le 22/09) : sans y toucher, Metro Interflo lancé sur le port **8088** et l'app pointée dessus via `defaults write com.interflo.app RCT_jsLocation localhost:8088` (mécanisme officiel `RCTBundleURLProvider`). ⚠️ Ce réglage persiste dans les préférences de l'app sur ce simulateur : pour revenir au port par défaut, `defaults delete com.interflo.app RCT_jsLocation`.
4. **Bug backend constaté (à remonter à interflo-backend)** : `POST /sessions/attach` sans header `Accept: application/json` renvoie **500** (« Route [login] not defined ») au lieu de 401 — le handler non authentifié tente une redirection web. Le client mobile envoie toujours `Accept: application/json` (401 conforme vérifié), mais la robustesse de l'API le mériterait.

## Consommation de données estimée — flux d'appairage

| Étape | Requêtes | Charge utile (corps) |
|---|---|---|
| resolve | 1 POST | ~30 B envoyés / ~130 B reçus |
| request-code | 1 POST | ~25 B / 0 B (204) |
| verify | 1 POST | ~35 B / ~200 B (token + player) |
| attach | 1 POST | ~20 B / ~130 B |
| quit (optionnel) | 1 DELETE | 0 B / 0 B |

**Total : ~0,5 Ko de corps ; ~5–8 Ko en comptant en-têtes HTTP/TLS/TCP** pour le parcours complet premier appairage, **~3 Ko** quand le token est déjà persisté (resolve + attach). Aucun polling (l'écran d'attente reste inerte, I-2) — cohérent avec la contrainte « connexions limitées ». (Le bundle JS servi par Metro, ~5–10 Mo, est un artefact de dev, absent en production.)

## Hypothèse hors mission (signalée, non mise en œuvre)

Le port 8081 monopolisé par le packager Expo de `yagaz-app` montre que plusieurs projets RN cohabitent sur cette machine : Expo (dev-client) mutualiserait ce genre de friction (serveur unique, deep links par projet). Simple hypothèse à arbitrer par le PO — l'app reste bare RN et fonctionne, aucune migration engagée.
