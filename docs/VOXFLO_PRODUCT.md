# Voxflo — Note de cadrage produit

> **Nature de ce document** : NOTE DE CADRAGE PRODUIT, pas un ADR. Aucun numéro d'ADR n'est consommé ici. Aucune décision d'architecture technique n'est scellée : ce document consigne les décisions **produit** prises par le PO en session orale du 2026-08-30, et **isole explicitement** ce qui reste à instruire au source avant tout ADR.
>
> **Statut** : 🔮 CIBLE — v0.1 (2026-08-30). Zéro code, zéro table, zéro route. Rien de ce document n'est livré.
>
> **Voie** : `<DOMAINE>-<NN>` ← à attribuer par le PO (aucune voie Voxflo n'existe à ce jour).
>
> **⚠️ Réserve de fraîcheur** : ce document n'est adossé à **aucune lecture du dépôt**. Il ne cite ni fichier, ni table, ni route existante. Toute affirmation portant sur BOS (identités, Émissions, tenancy, auth) est marquée « à instruire au source » et **doit être rouverte au code** avant d'entrer dans un ADR. Règle « citer = rouvrir ».

---

## 1. Ce qu'est Voxflo (en une phrase)

Voxflo est le produit qui **identifie en direct l'intervenant du public qui prend la parole sur un plateau**, et pousse son identité vers le générateur de synthés de la régie graphique — sans que le public ait le moindre geste à faire.

Le problème résolu : aujourd'hui, entre la fiche de l'invité (nom, fonction) et le bandeau affiché à l'antenne, il n'y a **aucun pont automatisé**. Un opérateur retape le nom à la main, souvent depuis un tableur, sous la pression du direct.

---

## 2. Le trou du marché (constat de la session, à confirmer par une veille formelle)

Deux mondes existent séparément :

- **Les générateurs de synthés** en régie graphique (Chyron, Vizrt, Ross Xpression, CasparCG en libre). Ils savent afficher un bandeau à partir d'une source de données ou d'un flux réseau.
- **La saisie manuelle** de l'opérateur, dans une liste ou un tableur.

Ce qui n'existe pas comme produit intégré : **la fiche invité liée à une place physique**, mise à jour en temps réel pendant l'émission. C'est précisément le périmètre de Voxflo.

> ⚠️ Ce constat vient de la session orale et d'une recherche non exhaustive. Avant d'engager du développement, une veille produit formelle est à conduire : il serait coûteux de découvrir tard qu'un produit broadcast couvre déjà ce besoin.

---

## 3. Les trois temps du produit

### 3.1 Enrôlement (à l'entrée du public)

Un opérateur — typiquement un chef d'accueil — enregistre les personnes **au fur et à mesure** de leur entrée en salle :

- nom, prénom
- fonction / qualité (ce qui s'affichera en 2ᵉ ligne du synthé)
- **numéro de place attribuée** (ex. « siège B2 »)
- *(optionnel, cf. §5.3)* photo, si et seulement si l'option reconnaissance faciale est activée **et** le consentement recueilli

À l'issue de l'enrôlement, la salle est « peuplée » : chaque siège occupé porte une identité.

### 3.2 Direct (le chef de plateau)

Le chef de plateau dispose d'une **tablette avec le plan de salle**. Au moment où il vise une personne, il **tape le siège** — et le synthé est prêt avant même que le micro n'arrive.

C'est le geste central du produit. Voir §5 pour les sources qui l'assistent ou l'automatisent.

### 3.3 Sortie régie

L'identité de la personne active est poussée vers le générateur de synthés. La régie déclenche l'affichage.

> ⚠️ **Non tranché** : le protocole de sortie (quel générateur en cible pilote, quel transport). C'est une question technique lourde qui conditionne l'architecture — voir §8.

---

## 4. Décisions PO scellées le 2026-08-30

Ces décisions sont **acquises**. Elles ne se rediscutent pas sans arbitrage PO explicite.

| # | Décision |
|---|---|
| **V-1** | **Voxflo est un produit à part**, pas un module de BOS. |
| **V-2** | Surface = **application web dédiée sur son propre domaine** (ex. `voxflo.<domaine>`), consommant la **même API et la même authentification** que BOS. Une seule base de code métier, deux surfaces d'accès. |
| **V-3** | Cibles d'usage : **tablette d'abord**, puis mobile, puis desktop/navigateur. Le web n'est pas un sous-produit : c'est le **chemin de repli d'urgence** si la tablette du chef de plateau lâche en plein direct. |
| **V-4** | Dans BOS : **vue de consultation seulement** (invités enrôlés, historique des prises de parole). Aucun pilotage temps réel depuis BOS. |
| **V-5** | Le **plan de salle tactile est le mode principal**, pas un secours. Les capteurs sont des accélérateurs optionnels. |
| **V-6** | Les sources de détection convergent toutes vers **un seul et même événement applicatif** (« place active »). La régie ne voit **jamais** plus d'une source. |
| **V-7** | **UWB écarté** (justification §5.5). |
| **V-8** | Le plan de salle est **configurable par plateau** : la géométrie du studio est définie une fois, réutilisée à chaque émission. |
| **V-9** | Le zonage micro (§5.4) fait partie du produit, pas de l'organisation seule : le plan de salle porte des **zones**, chaque zone est associée à un **canal micro**. |

### Justification de V-1/V-2 (pourquoi pas un module BOS)

Le contexte d'usage est incompatible avec le shell applicatif de BOS. Le chef de plateau en régie a besoin d'un écran plein, gros boutons, mode sombre, temps réel, sans barre latérale ni navigation. Enfermer cet écran dans BOS ferait hériter tout le shell pour rien, **et allongerait le chemin d'accès au moment précis où il doit être le plus court** (panne en direct).

---

## 5. Les sources de détection « place active »

Quatre sources, cumulatives, convergeant vers l'événement unique de V-6.

### 5.1 Tactile — plan de salle (MODE PRINCIPAL)

Le chef de plateau tape le siège sur le plan. Toujours disponible, aucun matériel, aucune dépendance.

**Pourquoi c'est le mode principal et pas le secours** : l'objection décisive de la session est que **la personne se lève souvent avant que le micro n'arrive**. Tout dispositif qui mesure « où est le micro » échoue dans ce cas — le siège est vide, la personne est debout dans l'allée. La bonne question n'est pas *où est le micro* mais **qui a la parole**, et seul l'humain le sait à l'avance.

### 5.2 NFC passif (accélérateur)

Pastille passive collée sous l'accoudoir ou au dos de chaque siège. Sans pile, sans maintenance, coût unitaire de quelques centimes. Lecture par un lecteur embarqué dans le manche du micro, ou par le téléphone de l'assistant plateau approché du siège.

**Forces** : lecture binaire (bon siège, ou rien) — pas de dérive, pas de calibration. Et surtout : **la pastille déménage avec le banc**. Si le décor change, elle suit.

**Limite** : portée de quelques centimètres, donc inopérant si la personne s'est déjà levée (§5.1).

### 5.3 Reconnaissance faciale (option, sous réserve de consentement)

Photo prise pendant l'enrôlement (moment où l'on saisit déjà nom et fonction, donc coût marginal nul). Une caméra fixe — **distincte des caméras de tournage** — cadre les gradins. Quand quelqu'un se lève, le visage est isolé et comparé aux seules empreintes **du jour** : quelques dizaines de personnes, donc rapide et fiable dans ce volume.

**Limites reconnues** :
- **Portée** : au-delà d'environ quinze à vingt mètres, les visages du fond n'ont plus assez de pixels, même avec un bon objectif. Inopérant sur un très grand plateau, sauf caméra dédiée par zone.
- **Consentement** : point bloquant identifié par le PO. La faisabilité juridique n'est pas acquise et varie par juridiction. Traiter ce point **avant** tout développement, pas après.

**Conséquence produit** : la reconnaissance faciale est une **option activable par tenant**, jamais un prérequis. Voxflo doit être pleinement fonctionnel sans elle.

### 5.4 Zonage micro (réduction du champ de choix)

Les gradins sont découpés en zones (gauche / centre / droite, par exemple). Un micro baladeur par zone, un assistant plateau par zone — ils ne se croisent pas.

Quand le canal micro 2 est ouvert, Voxflo sait qu'on est dans la zone centre : l'écran du chef de plateau **n'affiche plus que la trentaine de sièges de cette zone**, au lieu de deux cents. Saisie plus rapide, erreur de côté impossible.

**Bénéfice secondaire, non technique mais décisif** : un micro unique pour deux cents personnes est ingérable en direct — le temps de traverser les rangées, le rythme de l'émission est perdu. Le zonage est d'abord une réponse d'exploitation.

> ⚠️ **Hypothèse à vérifier, non un fait** : il a été supposé en session que la console son expose l'état des canaux (quel micro est ouvert) et que Voxflo pourrait le lire pour filtrer automatiquement. **Cela n'a pas été vérifié.** Si la console ne l'expose pas, le chef de plateau sélectionne la zone à la main — le bénéfice reste, l'automatisme disparaît.

### 5.5 UWB — écarté (V-7)

L'ultra-large bande donne la position d'un tag à dix ou vingt centimètres près, ce qui suffirait techniquement. Elle est néanmoins écartée pour trois raisons, la troisième étant décisive :

1. **Coût** : ordre de grandeur relevé le 2026-08-30 (source Locatify, à re-vérifier avant tout achat) d'environ 10 € par m² pour l'infrastructure d'ancres, **hors pose, hors câblage réseau, hors tags**. Sur un plateau de 200 m², on est autour de 2 000 € pour la seule couverture. Les tags sont marginaux (2 ou 3 suffisent, un par micro).
2. **Calibration** : les ancres doivent être synchronisées sur la même horloge et positionnées précisément. Compter une demi-journée d'installation par studio.
3. **⚠️ La raison décisive** : le système rend des **coordonnées dans l'espace**, pas un numéro de siège. Il faut une carte qui traduise « ce point » en « siège B2 » — et **cette carte est à refaire à chaque changement de décor**. L'infrastructure d'ancres est réutilisable, le mapping siège par siège ne l'est pas. C'est exactement ce que le NFC évite, puisque la pastille voyage avec le siège.

Rapporté au NFC à quelques centimes la pastille, l'UWB ne se justifie pas.

---

## 6. Contrainte de scénographie (⚠️ hors périmètre logiciel, mais bloquante)

**Le système ne fonctionne que si le plan de salle correspond physiquement à la réalité du plateau.**

Or le décor de plateau ne prévoit pas toujours des places assises individuelles : on trouve fréquemment des **bancs alignés** sans découpage. Dans ce cas, aucune des quatre sources ne peut désigner une place.

**Exigence à porter en amont, dès la conception du décor** :

- Les bancs sont **segmentés en places numérotées**, en nombre défini par rangée.
- Un **marquage discret** matérialise la place (au sol, sur le dossier, ou sur l'accoudoir).
- La géométrie ainsi figée est saisie une fois dans Voxflo comme **plan de salle du plateau** (V-8), réutilisée à chaque émission.

> Cette contrainte est le **vrai risque d'échec du produit**. Elle ne se règle pas par du logiciel : elle se règle avec les équipes décor, avant le premier tournage.

---

## 7. Périmètre fonctionnel pressenti (V1 — non arbitré)

Découpage proposé, **à valider par le PO avant tout cadrage technique** :

| Brique | Contenu |
|---|---|
| **Plan de salle** | Définition de la géométrie d'un plateau : rangées, places, zones. Réutilisable par émission. |
| **Enrôlement** | Saisie rapide à l'entrée : identité + place. Écran optimisé pour la vitesse, pas pour l'exhaustivité. |
| **Direct** | Plan tactile temps réel, filtrage par zone, désignation de la place active. |
| **Sortie régie** | Publication de l'identité active vers le générateur de synthés. |
| **Consultation BOS** | Vue lecture seule : invités enrôlés, historique des prises de parole (V-4). |

Sources de détection §5.2 (NFC) et §5.3 (facial) : **hors V1**, à traiter comme extensions une fois le socle tactile en production.

---

## 8. Questions ouvertes — à instruire AU SOURCE avant tout ADR

Aucune de ces questions n'a de réponse dans ce document. Aucune ne doit être devinée.

### Sur le dépôt existant (lecture de code obligatoire)

1. **Identités** — l'enrôlement crée-t-il une entité neuve (« invité public », éphémère, non-utilisateur), ou réutilise-t-il un mécanisme existant de BOS ? *Rien n'a été vérifié au code.* Un invité du public n'est ni un utilisateur, ni un membre de tenant — c'est probablement une entité propre, mais cela doit être établi en lisant, pas en supposant.
2. **Rattachement à une émission** — une session Voxflo se rattache-t-elle à un objet existant (Émission, Édition, Programme) ? À instruire ; ne pas présumer de l'état de ces briques.
3. **Tenancy** — la doctrine « base unique partagée pour tous les produits » s'applique-t-elle ici ? Probablement oui par cohérence, mais à confirmer et à graver dans l'ADR, pas à hériter tacitement.
4. **Authentification** — V-2 pose la réutilisation de l'auth BOS. Le mécanisme exact (session, jeton porteur) dépend du contexte de la surface web dédiée et **doit être instruit avec le fil propriétaire de l'auth**, pas décidé ici.

### Sur le produit et le métier

5. **Protocole de sortie régie** — quel générateur en cible pilote ? Quel transport ? C'est la question la plus structurante de tout le produit : elle conditionne le modèle de données de sortie.
6. **Temps réel** — quel transport entre la tablette du chef de plateau et la régie ? Quelle latence acceptable en direct ?
7. **Résilience réseau** — que se passe-t-il si le réseau tombe en régie pendant le direct ? Un produit de direct sans mode dégradé n'est pas un produit de direct.
8. **Consentement et base légale** — pour les données d'enrôlement (nom, fonction) et *a fortiori* pour la photo et la reconnaissance faciale. À traiter avant tout développement de §5.3.
9. **Conservation des données** — combien de temps garde-t-on les identités enrôlées après l'émission ? La photo ? L'historique des prises de parole ?
10. **Console son** — expose-t-elle l'état des canaux micro (§5.4) ? Vérification terrain requise.
11. **Multi-opérateurs** — plusieurs personnes enrôlent en parallèle à l'entrée. Quelle gestion des conflits sur l'attribution des places ?

---

## 9. Rappels de discipline pour les fils qui reprendront ce document

- Ce document **n'est pas une source de vérité sur le dépôt**. Il ne cite aucun fichier, aucune ligne, aucune table. Tout ce qui touche BOS est une question, pas un fait.
- Toute surface d'écriture neuve créée par Voxflo (enrôlement, désignation de la place active, sortie régie) est une **surface d'autorité neuve** : D61 + audit Opus + fail-closed + demande explicite d'ability au fil propriétaire de l'auth.
- **Aucun paquet tiers sur les surfaces de sécurité** (autorisation, cloisonnement tenant, visibilité, anti-IDOR) — maison et prouvé par mutation.
- Diagnostic read-only **avant** tout ADR ; ADR **avant** tout code.
- Ce document ne consomme **ni numéro d'ADR, ni numéro de dette, ni numéro de voie**.

---

## 10. Ce qui ne doit pas être re-fabriqué

- ⚠️ Il **n'existe pas** de logiciel du marché identifié qui fasse « plan de salle tactile + synthé automatique » de bout en bout. Ce constat vient d'une recherche non exhaustive (§2) — ne pas le citer comme un fait établi, et ne pas non plus inventer l'inverse.
- ⚠️ « La console son expose les canaux ouverts » est une **hypothèse de session**, jamais vérifiée (§5.4).
- ⚠️ L'UWB n'est pas écarté pour cause d'imprécision — il est **assez précis**. Il est écarté pour le coût et surtout pour le re-mapping à chaque décor (§5.5). Ne pas déformer la raison.
- ⚠️ Le tactile n'est **pas** un secours des capteurs : c'est l'inverse (V-5).
