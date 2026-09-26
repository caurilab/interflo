# 06 — UX / UI

> **Statut** : 🔮 CIBLE. Principes et contraintes ; **aucune maquette, aucun écran spécifié**.
> ⚠️ Les contraintes de ce document viennent du **public visé** et des **décisions produit**, pas de préférences esthétiques. Elles ne se négocient pas au moment du design.

---

## 1. Les quatre surfaces

| Surface | Support | Utilisateur | Exigence dominante |
|---|---|---|---|
| **Application joueur** | Mobile (React Native) | Grand public | Un seul geste, faible consommation, lisible sur entrée de gamme |
| **Console animateur** | Tablette | Animateur, en direct | Gros boutons, état sans ambiguïté, chemin court |
| **Console agent questions** | Non spécifié | Agent de production | Accès au time code, retour au son, correction rapide |
| **Tableau de bord** | Web (Filament) | Opérateur tenant, éditeur | Administration classique |

---

## 2. Application joueur

### 2.1 Le geste unique

**Chaque proposition est elle-même un buzzer** (I-5). Toucher une proposition vaut buzz *et* réponse. Il n'y a pas d'étape de confirmation, pas de « valider », pas de retour en arrière.

C'est la décision produit la plus structurante pour l'interface : quatre zones tactiles, rien d'autre pendant une fenêtre.

> ⚠️ Conséquence à assumer : un geste accidentel est une réponse. L'interface doit rendre les zones **assez grandes pour être visées sans erreur** et assez espacées pour qu'un frôlement ne déclenche pas la mauvaise.

### 2.2 Contraintes venant du public visé

Une part importante du public utilise des **téléphones d'entrée de gamme** sur des **connexions limitées**. Trois conséquences :

- **Consommation de données** : critère de conception, pas optimisation tardive. Un jeu coûteux en data exclut une partie du public.
- **Lisibilité** : petits écrans, luminosité variable, usage en soirée dans un salon éclairé par une télévision.
- **Batterie** : le micro ne s'écoute jamais en continu (I-22).

### 2.3 Ce que le joueur voit, et quand

| Moment | Ce qui est affiché |
|---|---|
| Hors fenêtre | L'application est **inerte** pour ce joueur. |
| Ouverture | Signifiée **par l'application** (I-3), jamais par l'image à l'écran de télévision. |
| Pendant la fenêtre | 4 propositions, et rien d'autre. |
| Après réponse | **Juste ou faux**, sans la bonne réponse (I-29). |
| Élimination, éliminé | Verrouillé jusqu'à la fin du thème (EX-32) — l'état doit être **clair et non culpabilisant**. |
| Fin d'émission | L'accès meurt (I-16). ⚠️ Ce que voit le joueur à cet instant **n'est pas spécifié**. |

> ⚠️ **Le retour « faux » est le moment le plus délicat du produit.** Sur cent mille joueurs, la grande majorité le verra à chaque tour. S'il est sec ou humiliant, ils décrochent — et c'est exactement le risque que I-29 cherchait à éviter. Le ton de cet écran compte autant que sa logique.

### 2.4 Appairage

QR **et** code court, toujours les deux (I-14). Le code court n'est pas un repli caché dans un menu : scanner un téléviseur depuis un canapé est souvent pénible (distance, reflets, taille, caméra), et sans alternative visible on perd des joueurs sur un problème purement optique.

Le code doit être **lisible à l'oral** (EX-03) : pas de caractères ambigus.

### 2.5 Permission micro

Demandée pour le format buzzer uniquement. Le refus **n'empêche jamais de jouer** (I-23).

⚠️ La formulation, le moment de la demande et le comportement au refus **ne sont pas spécifiés**. C'est un point de confiance : mal amenée, la demande se solde par une désinstallation.

---

## 3. Console animateur

Traitée en détail dans `10-contrat-api-console-animateur.md`. En résumé pour le design :

- **Écran plein, gros boutons.** L'animateur agit en direct, parfois sans regarder.
- **L'état de la fenêtre doit être lisible d'un coup d'œil**, et refléter l'état serveur, jamais un état local optimiste.
- **La perte de lien doit être visible.** Un silence ambigu en direct est pire qu'une erreur affichée.
- **Rappel de l'enveloppe à meubler** après chaque question — **ordre de grandeur** vingt à vingt-cinq secondes, non mesuré, plusieurs fois par thème. C'est une contrainte technique, pas du confort.

> Ce n'est pas Filament. Même raisonnement que pour la régie Voxflo : chemin d'accès court en cas de panne en plein direct.

---

## 4. Affichage studio

- **Jamais la liste des répondants** (I-10).
- Le **gagnant du tour** en format buzzer, nom et réponse, même s'il se trompe (I-7).
- Le **compteur de participation** (I-10).
- Le **nombre de survivants** après chaque manche en format élimination (I-27).

> En format élimination, le compteur qui descend est à la fois le résultat du tour et le spectacle. C'est un meilleur objet visuel qu'une liste de noms illisible.

---

## 5. ⚠️ Console agent questions — non spécifiée

C'est la surface la plus récente (I-33, 2026-09-25) et la moins définie.

Ce qu'elle doit permettre :

- Lire la **transcription horodatée** du direct.
- Voir les **questions proposées** par le modèle de langage.
- **Cliquer sur un time code** pour retrouver le passage, et **remonter au son enregistré** si le souvenir manque.
- **Corriger** la question avant validation.
- **Valider** — sans quoi rien ne part à l'antenne (EX-40).

Contrainte non négociable : cela se fait **pendant l'émission**, sous pression de temps. L'interface doit être rapide, pas complète.

> ⚠️ **Point de friction probable** : l'articulation entre l'agent qui valide et l'animateur qui mène le direct n'est pas spécifiée. Qui pousse la question validée à l'antenne ? Voir `10-contrat-api-console-animateur.md` §5.

---

## 6. ⚠️ En attente d'arbitrage

- **Bibliothèque de stylisation React Native.** Recommandation : **NativeWind**, qui porte la grammaire Tailwind sur React Native et garde une syntaxe commune avec le web. **Non validé par le PO.**
- **Identité visuelle** d'Interflo : non abordée.
- **Personnalisation par tenant** : une chaîne peut-elle habiller l'application à ses couleurs ? Non tranché — et cela a des conséquences sur l'architecture de thème.
- **Surface web joueur** en repli si l'application ne s'installe pas : évoqué, non tranché.
- **Ce que voit le joueur quand la session meurt** (§2.3).
