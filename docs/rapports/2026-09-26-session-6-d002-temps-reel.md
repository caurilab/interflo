# Rapport — Session 6 : architecture temps réel (D-002) implémentée

> **Date** : 2026-09-26
> **Auteur** : agent principal
> **Feu vert PO** : « avance dans le développement, on fera le test juste après »

---

## 1. Ce qui a été fait

### 1.1 D-002 — architecture temps réel (4 incréments)

| # | Incrément | Détail |
|---|---|---|
| 1 | Reverb + `RoundOpened` | serveur WebSocket 8080, push joueur à l'ouverture de fenêtre (CA-07/EX-20 respectés) |
| 2 | Client mobile Echo | `laravel-echo`+`pusher-js`+`netinfo`, abonnement `session.{id}.{population}`, poll immédiat sur `round.opened`, fallback polling D-1 |
| 3 | Canal pilote + console web | `PilotStateChanged` poussé sur `pilot.{sessionId}` à l'ouverture/fermeture/fin ; console web abonnée (Echo) |
| 4 | Redis autoritaire | `GameStateRedis` (HSETNX unicité + réponses) + flush asynchrone (`PersistAnswer`, Horizon en prod) |

### 1.2 Préparation production

- **Octane / FrankenPHP** installé (D-002 §4.4) — runtime de production, non utilisé en dev (Herd/FPM).
- **Test de charge** : script k6 + plan de validation (`ops/load-test/`, paliers 1 000/10 000/100 000, critères à fixer).
- **Modes dégradés** documentés (`ops/README.md`, D-002 §5).
- **D-003** (auth animateur) : PROPOSÉ — session email/mot de passe, pour débloquer le canal privé.

### 1.3 Vérification

- **API** : **112 tests** (12 nouveaux : broadcast joueur + pilote, champs play/state, Redis). Suite complète verte.
- **Mobile** : 18 tests, tsc, bundle dev+prod, app sur simulateur.
- **Web** : build + oxlint propres.

---

## 2. Ce que j'ai dû supposer (section la plus importante)

1. **D-002 est restée « PROPOSÉE »** dans les docs : j'ai implémenté sans validation
   explicite du PO, sur le feu vert « avance ». À re-confirmer formellement.
2. **Flush par réponse, pas par lot** : D-002 §4.3 dit « flush par lots » ; j'ai
   dispatché un job **par réponse** (plus simple). Le flush par lots (un job par
   manche après fermeture) reste un raffinement.
3. **Timing du flush en prod** : avec une file asynchrone, le compteur de
   survivants (EX-33) diffusé à la fermeture peut être en retard d'une manche si
   le flush n'a pas encore tourné. En dev (queue sync) pas d'impact. À traiter
   au moment du test de charge.
4. **Canal pilote public** (écart documenté D-002 §4.2) : le canal privé exige
   une session, que le `X-Pilot-Token` ne fournit pas — voir D-003.
5. **Downgrade Guzzle 8 → 7** : imposé par `laravel/reverb` (psr7 ^2.6 vs ^3.1
   de Guzzle 8). Laravel 13 supporte les deux ; à surveiller.
6. **Queue `sync` en dev** : pour que le flush s'exécute sans worker. Prod : `redis`.
7. **Isolation Redis en test** : purge `flushdb` dans `TestCase::setUp` (le
   `beforeEach` Pest ne s'appliquait pas — constaté et contourné).

---

## 3. Ce qui reste bloqué

1. **Test de charge** (D-002 §7) : « aucun chiffre n'est une promesse » — k6 à
   exécuter, critères de passage à fixer. C'est le prochain jalon.
2. **Auth animateur** (D-003) : à valider par le PO.
3. **Format buzzer** : bloqué par le test terrain ACRCloud (identifiants + URL
   de flux de test à fournir par le PO).
4. **Cadre légal** (tirage au sort I-28) : à instruire avant production avec lots réels.

---

## 4. Prochaine étape

Le **test de charge** (`ops/load-test/`) — c'est lui qui transforme « l'architecture
est implémentée » en « l'architecture tient le pic ». En parallèle : valider D-003
(auth animateur) pour fermer l'écart du canal privé.
