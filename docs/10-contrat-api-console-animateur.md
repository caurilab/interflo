# 10 — Contrat d'API : console animateur

> **Statut** : 🚧 **AUCUNE ROUTE N'EST DÉFINIE.** Voir `09-contrat-api.md` §1.
> Ce document isole les exigences propres à la surface la plus critique du produit.

---

## 1. Pourquoi cette surface est traitée à part

La console de l'animateur est la seule surface qui **agit en direct, à l'antenne, sans possibilité de rattrapage**. Une erreur ici se voit.

Elle a donc des exigences que les autres surfaces n'ont pas, et c'est pour cette raison qu'elle ne vit pas dans Filament (I-41, `04-architecture.md` §3.2) : écran plein, gros boutons, chemin d'accès court en cas de panne. Même raisonnement que pour la régie Voxflo.

Support : **tablette** (I-41).

---

## 2. Ce que la console commande

| Action | Contrainte |
|---|---|
| **Ouvrir une fenêtre** | L'ouverture est signifiée aux joueurs **par l'application**, jamais par l'image à l'écran (I-3). |
| **Fermer une fenêtre** | Hors fenêtre, **le serveur refuse** (I-2). |
| **Désigner la population** | Studio **ou** domicile. Les deux ne concourent jamais ensemble (I-1). |
| **Relancer un tour** | Format buzzer. Ordre de grandeur : jusqu'à 3 fois, à la main de l'animateur (I-7). Nombre exact non arrêté. |
| **Enchaîner les manches** | Format élimination : 5 manches (I-27). |
| **Clôturer la session** | Émission terminée, l'accès meurt (I-16). ⚠️ Qui déclenche la clôture — l'animateur, une durée programmée, ou les deux — **n'est pas tranché**. |

---

## 3. Ce que la console affiche

- Le **gagnant du tour** en format buzzer, nom et réponse, **même s'il se trompe** (I-7, I-10 pour ce qui s'affiche au studio).
- Le **compteur de participation** (I-10).
- Le **nombre de survivants** après chaque manche en format élimination (I-27).
- ⚠️ **Jamais la liste des répondants** (I-10).

---

## 4. Exigences propres au direct

| # | Exigence | Pourquoi |
|---|---|---|
| **EXA-1** | L'état de la fenêtre affiché doit refléter l'**état serveur**, jamais un état local optimiste. | Un animateur qui croit la fenêtre fermée alors qu'elle est ouverte fausse le tour. |
| **EXA-2** | La console doit indiquer visiblement quand elle a **perdu le lien** avec le serveur. | Un silence ambigu en direct est pire qu'une erreur affichée. |
| **EXA-3** | Un **chemin de repli** doit exister si la tablette tombe. Forme non arrêtée. | Le direct ne s'arrête pas parce qu'un appareil tombe. |
| **EXA-4** | Aucune action destructrice ne doit être atteignable par erreur pendant un tour. | — |
| **EXA-5** | La console doit rappeler à l'animateur l'**enveloppe à meubler** après chaque question. | **Ordre de grandeur** vingt à vingt-cinq secondes (§4.4 cadrage), non mesuré — la valeur réelle dépend de la durée de fenêtre d'écoute, encore inconnue. Contrainte technique de conduite, pas du confort. |

> ⚠️ **EXA-5 est une exigence produit, pas un agrément.** Si l'animateur enchaîne trop vite, les joueurs les plus décalés sont coupés (EX-20).

---

## 5. ⚠️ Ce qui n'est pas tranché

1. **Qui clôture la session** — animateur, durée programmée, ou les deux ? Et que voit le joueur au moment où la session meurt ?
2. **Nombre de relances** : fixe ou à la main.
3. **Forme du chemin de repli** (EXA-3).
4. **Transport temps réel** entre console et serveur — dépend de `04-architecture.md` §5.
5. **Articulation avec l'agent questions** : qui pousse la question validée à l'antenne, l'agent ou l'animateur ?

> Le point 5 mérite attention : l'agent questions (I-33) valide, mais c'est l'animateur qui mène le direct. Le passage de l'un à l'autre n'est pas spécifié, et c'est un point de friction probable en conditions réelles.
