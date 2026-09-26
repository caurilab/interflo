# 04 — Architecture

> **Statut** : 🔮 CIBLE partielle. Les **principes** sont tranchés ; la **couche temps réel n'est pas conçue**.
> **⚠️ Aucune lecture du dépôt Tvflo n'a été faite.** Tout ce qui concerne l'API BOS est **à instruire au source** avant d'être implémenté.
> **⚠️ Ce document ne contient ni route, ni table, ni signature.** Voir `09-contrat-api.md` pour l'état du contrat.

---

## 1. Le principe fondateur

**Interflo est un produit à part** : point d'entrée séparé, service séparé, base séparée (I-35).

Quatre raisons, la première décisive :

1. **Isolation de panne.** Si le jeu et la régie partagent le même point d'entrée, un pic Interflo peut faire tomber BOS **en plein direct**. Or pendant l'émission BOS n'est pas au repos : c'est la conduite, c'est Voxflo qui pousse les synthés. Le jour où le jeu marche trop bien, c'est l'antenne qui tombe.
2. **Surface d'attaque.** Employés de chaînes d'un côté, grand public de l'autre. Exposer l'API BOS au grand public l'élargit énormément, pour rien.
3. **Nature technique.** BOS est du requête-réponse classique. Interflo est du temps réel bidirectionnel, avec pics concentrés et arbitrage à faible latence. Ce n'est pas le même type de serveur.
4. **Tenancy.** BOS est cloisonné par chaîne. Interflo est générique et non lié à une chaîne (I-13), tout en restant multi-tenant (I-38).

> ⚠️ **Point d'entrée, service et base vont ensemble ou l'exercice est décoratif.** Séparer l'API tout en partageant la base ne protège de rien : le pic Interflo sature la base, et BOS tombe quand même.

---

## 2. Le couplage avec BOS, et comment il est neutralisé (I-36)

Le PO souhaite que BOS puisse consommer les données d'Interflo, et qu'Interflo dispose du contexte tenant. Pris naïvement, cela annule le bénéfice de la séparation : si Interflo interroge BOS pendant le jeu, un BOS lent ou indisponible tue le direct.

**La forme retenue est unidirectionnelle et anticipée :**

| Moment | Ce qui se passe |
|---|---|
| **Avant l'émission** | Interflo **copie** ce dont il a besoin : chaîne, émission, configuration du tenant. |
| **Pendant l'émission** | Interflo joue **sans rien demander à personne**. Aucun appel synchrone vers BOS. |
| **Après l'émission** | BOS lit les résultats via l'API Interflo. Sans danger. |

> ⚠️ **Règle absolue** : aucun appel synchrone vers BOS pendant une fenêtre de jeu. Si un tel appel apparaît dans une implémentation, la séparation est dissoute.

### 2.1 Deux chemins d'authentification (I-37)

| Population | Chemin | Peut dépendre de BOS ? |
|---|---|---|
| Opérateur tenant (configure le jeu) | Auth BOS | Oui — c'est la seule qui peut |
| Téléspectateur / public studio | Numéro de téléphone vérifié (I-8) ⚠️ sauf boîtier seul — non tranché | **Jamais** |

Le téléspectateur ne touche **jamais** l'API BOS, sous aucune forme.

---

## 3. Les composants

```
interflo-app/        Application joueur — React Native
interflo-web/        Console animateur (tablette) + console agent questions
interflo-api/        API Laravel + tableau de bord Filament + couche temps réel
interflo-firmware/   Boîtier de plateau
```

> ⚠️ Le découpage ci-dessus est une **proposition de structure**, calquée sur l'arborescence fournie par le PO. La place exacte de la couche temps réel (dans `interflo-api` ou dans un service à part) **dépend de §5 et n'est pas tranchée**.

### 3.1 Stack (I-41)

| Composant | Technologie |
|---|---|
| API | **Laravel**, dernière version |
| Application joueur | **React Native** |
| Tableau de bord | **Filament** |
| Console animateur | Surface dédiée, tablette |

> ⚠️ **En attente d'arbitrage** : la bibliothèque de stylisation React Native. Recommandation : **NativeWind**, qui porte la grammaire Tailwind sur React Native. **Non validé par le PO.**

> **Sur la parenté avec BOS** : le PO demande de rester « à peu près dans la même structure ». D'après `tvflo-backend.md` (document de projet, **pas** une lecture du dépôt), BOS tourne sur Laravel 13, PHP 8.4, PostgreSQL 17, avec Stancl Tenancy, Spatie Permission, Sanctum, nwidart/laravel-modules et Pest. **Ces choix sont à confirmer pour Interflo, pas à recopier par défaut** : le profil de charge et la population d'utilisateurs diffèrent radicalement.

