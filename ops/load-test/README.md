# Test de charge — Interflo

> **Objectif** : valider la tenue de charge au pic (D-002 §7). « Aucun chiffre
> de ce document n'est une promesse tant que le palier 100 000 n'a pas été
> exécuté » (D-002 §7).

## Ce qu'on mesure (D-002 §7.1)

| Grandeur | Cible V1 | Statut |
|---|---|---|
| Joueurs simultanés | 100 000 | hypothèse (question PO) |
| Réponses au pic | 100 000 sur ~15–25 s (~4 000–7 000 req/s) | hypothèse |
| Connexions WebSocket | jusqu'à 100 000 (Reverb, ext-uv) | à mesurer |
| Charge utile « question » | ~300–500 octets | mesuré session 4 |

## Scripts

- **`k6-answer-peak.js`** — pic de soumissions HTTP (le chemin d'écriture
  critique). Un VU = un joueur, un POST `rounds/{round}/answer`.
- WebSocket (Reverb) : à ajouter (`k6/ws`) — mesurer la latence de
  distribution et le nombre de connexions actives.

## Prérequis

1. Une manche **ouverte** (via la console animateur, `pilot/rounds/{round}/open`).
2. Des joueurs **servis** (la fenêtre personnelle EX-20 exige `served_at`).
   En test de charge, on ne passe pas par le parcours complet (appairage →
   OTP) : on insère les `RoundPlayerState` servis en base (ou via le seeding).
3. Un jeton Sanctum par VU (ou un jeton partagé pour un premier palier).

## Paliers (D-002 §7.1)

```
1 000  → 10 000  → 100 000 VU
```

Critères de passage **à fixer à la première exécution** :
- latence de soumission (p95) ;
- taux de soumissions acceptées (vs rejetées) ;
- exactitude des compteurs (participants / survivants) ;
- aucun joueur éliminé par défaillance d'infrastructure (D-3/D-4).

## Mode dégradé (D-002 §5)

Tester aussi la **panne en plein direct** :
- tuer Redis → vérifier D-3 (fenêtre clôturée proprement, manche rejouée) ;
- tuer Reverb → vérifier D-1 (repli polling) et D-2 (nouveaux venus en polling) ;
- saturer HTTP → vérifier D-4 (rejet propre « réessaie », joueur non verrouillé).
