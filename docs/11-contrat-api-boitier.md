# 11 — Contrat d'API : dispositif de plateau

> **Statut** : 🚧 **NON INSTRUIT.** Le protocole entre le serveur et le boîtier physique est une question ouverte depuis le premier cadrage. Ce document la cadre, il ne la résout pas.

---

## 1. Le principe (I-11)

Les **4 boutons du boîtier** et les **4 lignes du téléphone** déclenchent **le même événement applicatif**.

Ce qui change, c'est ce que le geste **actionne sur le plateau** : quatre portes qui s'ouvrent, une roue qui s'arrête, quatre colonnes lumineuses qui montent selon les votes du public à domicile.

Le boîtier est donc la **déclinaison matérielle de la même interface**, pas un second produit.

---

## 2. Deux flux distincts, à ne pas confondre

| Flux | Sens | Rôle |
|---|---|---|
| **Entrée** | Boîtier → serveur | Un joueur du studio répond. Équivalent strict d'un geste sur téléphone. |
| **Sortie** | Serveur → plateau | Le résultat actionne le décor : portes, roue, colonnes lumineuses. |

⚠️ **Ce ne sont pas les mêmes exigences.** L'entrée doit être fiable et horodatée comme n'importe quelle réponse. La sortie est un pilotage de scène, avec des contraintes de synchronisme visuel qui n'ont rien à voir.

---

## 3. Ce qui est décidé

| Point | Décision |
|---|---|
| Conception | **Par client**, selon le type d'émission (I-11) |
| Fabrication | Boîtier imprimé en 3D (I-11) |
| Électronique | ⚠️ **Non arbitrée.** Une carte de type Arduino a été évoquée, sans décision. |
| Rôle commercial | Le dispositif doit rendre le public à domicile **visible à l'antenne** (I-12) |
| Usage studio | Le public studio joue sur téléphone, sur boîtier, ou les deux (I-39) |

---

## 4. ⚠️ Pourquoi la sortie compte plus qu'elle n'en a l'air

Trois dispositifs ont été évoqués. Deux d'entre eux — les portes et la roue — fonctionnent **sans aucun téléspectateur**.

Le troisième, les **colonnes lumineuses qui montent selon les votes du public à domicile**, ne fonctionne pas sans eux. Et c'est précisément celui qui porte l'argument commercial du produit : sans visibilité à l'antenne, personne n'a de raison de télécharger l'application.

**Conséquence pour le protocole de sortie** : il doit pouvoir porter un **flux continu de comptage** pendant une fenêtre, pas seulement un résultat final. Une colonne qui monte en direct n'est pas un état à afficher une fois, c'est une valeur qui évolue seconde après seconde.

> C'est la seule exigence de ce document qui ne peut pas attendre l'architecture temps réel : elle en fait partie.

---

## 5. Questions ouvertes

1. **Protocole** entre serveur et dispositif. Rien n'est arrêté.
2. **Électronique embarquée** : carte, alimentation, connectivité du boîtier joueur.
3. **Appairage des boîtiers studio** : comment un boîtier est-il rattaché à un joueur, et à une place ? ⚠️ Recoupe la question Voxflo, qui enrôle déjà le public studio avec sa place — **à instruire au source**.
4. **Alimentation et autonomie** sur la durée d'un tournage.
5. **Mode dégradé** : que se passe-t-il si un boîtier tombe en plein direct ? Le joueur bascule-t-il sur son téléphone ?
6. **Horodatage côté boîtier** : le boîtier a-t-il une horloge à synchroniser comme un téléphone (I-6), ou son geste est-il horodaté par une passerelle de plateau ?

> ⚠️ **La question 6 n'est pas un détail.** Si le boîtier est filaire et local, son geste n'a pas de latence réseau et l'horodatage au geste (I-6) perd son objet — mais seulement pour le studio, qui ne concourt de toute façon jamais contre le domicile (I-1). À trancher explicitement plutôt qu'implicitement.

---

## 6. Renvois

| Sujet | Document |
|---|---|
| Fabrication, impression 3D, sourcing | `05-materiel-et-sourcing.md` |
| Règles générales de contrat | `09-contrat-api.md` |
| Couche temps réel | `04-architecture.md` §5 |