### 3.2 Filament n'est pas la console de l'animateur

Filament convient à l'administration : tenants, configuration, banque de questions, seuils, résultats.

Il n'est **pas** fait pour la console de l'animateur, qui ouvre et ferme des fenêtres à la seconde en plein direct. Celle-ci mérite une surface dédiée — écran plein, gros boutons, chemin d'accès court en cas de panne. Même raisonnement que pour la régie Voxflo.

**Deux surfaces, pas une.**

---

## 4. Ce qui est déjà contraint par le produit

Ces contraintes ne sont pas négociables au moment de l'implémentation ; elles viennent de décisions produit.

| Contrainte | Origine | Conséquence technique |
|---|---|---|
| Le serveur refuse hors fenêtre | I-2 | L'autorisation de répondre est un **état serveur**, pas un état client. |
| Horodatage au geste sur l'appareil | I-6 | Le serveur reçoit un temps client et doit le **valider**, pas le croire. |
| Rejet des temps impossibles | I-6 | Borne physiologique basse + borne haute, contrôlées serveur. |
| Retour individuel juste/faux | I-29 | **Un bit par joueur.** Pas de calcul par joueur, pas de pourcentage à diffuser. |
| La bonne réponse ne doit pas fuiter | CA-07 | Le verdict se calcule **serveur** ; le client ne reçoit jamais la bonne réponse. |
| Fenêtre personnelle, pas absolue | §4.2 cadrage | L'enveloppe serveur = fenêtre + pire décalage attendu. |
| Une seule session active | I-17 | Modèle de session côté serveur, pas simple affichage. |
| Multi-tenant strict | I-38 | Cloisonnement des joueurs et des données par tenant, testé. |

---

## 5. ⚠️ La couche temps réel — NON CONÇUE

**Rien dans ces documents ne répond à « cent mille personnes qui répondent en même temps ».**

Ce qui est acquis rend le problème *traitable* — choix multiple plutôt que vocal, horodatage au geste, refus serveur hors fenêtre, retour à un bit. Mais l'architecture reste entièrement à concevoir.

### 5.1 Les questions à instruire

- **Pic de charge** concentré sur quelques secondes, plusieurs fois par émission. Le dimensionnement se fait sur le pic, pas sur la moyenne.
- **Transport temps réel bidirectionnel** vers un très grand nombre de clients simultanés.
- **Deux profils de charge différents** :
  - *Buzzer* : ordonner des horodatages à faible latence, produire un gagnant.
  - *Élimination* : compter des bonnes réponses, sur une population qui fond à chaque manche.
- **Diffusion du retour individuel.** Désormais dimensionnable grâce à I-29.
- **Mode dégradé** : que se passe-t-il si l'infrastructure sature **en plein direct** ?

> **Un produit de direct sans mode dégradé n'est pas un produit de direct.** C'est le chantier le plus lourd du produit, et il mérite sa propre décision d'architecture avant toute ligne de code temps réel.

### 5.2 Ce qu'il ne faut pas faire en attendant

- Ne pas choisir un transport temps réel « par défaut » parce qu'il est déjà dans BOS. Le profil de charge n'a rien à voir.
- Ne pas implémenter le classement buzzer avant d'avoir tranché §5.1 — c'est le profil le plus exigeant.
- Ne pas supposer que ce qui tient en démonstration tiendra au pic.

---

## 6. Question ouverte : monorepo ou dépôt séparé ?

Non tranché par le PO. Les autres produits de la suite sont dans le monorepo, et Voxflo y ira.

**À distinguer** : monorepo ≠ même déploiement. La séparation décidée en I-35 est une séparation **d'exécution** (point d'entrée, service, base). Placer Interflo dans le monorepo ne recrée **aucun** couplage d'exécution.

**Recommandation** : dans le monorepo, avec une **règle explicite d'interdiction d'import direct** entre Interflo et BOS — tout passe par l'API, conformément à I-36.

C'est ce risque-là qui est réel : la proximité dans le dépôt invite à importer du code BOS au lieu de passer par l'API, et c'est ainsi que la séparation se dissout sans que personne ne l'ait décidé.

La décision est par ailleurs **peu coûteuse à inverser** dans les deux sens.

---

## 7. Renvois

| Sujet | Document |
|---|---|
| Décisions et justifications | `INTERFLO_PRODUCT.md` |
| Exigences | `03-prd.md` |
| Entités et leurs relations | `07-modele-de-donnees.md` |
| Empreinte audio, décalage, flux chaînes | `08-chaine-de-mesure.md` |
| État du contrat d'API | `09-contrat-api.md` |
