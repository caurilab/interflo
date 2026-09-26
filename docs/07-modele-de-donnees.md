# 07 — Modèle de données

> **Statut** : 🚧 **SQUELETTE, PAS UN SCHÉMA.** Ce document nomme les entités et leurs relations telles que le produit les impose. Il ne contient **aucune table, aucune colonne, aucun type, aucun index**.
>
> ⚠️ **Ne pas générer de migrations à partir de ce document.** Trois inconnues bloquantes l'en empêchent — voir §5.
>
> **⚠️ Aucune lecture du dépôt Tvflo n'a été faite.** Les entités BOS mentionnées ici sont **à instruire au source**.

---

## 1. Pourquoi ce document s'arrête là

Trois questions ouvertes déterminent la forme du schéma, et aucune n'est tranchée :

1. **L'architecture temps réel n'est pas conçue** (`04-architecture.md` §5). Le profil de persistance d'un système qui encaisse cent mille écritures en quelques secondes n'a rien à voir avec celui d'une application de gestion. Ce qui est persisté, quand, et sous quelle forme, dépend de cette décision.
2. **Le recouvrement avec Voxflo n'est pas instruit.** Voxflo enrôle déjà le public studio avec son identité et sa place ; Interflo fait jouer ce même public. Réemploi ou duplication ? Cela change la nature de l'entité joueur côté studio.
3. **Ce qui est copié depuis BOS avant l'émission n'est pas arrêté** (I-36). La liste exacte détermine plusieurs entités de ce document.

Écrire un schéma maintenant reviendrait à graver trois suppositions.

---

## 2. Les entités imposées par le produit

Ces entités existent nécessairement, quelle que soit la forme finale.

### 2.1 Contexte (copié depuis BOS avant l'émission — I-36)

| Entité | Rôle | Note |
|---|---|---|
| **Tenant** | La chaîne cliente. Cloisonne tout le reste (I-38). | Existe dans BOS. Copié, pas interrogé en direct. |
| **Émission** | Le programme diffusé. La session de jeu lui est adossée (I-16). | Existe dans BOS. ⚠️ Le rattachement exact — Programme / Émission / Édition — est **à instruire au source**. |
| **Configuration de jeu** | Les réglages du tenant : format actif, durée de fenêtre (I-31), mode mesuré activé ou non (I-25), règle de fin de partie (I-28). | Propre à Interflo. |

### 2.2 Session et appairage

| Entité | Rôle | Contrainte produit |
|---|---|---|
| **Session de jeu** | Vit et meurt avec l'émission (I-16). | Un joueur n'a **qu'une session active à la fois** (I-17) — c'est un état serveur, pas un affichage. |
| **Code d'appairage** | Pointeur public vers chaîne + émission (I-15). | **Rotatif** (I-18). ⚠️ Le comportement d'un joueur appairé quand le code tourne — perd-il sa session ? — **n'est pas tranché** (§16 question 15). EX-05 propose qu'il la garde ; ce n'est pas validé. |

### 2.3 Joueur

| Entité | Rôle | Contrainte produit |
|---|---|---|
| **Joueur** | Identifié par **numéro de téléphone vérifié** (I-8). | Aucune pièce d'identité stockée. ⚠️ Le numéro est une donnée personnelle — conservation à instruire (`02-modele-economique.md` §4). ⚠️ **I-39 autorise le studio à jouer sur boîtier seul** : ce joueur-là n'a pas forcément de numéro. Comment il est identifié **n'est pas tranché** (`11-contrat-api-boitier.md` §5 question 3). |
| **Population** | Studio ou domicile (I-1). | Les deux **ne concourent jamais** l'une contre l'autre. |
| **Appareil** | Téléphone ou boîtier (I-39, I-11). | Les deux produisent **le même événement applicatif**. |

### 2.4 Jeu

| Entité | Rôle | Contrainte produit |
|---|---|---|
| **Question** | 4 propositions, une correcte (I-4). | Produite pendant l'émission (I-33), **validée par un humain** avant diffusion (EX-40). Porte sa provenance : plateau ou culture générale (I-32). |
| **Fenêtre** | Ouverte et fermée par l'animateur (I-2). | Hors fenêtre, **le serveur refuse**. La fenêtre est **personnelle**, pas absolue (EX-20). |
| **Réponse** | Le geste d'un joueur. | Horodatée **sur l'appareil** (I-6), validée serveur (I-6, I-30). |
| **Manche** | Format élimination : 5 manches (I-27). | Un joueur qui se trompe est **verrouillé jusqu'à la fin du thème** (EX-32). |

### 2.5 Mesure (format buzzer uniquement)

| Entité | Rôle | Contrainte produit |
|---|---|---|
| **Mesure de décalage** | Décalage individuel, issu de l'empreinte audio. | **Déduite du signal capté**, jamais reçue du client (I-21). Peut être absente — et l'absence est un état normal (I-23). |
| **Classement** | Deux classements distincts : mesurés et non-mesurés (I-23). | Seul le classement mesuré s'affiche au studio. |

---

## 3. Relations structurantes

```
Tenant ──< Émission ──< Session de jeu ──< Fenêtre ──< Réponse
                              │                          │
                              ├──< Code d'appairage       └──> Joueur
                              └──< Question (validée)
```

Quelques relations méritent d'être nommées explicitement, parce qu'elles portent une règle produit :

- **Réponse → Fenêtre** : une réponse hors fenêtre n'existe pas. Elle n'est pas enregistrée « invalide », elle est **refusée** (I-2).
- **Joueur → Session** : une seule active (I-17).
- **Question → Validation humaine** : une question non validée ne peut pas être diffusée (EX-40). L'état de validation fait partie de l'entité.
- **Mesure → Réponse** : la mesure est **optionnelle** et son absence est normale (I-23). Ne jamais la modéliser comme obligatoire.

---

## 4. Invariants à faire respecter par le schéma

| # | Invariant | Source |
|---|---|---|
| **INV-1** | Toute entité de jeu est **rattachée à un tenant**, et deux tenants ne se voient jamais. | I-38 |
| **INV-2** | Aucune **bonne réponse** ne doit pouvoir être servie au client. Le verdict se calcule serveur. | CA-07 |
| **INV-3** | Un **horodatage client** n'est jamais une donnée de confiance : il est borné et validé avant usage. | I-6, I-30 |
| **INV-4** | Une **mesure de décalage** n'est jamais acceptée depuis le client. | I-21 |
| **INV-5** | L'absence de mesure est un **état valide**, pas une erreur. | I-23 |
| **INV-6** | Un **code d'appairage** n'autorise rien. Il désigne. | I-15 |

---

## 5. ⚠️ Ce qu'il ne faut pas faire à partir de ce document

- **Ne pas générer de migrations.** Voir §1.
- **Ne pas supposer que le joueur Interflo est le même objet que l'invité public Voxflo.** Non instruit.
- **Ne pas recopier le modèle de tenancy de BOS par défaut.** Le cloisonnement est exigé (I-38), mais le mécanisme doit être choisi pour le profil de charge d'Interflo, pas hérité.
- **Ne pas modéliser la réponse comme une simple ligne insérée.** Cent mille insertions en quelques secondes est une décision d'architecture, pas de schéma.

---

## 6. Renvois

| Sujet | Document |
|---|---|
| Décisions et justifications | `INTERFLO_PRODUCT.md` |
| Exigences et invariants d'acceptation | `03-prd.md` |
| Séparation, couplage, temps réel | `04-architecture.md` |
| Mesure de décalage | `08-chaine-de-mesure.md` |
