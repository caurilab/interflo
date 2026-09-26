# 01 — Vision et concept

> **Statut** : 🔮 CIBLE. Document de référence, dérivé de `INTERFLO_PRODUCT.md` v0.3.
> **Source d'autorité** : en cas de divergence, `INTERFLO_PRODUCT.md` fait foi. Ce document le met en récit, il ne le remplace pas.
> **⚠️ Aucune lecture du dépôt Tvflo n'a été faite.** Tout ce qui touche BOS est marqué « à instruire au source ».

---

## 1. Le produit en une phrase

Interflo est l'**application de participation en direct** aux jeux d'une émission de télévision — utilisée simultanément par le **public présent en studio** et par les **téléspectateurs à domicile**, chacun sur son propre tour.

---

## 2. Le problème que ça résout

Une émission de plateau a deux publics. Celui qui est là, qu'on filme et qu'on fait réagir. Et celui qui regarde, qu'on ne voit jamais et qui ne participe à rien.

Interflo fait jouer les deux. Le public en studio depuis son téléphone ou depuis un boîtier physique ; les téléspectateurs depuis chez eux, sur leurs propres tours.

**Ce n'est pas un jeu SMS.** Le SMS est payant, lent, et ne rend rien de visible à l'antenne. Interflo peut mettre à l'écran, en direct, le nombre de personnes qui jouent — mais ⚠️ cela dépend entièrement du **dispositif de plateau choisi** : deux des trois exemples envisagés fonctionnent sans aucun téléspectateur. Voir §6.

---

## 3. Ce qui a été écarté, et pourquoi

Deux idées de départ ont été abandonnées en cadrage. Elles reviennent naturellement à l'esprit ; ce paragraphe existe pour qu'on ne les refabrique pas.

### 3.1 Le buzzer seul

Le concept initial : un bouton, le premier qui appuie gagne.

Cela ne départage pas sur la connaissance mais sur la **latence réseau**. La fibre bat la 3G systématiquement, et le téléspectateur à domicile — qui reçoit l'image avec plusieurs secondes de décalage — ne gagnerait jamais.

C'est pourquoi **chaque proposition est elle-même un buzzer** : un seul geste vaut buzz *et* réponse. C'est ce qui unifie les deux publics.

### 3.2 La réponse vocale

Envisagée, écartée pour trois raisons cumulatives : la transcription du français ivoirien avec accents et noms propres locaux risque de **refuser de bonnes réponses** (mortel pour la confiance) ; le volume audio à traiter en quelques secondes est ingérable ; et on ne départage pas à la milliseconde des gens qui parlent deux secondes.

Le choix multiple à quatre propositions règle les trois, et supprime tout débat sur ce qui compte comme bonne réponse.

---

## 4. Deux formats, deux besoins d'émission

Le produit porte **deux formats de jeu**. Ce ne sont pas deux versions du même jeu.

| | **Buzzer** | **Élimination** |
|---|---|---|
| Produit | Un gagnant unique | Un groupe de finalistes |
| Rythme | Court, suspense | Cinq manches, montée en tension |
| Départage | Temps de réaction | Bonnes réponses cumulées |
| Dépend de la mesure de décalage | **Oui** | **Non** |
| Assistance par IA | Vole la victoire | Élargit le groupe de finalistes |

**Ordre d'activation** : on lance avec l'**élimination**, qui ne dépend d'aucune inconnue d'infrastructure. Le buzzer est développé en V1 également, mais activé ensuite, selon les retours.

⚠️ **L'élimination n'est pas immunisée contre l'assistance par IA**, elle en souffre autrement : le modèle ne vole pas une victoire, il fait survivre quelqu'un qui ne savait pas. Et avec quatre propositions, le hasard seul fait survivre une manche sur quatre — de sorte que qui pilote des milliers de comptes automatisés atteint la finale sans rien connaître. Voir `INTERFLO_PRODUCT.md` §4.3.

---

## 5. Le principe qui tient tout le reste

**Ce qui n'est pas défendable par la technique est traité par le règlement du jeu.**

Ce principe revient trois fois dans le produit :

- Le **code d'appairage** est diffusé à l'antenne, donc public. Quelqu'un peut jouer sans regarder l'émission. On peut augmenter le coût de cette triche ; on ne peut pas l'annuler.
- L'**assistance par modèle de langage** — un second téléphone qui filme la question et demande la réponse — n'est pas détectable. Aucun signal ne sort.
- Les **comptes automatisés** : avec quatre propositions, le hasard seul fait survivre une manche sur quatre. Qui en pilote des milliers atteint la finale sans rien connaître.

La **pièce d'identité** a été envisagée contre ces trois-là et écartée — non parce qu'elle serait lourde, mais parce qu'elle **ne protège de rien** : celui qui automatise son téléphone a un compte parfaitement vérifié, le sien.

Dans les trois cas, la réponse est la même : la **dotation** décide du niveau de risque acceptable, et la vérification se fait **à la sortie** (à la remise du lot), pas à l'entrée.

Corollaire à ne jamais perdre : un jeu à lots symboliques n'a aucun de ces problèmes. C'est la forte dotation qui les crée.

---

## 6. L'angle commercial

Le dispositif physique de plateau peut prendre plusieurs formes — quatre portes dont une contient le lot, une roue qui s'arrête sur le choix, ou **quatre colonnes lumineuses qui montent selon les votes du public à domicile**.

Les deux premières fonctionnent **sans aucun téléspectateur**. La troisième, non.

**C'est là qu'est l'argument de vente.** Si le dispositif ne rend pas le public à domicile visible sur le plateau, ce public reste invisible — et personne n'a de raison de télécharger l'application. En format élimination, le compteur de survivants qui descend manche après manche est à la fois le résultat du tour et le spectacle.

---

## 7. Le public visé, et ce que ça impose

Côte d'Ivoire d'abord, avec une part importante de **téléphones d'entrée de gamme** et de **connexions mobiles limitées**.

Trois conséquences qui traversent tout le produit :

1. **L'horodatage se fait au geste sur l'appareil**, pas à l'arrivée serveur — sinon une mauvaise connexion pénalise deux fois.
2. **La consommation de données est un critère de conception**, pas une optimisation tardive. Un jeu coûteux en data exclut une partie du public visé.
3. **Le micro ne s'écoute jamais en continu** — batterie, et acceptabilité de la permission.

---

## 8. Ce que ce document ne dit pas

- L'architecture temps réel n'est pas conçue. Voir `04-architecture.md` et `08-chaine-de-mesure.md`.
- Le cadre légal des jeux dotés en Côte d'Ivoire n'est pas instruit. Voir `02-modele-economique.md` §4.
- Le recouvrement fonctionnel avec Voxflo n'a pas été vérifié dans le dépôt.

---

## 9. Renvois

| Sujet | Document |
|---|---|
| Décisions scellées et leurs justifications | `INTERFLO_PRODUCT.md` |
| Exigences détaillées | `03-prd.md` |
| Découpage technique | `04-architecture.md` |
| Mesure du décalage et empreinte audio | `08-chaine-de-mesure.md` |
| Boîtier de plateau | `05-materiel-et-sourcing.md` |
