# Rapport — Session 4 : format élimination jouable de bout en bout + passage animations

> **Date** : 2026-09-25
> **Auteur** : agent principal (Kimi Work) + sous-agents backend / mobile / frontend
> **Feu vert PO** : « Go » sur le format élimination ; puis « tout devrait être super bien animé, avec quelques effets ».
> Rapports détaillés : `2026-09-26-backend-moteur-elimination.md`, `2026-09-26-mobile-jeu-elimination.md`, `2026-09-26-frontend-console-animateur-api.md`, `2026-09-26-mobile-animations.md`, `2026-09-26-web-animations.md`.

---

## 1. Le format élimination est jouable de bout en bout

**Le cœur du produit existe et tourne.** Une partie réelle a été jouée sur le simulateur du PO contre l'API réelle, pilotée depuis la console animateur réelle.

### API — moteur (105 tests verts, 1264 assertions)

- Cycle de vie des manches : fenêtre **ouverte/fermée par l'animateur, état serveur** (I-2) ; refus hors fenêtre même pour un client modifié (CA-02).
- **Fenêtre personnelle** (EX-20) : chaque joueur a son `served_at` ; son enveloppe court depuis ce moment, pas depuis l'ouverture animateur. C'est le piège numéro 1 du cadrage, il est implémenté correctement.
- **Plancher anti-automatisation** (I-30) distinct de la durée de fenêtre (I-31) ; horodatage au geste borné et validé (I-6, CA-03 : horloge falsifiée rejetée).
- **Verrouillage** (EX-32) : qui se trompe est éliminé jusqu'à la fin du thème ; compteur de survivants exact après chaque manche (EX-33).
- **Fin de partie** (I-28) : tous les survivants ou tirage `random_int` à 1/3/5 (scellé), idempotent, persisté, avec rappel de la bascule réglementaire §10.4 partout où le tirage apparaît.
- **CA-07 prouvé par test** : toutes les charges utiles joueur sont inspectées, `correct_index` n'y apparaît jamais. Le verdict est un bit (R-5).
- Banque de questions Filament avec **validation humaine explicite** (EX-40) — une question non validée ne peut pas être attachée à une manche, refus côté service, pas juste côté UI.
- 7 migrations PROVISOIRES de jeu (marquées docs/07 §5), contraintes en base (4 propositions, manches 1..5, winners 1/3/5).

### Mobile — le joueur joue (10 tests + E2E complet sur simulateur)

Machine à états pilotée par `play/state` (idle → waiting → question → answered → locked → finished), polling provisoire 2 s (premier plan uniquement, jamais deux requêtes en vol — R-11), **offset d'horloge** calculé sur `server_time` (I-6/EX-16), horodatage **au geste**. E2E démontré : appairage → question réelle → bonne réponse → mauvaise → rejet fenêtre dépassée → élimination → fin de partie gagnante. 7 captures dans `assets/2026-09-26-gameplay-*.png`.

### Console animateur — le pilotage réel

Jeton pilote (⚠️ provisoire), création de partie, OUVRIR/FERMER avec confirmation (F-4), état serveur jamais optimiste (F-1), badge de lien rouge immédiat à la perte et vert au retour (F-2 — démontré en coupant l'API pendant le test), compteurs réels 4 participations / 3 survivants, MANCHE SUIVANTE grisée tant que la fenêtre est ouverte, fin de partie avec gagnants. E2E documenté en 11 captures (`console-live-01…11`).

## 2. Passage animations (amendement D-001 §3)

Le PO veut un produit vivant : « tout bien animé, avec des effets ». Consigné dans D-001 §3 avec ses garde-fous, puis appliqué :

- **Mobile** : 100 % worklets Reanimated (zéro dépendance ajoutée) — transitions fondues keyées sur l'identité d'état (un poll stable ne relance rien), propositions en cascade 60 ms, press spring 0.97 sans retarder le geste (M-1), célébration « juste » / entrée douce « faux » (M-4), fête mesurée du gagnant. Constantes `MOTION` centralisées, marquées non validées. 15 tests verts. Rebuild iOS fait, app relancée sur le simulateur, aucun warning Reanimated.
- **Web** : CSS pur (aucune lib JS) — halo d'énergie animé à l'accueil, sweep dégradé à l'ouverture de fenêtre, compteurs qui tickent au changement de valeur, chips gagnants en cascade, modale animée à l'entrée (sortie instantanée, F-1 prime). **Les handlers d'action sont byte-identiques — zéro délai ajouté sur les gestes de direct.** `prefers-reduced-motion` respecté (vérifié dans le CSS compilé). Badge rouge toujours immédiat à la perte de lien (F-2 préservé par construction).

⚠️ Les captures ne montrent pas le mouvement : une passe manuelle de quelques minutes est recommandée pour valider le ressenti.

## 3. Audit (agent principal)

| Contrôle | Résultat |
|---|---|
| Suite API complète | ✅ 105 tests verts, 0 régression |
| CA-01 à CA-07 (dans la limite du jouable aujourd'hui) | ✅ couverts par des tests dédiés (CA-05 reste lié au micro — buzzer) |
| Captures relues | ✅ gameplay mobile (question, feedback, locked, gagnant) + console live |
| Invariants INV-1..8 / R-1..R-11 | ✅ respectés ; INV-1 reste structurel (mécanisme de tenancy non choisi) |
| Aucun appel synchrone BOS | ✅ |
| Processus résiduels | ✅ tous tués ; process des autres projets du PO intacts (8081, mecasync) |

## 4. Ce que nous avons dû supposer (section la plus importante)

1. **⚠️ Auth animateur inventée** : token opaque `X-Pilot-Token` par session, en clair en base, sans rotation ni révocation. Rien n'est spécifié dans le cadrage — **à trancher** (c'est le point le plus sensible de la session).
2. **Population joueur** : défaut `home` ; rien ne permet de devenir `studio` tant que le recouvrement Voxflo n'est pas instruit (inconnue n°2).
3. **Persistance des réponses et polling HTTP PROVISOIRES** : corrects, ne prétendent pas tenir le pic de 100 000 écritures — l'ADR temps réel reste le chantier le plus lourd du produit.
4. **RELANCER retiré de la console élimination** (la relance est au buzzer) → MANCHE SUIVANTE.
5. **Manque du contrat** : aucune route ne liste les questions validées pour la création de partie — champ id sobre en attendant.
6. **Verrouillage déduit** (pas de colonne) : personne ne rejoint une partie en cours de route.
7. **Valeurs introduites** (points de départ non validés) : `clock_tolerance_ms` 2000, `pilot_token_length` 48, polling 2 s, constantes MOTION (260/140 ms, stagger 60 ms…).
8. **Fenêtre 10 s supposée non représentative du direct** : le test E2E a vu de vrais rejets `window_exceeded` — le comportement est correct, la valeur est à calibrer au premier tournage.
9. `simctl launch` avait échoué sur un mauvais bundle id (`org.interflo.app` vs `com.interflo.app`) — résolu par rebuild, consigné.

## 5. Prochaine étape

Le **format buzzer** est le seul gros morceau fonctionnel restant côté jeu — mais il dépend de la chaîne de mesure (test terrain ACRCloud, identifiants à demander au PO) et de l'ADR temps réel. Alternative immédiate sans dépendance : **l'ADR temps réel** (transport joueur + persistance au pic + mode dégradé) — c'est lui qui transforme les deux provisoires (polling, insertions) en architecture de direct.
