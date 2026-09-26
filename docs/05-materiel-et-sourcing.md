# 05 — Matériel et sourcing

> **Statut** : 🚧 **LARGEMENT OUVERT.** Aucun composant n'est choisi, aucun fournisseur identifié, aucun coût estimé.
> Ce document rassemble ce qui est décidé et liste ce qu'il faut instruire.

---

## 1. Ce qui est décidé

| Point | Décision |
|---|---|
| Nature | **Boîtier imprimé en 3D**, déclinaison matérielle de l'interface à 4 propositions (I-11) |
| Interface | **4 boutons**, équivalents stricts des 4 lignes du téléphone (I-11) |
| Conception | **Par client**, selon le type d'émission (I-11) |
| Rôle | Rendre le public à domicile **visible à l'antenne** (I-12) |
| Usage | Le public studio joue sur téléphone, sur boîtier, ou les deux (I-39) |

---

## 2. Deux objets différents sous le même mot

Le mot « dispositif » a désigné deux choses en cadrage. Il faut les séparer avant tout sourcing.

### 2.1 Le boîtier joueur

Un objet par spectateur en studio. Quatre boutons, une liaison vers le serveur.

Contraintes : coût unitaire (multiplié par le nombre de places), autonomie sur la durée d'un tournage, robustesse au maniement par le public, appairage à un joueur et à une place.

### 2.2 Le décor actionné

Un objet par plateau. Quatre portes, une roue, ou **quatre colonnes lumineuses qui montent selon les votes du public à domicile**.

Contraintes : synchronisme visuel avec le direct, fiabilité absolue (une panne se voit à l'antenne), et — pour les colonnes — capacité à afficher une valeur **qui évolue en continu** pendant une fenêtre, pas un résultat final.

> ⚠️ **Le décor actionné porte l'argument commercial.** Les portes et la roue fonctionnent sans aucun téléspectateur ; les colonnes non. Sans visibilité à l'antenne du public à domicile, personne n'a de raison de télécharger l'application.

---

## 3. ⚠️ Ce qui n'est pas arbitré

- **Électronique embarquée.** Une carte de type Arduino a été évoquée en séance, **sans décision**. Ne pas la traiter comme acquise.
- **Connectivité** du boîtier joueur : filaire, radio, Wi-Fi local ? Cela change tout — coût, autonomie, latence, et même la pertinence de l'horodatage au geste (voir `11-contrat-api-boitier.md` §5, question 6).
- **Alimentation et autonomie** sur une durée de tournage réelle.
- **Fabrication** : impression en interne ou sous-traitée ? Volumes par plateau ?
- **Sourcing des composants** en Côte d'Ivoire : disponibilité, délais, pièces de rechange.
- **Qui finance** — l'éditeur amortit, ou la chaîne achète ? Voir `02-modele-economique.md` §6.

---

## 4. Une piste notée en passant

Le moteur d'empreinte audio **Olaf** tourne sur microcontrôleur (type ESP32) et se compile en WebAssembly. ⚠️ Le chiffre de mémoire parfois cité vient de la publication JOSS d'Olaf (`08-chaine-de-mesure.md` §8), **pas d'un essai** : à vérifier à la source avant tout dimensionnement.

Ce n'est **pas** une recommandation : le boîtier de studio n'a aucun besoin de mesure de décalage, puisque le studio ne concourt jamais contre le domicile (I-1) et voit l'action en direct sans décalage.

La note est conservée uniquement au cas où un dispositif embarqué **hors studio** serait un jour envisagé.

---

## 5. Prochaine étape

Avant tout sourcing, trancher §3 — en particulier la **connectivité**, qui commande le coût, l'autonomie et le protocole (`11-contrat-api-boitier.md`).

Un prototype à faible nombre de places, sur une émission réelle, apprendrait plus que n'importe quelle spécification écrite en amont.
