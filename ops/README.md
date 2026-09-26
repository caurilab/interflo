# ops

Exploitation, déploiement et test de charge.

## Transport temps réel (D-002 — implémenté, non validé par test de charge)

- **Reverb** (WebSocket, port 8080) : push `round.opened` (joueur) et
  `pilot.state` (console). Redis pub/sub entre nœuds, **ext-uv obligatoire**
  en production (au-delà de ~1 000 connexions/process).
- **Redis autoritaire** pendant la fenêtre (HSETNX unicité + réponses) :
  PostgreSQL sort du chemin critique du pic, flush asynchrone (job
  `PersistAnswer`, Horizon en prod).
- **Octane / FrankenPHP** : runtime de production (installé, non utilisé en
  dev sous Herd/FPM).
- **Mode dégradé** (D-002 §5) : D-1 (repli polling) implémenté ; D-2/D-3/D-4
  documentés, à exercer par le test de charge.

## Ce qui est déjà connu

- Le dimensionnement se fait sur le **pic**, pas sur la moyenne.
- **Base séparée de celle de BOS** (I-35).
- Un **flux par chaîne cliente** doit être ingéré en continu pendant ses
  émissions.
- Un **mode dégradé en plein direct** est obligatoire.

## Test de charge

Voir `ops/load-test/` : scripts k6 + plan de validation (paliers 1 000 /
10 000 / 100 000, critères de passage à fixer). Aucun chiffre n'est une
promesse tant que le palier 100 000 n'a pas été exécuté (D-002 §7).

