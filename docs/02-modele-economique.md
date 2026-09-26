# 02 — Modèle économique

> **Statut** : 🚧 LARGEMENT OUVERT. Ce document **n'établit aucun tarif** et ne contient **aucune projection chiffrée**. Il inventorie les postes de coût connus et les questions à trancher.
>
> ⚠️ **À lire comme une liste de questions, pas comme un plan d'affaires.** Aucun chiffre d'affaires, aucun prix de vente, aucune hypothèse de volume n'a été arrêté par le PO à ce jour.

---

## 1. Ce qui est décidé

| Point | Décision |
|---|---|
| Nature du produit | Produit générique **vendu à plusieurs chaînes**, non lié à l'une d'elles (I-13) |
| Cloisonnement | Multi-tenant strict (I-38) |
| Sponsoring télécom | **Abandonné** (I-34) — voir §3 |
| Boîtier de plateau | **Conçu par client**, selon le type d'émission (I-11) |
| Fournisseur d'empreinte audio | ACRCloud, offre premium souscrite le 2026-09-25 (I-43) |

---

## 2. Les postes de coût identifiés

### 2.1 Coûts récurrents, côté éditeur

- **Empreinte audio** (ACRCloud). Souscription premium en cours. ⚠️ La consommation réelle par rapport aux quotas de l'offre **n'a pas été estimée** : elle dépend du nombre de joueurs, du nombre de fenêtres d'écoute par émission, et du nombre de chaînes clientes simultanées. À surveiller dès le premier tournage.
- **Ingestion des flux chaînes**. Chaque chaîne cliente exige un flux ingéré en continu pendant ses émissions (voir `08-chaine-de-mesure.md` §3). Coût par chaîne, pas par joueur.
- **Infrastructure temps réel**. Non dimensionnée — voir `04-architecture.md`. C'est probablement le poste dominant et le plus difficile à prévoir, parce qu'il se dimensionne sur le **pic** et non sur la moyenne.

### 2.2 Coûts par chaîne cliente

- **Boîtier physique**, conçu sur mesure selon le type d'émission. Impression 3D + électronique embarquée. Voir `05-materiel-et-sourcing.md`.
- **Poste de production supplémentaire** : l'agent qui valide les questions générées pendant l'émission (I-33). ⚠️ **À la charge de la chaîne**, et à annoncer clairement en avant-vente — c'est une charge d'exploitation récurrente, pas un coût d'installation.
- **Contrainte de conduite d'émission** : l'animateur doit meubler une enveloppe après chaque question — **ordre de grandeur** vingt à vingt-cinq secondes, non mesuré. Ce n'est pas un coût monétaire, mais c'est une contrainte éditoriale qui peut disqualifier certains formats d'émission.

### 2.3 Coûts côté joueur

- **Consommation de données.** Ce n'est pas un coût pour l'éditeur, mais c'est un **critère d'adoption** : un jeu coûteux en data exclut une partie du public visé. À traiter comme une exigence de conception, pas comme une optimisation.

---

## 3. Pourquoi le sponsoring télécom est abandonné (I-34)

Le PO avait envisagé un modèle où les opérateurs télécoms sponsorisent le jeu en promouvant la fibre, la latence devenant un argument de vente.

Deux raisons de l'abandon :

1. **Contradiction de principe.** Ce modèle revient à assumer publiquement que les joueurs les mieux connectés gagnent plus souvent — alors que le classement au temps de réaction (I-20) existe précisément pour neutraliser cet écart.
2. **Disparition du support.** Avec le format élimination en tête d'affiche, il n'y a plus de course à la vitesse du tout. Il n'y a donc plus de latence à vendre.

> ⚠️ Si ce modèle devait être rouvert, il faudrait rouvrir I-20 avec lui. Les deux ne tiennent pas ensemble.

---

## 4. ⚠️ Cadre légal — non instruit

**Aucune réponse n'est apportée ici.** Ces questions demandent un tiers compétent en droit ivoirien.

- Cadre légal des **jeux-concours dotés** en Côte d'Ivoire : règlement, dépôt, obligations envers les participants.
- **Le tirage au sort change la nature du jeu.** I-28 prévoit un tirage pour départager les survivants. Cela fait basculer le jeu de l'adresse vers le hasard, et dans beaucoup de juridictions ce n'est pas le même régime. **Ce point doit figurer explicitement dans le dossier remis au juriste.**
- Données personnelles collectées : numéro de téléphone, historique de participation, **et enregistrements audio captés par le micro**. Durées de conservation.
- Obligations propres si des **mineurs** participent.
- **Répartition des responsabilités** entre l'éditeur du produit et la chaîne diffusante — qui répond en cas de contestation d'un résultat ?

---

## 5. Le lien entre dotation et risque

C'est le point d'articulation entre le modèle économique et la conception technique, et il mérite d'être compris avant toute négociation commerciale.

**Trois failles du produit ne sont pas défendables techniquement** : le code d'appairage public (§13.3), l'assistance par modèle de langage (§8), et la survie du hasard sur comptes automatisés (§4.3). Renvois vers `INTERFLO_PRODUCT.md`.

Conséquence directe :

- **Lots symboliques** → aucune de ces failles n'est un problème. C'est de l'acquisition d'audience.
- **Lots réels et importants** → chaque faille devient une faille de règlement, et la vérification doit se faire à la remise du lot, en présence.

> **À dire en avant-vente** : le niveau de dotation d'une chaîne détermine le niveau de contrôle qu'elle devra exercer à la sortie. Ce n'est pas négociable par la technique.

---

## 6. Questions ouvertes

1. **Modèle de facturation** : abonnement par chaîne, par émission, par joueur actif, ou mixte ? Non tranché.
2. **Qui finance le boîtier** — l'éditeur l'amortit, ou la chaîne l'achète ?
3. **Consommation ACRCloud réelle** par émission et par joueur — à mesurer dès les premiers tests.
4. **Qui fournit l'URL du flux** de chaque chaîne, et avec quel engagement de disponibilité ? Si le flux tombe, la mesure de décalage tombe avec lui.
5. **Coût d'infrastructure au pic** — ne pourra être estimé qu'une fois l'architecture temps réel conçue.
6. **Dotation type** envisagée par les premières chaînes clientes : c'est cette réponse qui décide du niveau de contrôle à construire.
