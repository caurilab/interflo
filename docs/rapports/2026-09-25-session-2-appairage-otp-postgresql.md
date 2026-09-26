# Rapport — Session 2 : PostgreSQL, appairage, vérification téléphone, pnpm, simulateur

> **Date** : 2026-09-25
> **Auteur** : agent principal (Kimi Work) + sous-agents backend / mobile / outillage web
> **Feu vert PO** : « Go » sur l'appairage + vérification téléphone ; pnpm (ou bun) comme gestionnaire ; PostgreSQL déjà installé et à utiliser ; Expo accepté mais non exigé ; simulateurs iOS utilisables.
> Rapports détaillés : `2026-09-26-backend-postgres-otp-appairage.md`, `2026-09-26-mobile-pnpm-contrat-appairage-simulateur.md`.

---

## 1. Ce qui a été fait

### 1.1 API (`interflo-api`)

- **Bascule PostgreSQL 17.9** (bases `interflo` et `interflo_test`, séparées de Tvflo conformément à I-35). SQLite reste en repli commenté.
- **Vérification du numéro de téléphone (I-8)** : OTP haché, expiration, tentatives max, throttling, non-énumération des numéros, token Sanctum. Envoi SMS via contrat `SmsSender` (défaut : `LogSmsSender` — aucun provider choisi).
- **Appairage (I-14, I-15, I-18)** : génération et rotation des codes courts (alphabet sans ambigus, longueur et période en config), résolution publique du code (pointeur, n'autorise rien — INV-6), attachement avec **une seule session active** (I-17, garanti par index unique partiel en base), refus sur session terminée (I-16).
- **7 tables PROVISOIRES** (marquées, en attente de la décision modèle de données — docs/07 §5) : `players`, `phone_verification_challenges`, `tenants`, `emissions`, `game_sessions`, `pairing_codes`, `player_session`. Aucune entité de jeu.
- **Correction par l'agent principal** : un appel API authentifié sans `Accept: application/json` renvoyait une 500 (« Route [login] not defined ») au lieu d'une 401 — corrigé (`redirectGuestsTo` → null), avec test de régression.

**Contrat exposé** (5 routes, `/api/v1`) : `phone/request-code`, `phone/verify`, `pairing/resolve`, `sessions/attach` (POST/DELETE). Formes et codes d'erreur dans le rapport backend.

### 1.2 Mobile (`interflo-app`)

- Bascule **pnpm** (corepack 11.5.2, `node-linker=hoisted`, jest corrigé pour les chemins `.pnpm`).
- **Flux complet câblé sur le contrat réel** : code court → résolution → confirmation (chaîne + émission) → téléphone → OTP → attachement → écran d'attente (+ quitter). 3 nouveaux écrans, i18n 18 → 45 clés fr/en synchronisées.
- **Lancé sur le simulateur iPhone 16 (iOS 18.5) du PO** : build réussi (~7 min), app installée, parcours de bout en bout réel validé (OTP lu dans le log, resolve/verify/attach/detach conformes). Preuve : `assets/2026-09-26-mobile-pairing-screen.png`.
- Consommation du flux d'appairage : ~5–8 Ko tout compris, aucun polling (R-11).

### 1.3 Web (`interflo-web`)

- Bascule **pnpm** (lockfile régénéré, `packageManager` épinglé, README à jour). Build, lint, check i18n et dev server vérifiés — aucun écart de versions.

---

## 2. Audit (agent principal)

| Contrôle | Résultat |
|---|---|
| Suite API complète | ✅ **37 tests verts** (511 assertions), dont régression 401-sans-Accept |
| Routes exposées | ✅ 5 routes API seulement, aucune route de jeu |
| Bonne réponse côté serveur (INV-2/R-4) | ✅ sans objet — aucune logique de jeu |
| Appels synchrones sortants | ✅ aucun |
| Valeurs en dur | ✅ tout en config `INTERFLO_*` (10 valeurs paramétrables cumulées) |
| Cloisonnement tenant (INV-1) | ⚠️ structurel seulement : `tenant_id` en place, mécanisme de tenancy non choisi (docs/07 §5) — tests d'isolation à écrire quand il le sera |
| Processus résiduels | ✅ Metro, serve, Vite tous arrêtés ; le Metro/Expo d'un autre projet du PO (port 8081) n'a pas été touché |
| Simulateurs | ✅ app installée sur l'iPhone 16 uniquement, rien d'autre fermé |

---

## 3. Ce que nous avons dû supposer (section la plus importante)

1. **EX-05 implémentée sans validation PO** : un joueur déjà appairé garde sa session quand le code tourne. **À trancher** (§16 question 15 du cadrage).
2. **Aucun provider SMS** : contrat `SmsSender`, défaut log. Brancher un vrai provider = 1 implémentation + 1 ligne de binding. **Choix à faire** (coût, couverture CI).
3. **Tables provisoires malgré l'interdiction de docs/07 §5** : limitées au strict périmètre « appairage + téléphone » validé par le PO, marquées PROVISOIRES, aucune entité de jeu. Si la décision modèle de données les contredit, elles se jettent.
4. **Token mobile en AsyncStorage** (dev) — Keychain à brancher avant tout usage réel.
5. **Rotation sous la minute** : le scheduler Laravel (granularité 1 min) ne suffit pas pour 15-30 s ; la validité par timestamps est correcte, la génération rapide attendra l'architecture temps réel.
6. **Metro du projet déplacé sur le port 8088** (8081 occupé par un autre projet du PO) — réglage persistant dans l'app, commande de retour documentée dans le rapport mobile.
7. **psql local en 18.6 côté client** (serveur 17.9) — sans conséquence.
8. **`otp_verify_throttle_per_minute` (10)** ajoutée sans demande explicite — à arbitrer.
9. Expo non adopté (app bare RN fonctionnelle) ; l'agent signale qu'Expo dev-client mutualiserait les frictions multi-projets de la machine — **décision PO**.

## 4. Valeurs paramétrables introduites (points de départ non validés)

`otp_length` 6 · `otp_ttl_seconds` 300 · `otp_max_attempts` 5 · `otp_request_throttle_per_hour` 10 · `otp_verify_throttle_per_minute` 10 (env `INTERFLO_OTP_*`). Cumul session 1 : floor anti-automatisation 800 ms, fenêtre 10 s, enveloppe 15 s, rotation 20 s, code 6 / alphabet 29, relances 3, meuble 22 s, culture générale null.

## 5. Prochaine étape

Le **tableau de bord Filament** (tenants, émissions, sessions, seuils — point 5 de « ce qui peut avancer ») : c'est ce qui permettra à un opérateur de créer les sessions que l'appairage consomme déjà. Ensuite, le format élimination reste le gros morceau attendu, précédé idéalement de la décision sur l'architecture temps réel.
