# Interflo — Note de cadrage produit

> **Nature de ce document** : NOTE DE CADRAGE PRODUIT, pas un ADR. Aucun numéro d'ADR n'est consommé ici. Ce document consigne les décisions **produit** prises par le PO, et isole explicitement ce qui reste ouvert.
>
> **Statut** : 🔮 CIBLE — v0.3 (2026-09-25). Zéro code, zéro table, zéro route. Rien de ce document n'est livré.
>
> **Historique** : v0.1 (2026-08-30) — cadrage initial, I-1 → I-17. v0.2 (2026-09-01) — §14 (présence à l'antenne, empreinte audio, classement au temps de réaction), I-18 → I-25. v0.3 (2026-09-25) — deux formats de jeu, §7 tranché, menace LLM, production des questions, architecture et surfaces, I-26 → I-43.
>
> **Voie** : le PO a décidé le 2026-09-25 de **ne pas attribuer de numéro de voie** à Interflo, et de laisser Claude Code gérer son propre découpage en local (I-42). ⚠️ Conséquence à assumer : les pratiques d'anti-collision multi-voie appliquées à BOS ne s'appliquent pas ici.
>
> **⚠️ Réserve de fraîcheur** : ce document n'est adossé à **aucune lecture du dépôt**. Il ne cite ni fichier, ni table, ni route. Toute affirmation portant sur BOS ou sur un autre produit de la suite est marquée « à instruire au source ». Règle « citer = rouvrir ».
>
> **⚠️ Réserve de compétence** : les §10 (cadre légal) et §11 (échelle) touchent des domaines où ce document ne fait qu'**identifier les questions**. Il n'y apporte aucune réponse, et ne doit pas être lu comme si c'était le cas.

---

## 1. Ce qu'est Interflo (en une phrase)

Interflo est l'**application de participation en direct** aux jeux d'une émission — utilisée simultanément par le **public présent en studio** et par les **téléspectateurs à domicile**, chacun sur son propre tour.

---

## 2. Le point de départ, et les deux déplacements

### 2.1 Premier déplacement — du buzz à la réponse (2026-08-30)

Le concept initial était un buzzer : un bouton, on appuie, le premier gagne. Deux objections l'ont fait bouger.

**Objection 1 — le premier qui buzze n'a rien prouvé.** Un buzz seul ne départage pas sur la connaissance mais sur la latence réseau. La fibre bat la 3G, systématiquement, et le téléspectateur à domicile — qui reçoit l'image avec plusieurs secondes de décalage — ne gagnerait **jamais**. Le PO a lui-même tranché : « s'il répond à quelque chose, c'est bien plus logique ».

**Objection 2 — la sécurité disparaît avec le problème.** Tant que le buzz seul décide, un script sur un téléphone authentifié gagne toujours : il buzze en trente millisecondes, ce qu'aucun humain ne fait. Dès lors que c'est la **réponse** qui départage, le buzz ne vaut plus rien à voler.

⚠️ L'objection 2 a été **partiellement invalidée depuis** : un modèle de langage, lui, peut répondre juste. Voir §8.

### 2.2 Second déplacement — du classement à l'élimination (2026-09-25)

Le PO a proposé un format alternatif : plutôt que de classer les joueurs au temps, on **élimine** ceux qui se trompent, manche après manche, en complexifiant les questions jusqu'à ce qu'il ne reste qu'un petit groupe.

Ce n'est pas une variante du buzzer, c'est un autre jeu, avec d'autres propriétés :

- Il ne dépend **d'aucune mesure de décalage** : la fenêtre doit simplement être plus longue que le pire décalage, et le problème est clos. Ni synchronisation d'horloge, ni empreinte audio ne sont nécessaires.
- Il est **beaucoup plus léger à l'échelle** : compter des bonnes réponses au lieu d'ordonner des dizaines de milliers d'horodatages à la milliseconde, sur une population qui fond à chaque manche.
- Il n'a **pas de problème de §7** : chaque joueur sait immédiatement s'il continue ou s'il sort.
- Il **résiste mieux à l'assistance par modèle de langage** (§8).

Ses faiblesses lui sont propres : la calibration de difficulté en direct, le hasard qui survit longtemps, et la convergence vers un groupe plutôt que vers une personne (§4.3).

**Décision** : le produit porte les deux formats (I-26). Ce ne sont pas deux versions du même jeu, mais deux réponses à deux besoins d'émission différents — le buzzer produit un gagnant unique, vite, avec du suspense ; l'élimination produit un groupe de finalistes, sur la durée.

> ⚠️ Le PO a d'abord envisagé d'abandonner le format élimination au motif qu'il « existe déjà ailleurs ». Cet argument a été écarté en séance : un format éprouvé est une bonne nouvelle, pas un défaut, et l'information venait d'un souvenir non vérifié. Si le format doit être abandonné un jour, que ce soit pour les trois objections de §4.3, pas pour son antériorité.

---

## 3. Décisions PO scellées

> I-1 → I-17 : 2026-08-30. I-18 → I-25 : 2026-09-01. I-26 → I-43 : 2026-09-25.

### 3.1 Mécanique de jeu

| # | Décision |
|---|---|
| **I-1** | **Deux populations séparées** : public en studio, téléspectateurs à domicile. Elles ne concourent **jamais** l'une contre l'autre. Chacune a ses propres tours. |
| **I-2** | **L'animateur ouvre et ferme les fenêtres de participation.** Hors fenêtre, **le serveur refuse** — ce n'est pas un bouton grisé côté client, c'est un refus serveur. |
| **I-3** | L'ouverture d'une fenêtre est signifiée **par l'application**, jamais par ce que le téléspectateur voit à l'écran (décalage de diffusion). |
| **I-4** | La réponse est un **choix multiple** (4 propositions). **Le vocal est écarté** (justification §6). |
| **I-5** | **Chaque proposition est elle-même un buzzer.** Un seul geste vaut buzz *et* réponse. *(Idée du PO — c'est ce qui unifie les deux publics.)* |
| **I-6** | **Horodatage au geste sur l'appareil**, pas à l'arrivée serveur — pour ne pas pénaliser les mauvaises connexions. Avec synchronisation sur l'horloge serveur à l'ouverture du tour, et rejet serveur de tout temps physiquement impossible. |
| **I-7** | Le **premier qui répond s'affiche au studio même s'il se trompe**, avec son nom et sa réponse. L'animateur peut relancer le tour (ordre de grandeur : jusqu'à 3 fois, à sa main). *Format buzzer uniquement.* |
| **I-10** | Au studio, on n'affiche **pas** la liste des répondants : le **gagnant du tour** + un **compteur global** de participation. |
| **I-26** | Le produit porte **deux formats** : **buzzer** (gagnant unique, départage au temps de réaction) et **élimination progressive** (manches successives, gagnants multiples). **Les deux sont développés en V1**, avec **activation progressive** : on lance avec l'élimination, on active le buzzer ensuite selon les retours. |
| **I-27** | Format élimination : **5 manches**. Qui se trompe est verrouillé jusqu'à la fin du thème ; les questions se complexifient à chaque manche. Le compteur de bonnes réponses s'affiche au studio après chaque manche. |
| **I-28** | Fin de partie en élimination : **plusieurs gagnants**. Selon l'émission — tous les survivants, ou **tirage au sort** pour retenir 1, 3 ou 5 gagnants. Configurable par tenant. ⚠️ Conséquence réglementaire : voir §10.4. |
| **I-29** | **§7 tranché** — le joueur non-premier reçoit un **retour individuel immédiat, juste ou faux, sans révélation de la bonne réponse**. **Aucun resserrement** des propositions aux relances. Le non-premier **ne gagne rien** sur le format buzzer : c'est le format élimination qui donne sa chance à la masse. *(Proposition de Claude validée par le PO.)* |
| **I-30** | **Plancher anti-automatisation** : un seuil de temps de réponse minimum en dessous duquel le serveur rejette, parce qu'aucun humain ne répond aussi vite. ⚠️ À ne pas confondre avec I-31. |
| **I-31** | **Durée de la fenêtre de réponse configurable par tenant**, depuis le tableau de bord d'administration. Une chaîne qui trouve la fenêtre trop longue peut la réduire. ⚠️ À ne pas confondre avec I-30. |
| **I-32** | Les questions sont **majoritairement tirées de l'émission elle-même** — le plateau, l'invité, ce qui vient d'être dit ou montré — avec un **petit pourcentage de culture générale**. Justification §8. |
| **I-33** | **Production des questions** : transcription horodatée du direct, un modèle de langage **propose** la question, un **agent humain valide** — il clique sur le time code, retrouve le passage, remonte au son enregistré si besoin, et corrige. **Rien ne part à l'antenne sans validation humaine.** *(Idée du PO.)* |
| **I-39** | Le public en studio joue **sur téléphone et/ou sur boîtier physique** — les deux sont possibles, au choix de l'émission. |
| **I-40** | **Classement persistant entre émissions : configurable par tenant**, selon la ligne éditoriale de l'émission. |

### 3.2 Identité, triche et présence à l'antenne

| # | Décision |
|---|---|
| **I-8** | Identification par **numéro de téléphone vérifié**. **La pièce d'identité à l'inscription est écartée** (justification §10.1). |
| **I-9** | La détection de triche est **comportementale** (régularité anormale, temps sous le seuil humain), pas identitaire. |
| **I-18** | **Le code d'appairage tourne** : renouvelé périodiquement en cours d'antenne (ordre de grandeur évoqué : 15 à 30 secondes). Un code photographié ou partagé est déjà mort. |
| **I-19** | Pour établir qu'un joueur entend réellement l'émission, on retient l'**empreinte audio** et non le **marquage audio incrusté** (watermark). Justification §14.3. *(Proposition du PO.)* |
| **I-20** | Le classement du format buzzer se fait sur le **temps de réaction**, pas sur l'heure absolue : on soustrait le décalage de diffusion propre à chaque joueur. *(Proposition du PO.)* |
| **I-21** | Le décalage individuel est **déduit du signal capté**, jamais d'une valeur envoyée par l'application. |
| **I-22** | L'écoute du micro se fait sur une **fenêtre courte**, ouverte juste avant les questions pendant que l'animateur annonce le passage aux téléspectateurs — **jamais en continu**. *(Proposition du PO.)* ⚠️ Durée à revoir : voir §14.5. |
| **I-23** | **La mesure de décalage est un bonus, jamais une condition pour jouer.** Mesure échouée → le joueur joue quand même, dans un **classement séparé**. Le studio n'affiche que le classement mesuré. |
| **I-24** | Le mécanisme d'ajustement **n'est pas expliqué au grand public**, mais il est **documenté précisément pour les chaînes** — obligation contractuelle, car c'est la chaîne qui répondra le jour où un joueur conteste un résultat. |
| **I-25** | Pour la **première saison**, le mode mesuré reste une **option activable par chaîne**. |
| **I-43** | Le fournisseur d'empreinte audio retenu est **ACRCloud** (offre premium souscrite par le PO le 2026-09-25), service *Live Channel Detection*. Justification et réserves §14.3. |

### 3.3 Produit, chaînes et architecture

| # | Décision |
|---|---|
| **I-11** | Un **dispositif physique** (boîtier imprimé en 3D, conçu par client selon le type d'émission) est la **déclinaison matérielle de la même interface** : 4 boutons au lieu de 4 lignes. |
| **I-12** | Le dispositif physique doit rendre le public à domicile **visible à l'antenne** (§12). |
| **I-13** | L'application **n'est liée à aucune chaîne**. Produit générique, vendu à plusieurs chaînes qui peuvent diffuser des jeux simultanément. |
| **I-14** | **Appairage par code QR affiché à l'antenne**, doublé d'un **code court saisi à la main** (ordre de grandeur : 6 caractères, annoncé à l'oral). Le code court est systématique, pas facultatif. |
| **I-15** | Le QR et le code court sont des **pointeurs publics**, pas des secrets. Ils donnent l'adresse — **pas le droit de jouer**. |
| **I-16** | La session est **adossée à l'émission** : émission terminée, accès mort. |
| **I-17** | **Une seule session active à la fois.** |
| **I-34** | Le modèle de **sponsoring télécom est abandonné**. Sans course à la vitesse (format élimination) et avec le classement au temps de réaction (I-20), il n'y a plus de latence à vendre. La contradiction relevée en v0.2 se referme d'elle-même. |
| **I-35** | **Interflo est un produit à part** : API séparée de celle de BOS, **point d'entrée séparé, service séparé, base séparée**. Justification §15.1. |
| **I-36** | La dépendance entre BOS et Interflo est **unidirectionnelle et anticipée** : Interflo copie ce dont il a besoin **avant** l'émission et joue ensuite sans rien demander ; BOS lit les résultats **après coup** via l'API Interflo. **Rien de synchrone pendant le direct.** *(Proposition de Claude validée par le PO.)* |
| **I-37** | **Deux chemins d'authentification distincts** : l'opérateur du tenant qui configure le jeu peut passer par l'auth BOS ; le **téléspectateur, jamais**. |
| **I-38** | **Multi-tenant** : plusieurs chaînes sur la même infrastructure, avec cloisonnement strict de leurs joueurs et de leurs données. |
| **I-41** | **Stack** : application mobile en **React Native**, API en **Laravel** (dernière version), tableau de bord d'administration en **Filament**, **surface tablette dédiée** pour l'animateur (§15.3). |
| **I-42** | **Aucun numéro de voie n'est attribué.** Claude Code gère son propre découpage et ses propres agents en local, et rend compte au PO. |

---

## 4. Les deux formats de jeu

### 4.1 Format buzzer

Un tour, quatre propositions, le premier qui répond juste gagne. L'animateur relance si la réponse est fausse (I-7). Départage au **temps de réaction** (I-20), ce qui suppose la mesure du décalage individuel (§14).

Produit **un gagnant unique**, vite, avec du suspense à l'antenne.

**Dépendances** : empreinte audio, synchronisation d'horloge, mesure de décalage. C'est le format le plus coûteux en infrastructure, et celui qui dépend d'inconnues encore non mesurées (§14.7).

### 4.2 Format élimination

Une question, une fenêtre de quelques secondes, tout le monde répond. Le système collecte les bonnes réponses, affiche au studio **le nombre de joueurs qui ont trouvé**, et **verrouille ceux qui se sont trompés** jusqu'à la fin du thème. Question suivante, plus difficile, réservée aux survivants. Cinq manches (I-27).

Produit **un groupe de finalistes**, sur la durée, avec un compteur qui descend à l'écran.

**Ne dépend d'aucune mesure de décalage.** La fenêtre doit simplement être plus longue que le pire décalage attendu.

> ⚠️ **La fenêtre est personnelle, pas absolue.** Dix secondes à partir du moment où *chaque joueur* voit la question — ce qui donne une enveloppe serveur d'environ vingt-cinq secondes si l'on table sur quinze secondes de décalage maximum. **À écrire précisément**, sinon une implémentation naïve ouvrira une fenêtre unique et coupera les joueurs les plus décalés.

### 4.3 ⚠️ Les trois objections au format élimination

Elles ne sont **pas résolues**. Elles se traitent par l'écriture et le règlement, pas par l'architecture — mais elles se traitent.

**Le hasard survit longtemps.** Avec quatre propositions, répondre au hasard donne une chance sur quatre de passer. Sur cent mille joueurs répondant au hasard, il en reste environ vingt-cinq mille après une manche, six mille après deux, quinze cents après trois, quatre cents après quatre. Deux conséquences : les finalistes ne sont pas nécessairement ceux qui savaient, et quiconque pilote des milliers de comptes automatisés se retrouve statistiquement en finale sans rien connaître.

**La calibration est un point de défaillance éditorial.** La convergence repose entièrement sur la difficulté des questions. Trop facile, personne ne sort ; trop dure, tout le monde sort d'un coup. Il faut calibrer en direct, sans rattrapage, pour une population dont on ne connaît pas le niveau. Ce n'est pas un problème technique — c'est une compétence d'écriture, et elle devient critique dès lors que I-32 exige une majorité de questions tirées du plateau, qui ne peuvent pas être préparées longtemps à l'avance.

**La convergence donne un groupe, pas une personne.** D'où I-28 : plusieurs gagnants, ou tirage au sort. Un départage final au temps réintroduirait tout ce que le format évite — mais sur quelques dizaines de personnes seulement, ce qui serait tenable.

### 4.4 Contrainte d'exploitation commune

Dans les deux formats, l'animateur doit **meubler l'enveloppe complète** après chaque question — l'ordre de grandeur est de vingt à vingt-cinq secondes, plusieurs fois par thème. Ce n'est pas du confort d'animateur, c'est une **contrainte technique de conduite d'émission**, à écrire noir sur blanc dans ce qui est remis aux chaînes.

---

## 5. Déroulé d'un tour

1. L'animateur annonce le jeu et désigne la population concernée (studio **ou** domicile).
2. Il **ouvre la fenêtre**. L'application des joueurs concernés s'active ; les autres restent inertes.
3. Les 4 propositions s'affichent. Chaque joueur touche celle qu'il croit juste — **un seul geste**.
4. Le geste est horodaté sur l'appareil, envoyé, contrôlé et classé côté serveur.
5. Chaque joueur reçoit son **retour individuel** : juste ou faux, sans la bonne réponse (I-29).
6. Le résultat remonte au studio — le gagnant en format buzzer, le compteur de survivants en format élimination.

**Sur le décalage de diffusion** : l'animateur continue son show pendant que la fenêtre court, et le résultat tombe à l'écran quand il tombe. Le décalage devient du temps d'antenne, pas un défaut à corriger.

> ⚠️ À ne pas confondre avec I-20. Le décalage reste du temps d'antenne **pour le spectacle** ; il est en revanche **neutralisé dans le calcul du classement** du format buzzer (§14.4). Les deux énoncés portent sur deux choses différentes.

---

## 6. Pourquoi le vocal est écarté (I-4)

Trois raisons cumulatives :

1. **Fiabilité de transcription** — français ivoirien, accents, noms propres locaux, bruit de fond domestique. Le risque n'est pas l'inconfort : c'est de **refuser de bonnes réponses**, ce qui est mortel pour la confiance dans le jeu.
2. **Volume** — envoyer et traiter des dizaines de milliers de fichiers audio en quelques secondes.
3. **Départage impossible** — on ne classe pas à la milliseconde des gens qui parlent deux secondes chacun.

Le choix multiple règle les trois d'un coup, et **supprime le débat sur ce qui compte comme bonne réponse**.

> ⚠️ **Ne pas confondre avec I-33.** La transcription automatique est écartée pour les *réponses des joueurs*, où une erreur est fatale et sans filet. Elle est retenue pour la *production des questions*, où un humain valide avant diffusion (§9). La différence n'est pas la technologie, c'est la présence d'un filet.

---

## 7. Le retour au joueur non-premier — TRANCHÉ (I-29)

> Cette question a été le verrou du cadrage pendant plusieurs semaines. Elle est tranchée depuis le 2026-09-25.

**Le problème.** Si seul le premier existe, sur cent mille joueurs, quatre-vingt-dix-neuf mille neuf cent quatre-vingt-dix-neuf ont touché leur écran pour rien — sans même savoir s'ils avaient juste. Manche après manche, ils décrochent, et le produit meurt de désintérêt plutôt que de panne.

**La décision.** Retour individuel immédiat — **juste ou faux** — sans révéler la bonne réponse. **Aucun resserrement** des propositions aux relances.

**Pourquoi.** Ne pas révéler la bonne réponse préserve la difficulté, qui était la réserve explicite du PO contre le resserrement. Et c'est peu coûteux à diffuser : un seul bit par joueur, pas de calcul de pourcentages à renvoyer à cent mille personnes.

**L'autre moitié de la question — le non-premier peut-il gagner quelque chose ?** Non, sur le format buzzer. Ce format produit un gagnant unique, c'est sa nature et son intérêt. La masse a sa chance dans le format élimination, avec ses cinq manches et ses gagnants multiples (I-27, I-28). **Les deux formats se répartissent les rôles au lieu de se copier** — il n'y a pas besoin d'inventer un troisième mécanisme.

> Cette décision débloque le dimensionnement du temps réel (§11) : on sait désormais ce que le serveur doit calculer et renvoyer à chaque joueur.

---

## 8. ⚠️ La menace « modèle de langage » — non résoluble

**Le scénario, identifié par le PO** : un joueur avec deux téléphones. Sur l'un, l'application de jeu. Sur l'autre, un modèle de langage. Il filme l'écran ou la question à l'antenne, demande la bonne réponse, et reporte.

**Ce n'est pas détectable.** L'application ne voit qu'un doigt sur une proposition. Il n'existe aucun signal observable de l'extérieur. Toute tentative de blindage technique est une perte de temps.

### 8.1 La portée diffère radicalement selon le format

**Sur le buzzer**, c'est grave : un modèle qui répond en trois secondes bat systématiquement un humain honnête qui en met cinq, et le gagnant unique lui revient.

⚠️ **Ceci invalide partiellement l'objection 2 de §2.1.** On disait qu'un script ne peut pas répondre juste. Un modèle, si. Le buzz redevient volable dès lors que l'assistance est possible.

**Sur l'élimination**, c'est mineur : il n'y a pas de course, tout le monde a la fenêtre entière, et l'humain qui sait répond dans les temps sans aide. Le modèle ne fait passer que celui qui ne savait pas — il **élargit le groupe de finalistes** au lieu de voler la victoire à quelqu'un.

> Le PO a formulé la défense ainsi : « s'il continue, il sera forcément bloqué par une question du plateau ». ⚠️ **C'est vrai en élimination seulement**, où survivre est cumulatif. Dans le buzzer, chaque tour est indépendant : le tricheur perd les tours plateau et gagne les tours de culture générale, sans jamais être éliminé de quoi que ce soit. Cette défense est une propriété du format élimination, pas du produit.

### 8.2 Ce qui atténue sans jamais supprimer

- **Plancher anti-automatisation** (I-30). Ne bloque pas le modèle — il lui suffit d'attendre — mais élimine l'avantage de vitesse pure. Peu coûteux, retenu.
- **Fenêtre courte** (I-31). Moins de temps pour filmer, transcrire, interroger, lire et reporter. Ne ferme rien, resserre.
- **Le type de questions** (I-32), qui est le vrai levier — voir ci-dessous.

### 8.3 ⚠️ Ce qui protège vraiment, et ce qui n'y suffit pas

Une question de culture générale, un modèle la traite. **Une question sur ce qui vient d'être dit ou montré à l'antenne, non** — l'information n'existe nulle part ailleurs que dans l'émission.

⚠️ **Attention à une erreur naturelle** : l'actualité récente et la politique locale **ne protègent pas**. Un modèle avec accès web y répond très bien. Seule la matière propre à l'émission est réellement hors de portée — et c'est aussi la plus coûteuse à écrire, puisqu'elle ne peut pas être préparée longtemps à l'avance (§9).

### 8.4 Le principe général

**Ce qui n'est pas défendable par la technique est traité par le règlement du jeu.** C'est déjà le principe appliqué au code d'appairage public (§13.3) et à la pièce d'identité (§10.1). Un jeu à lots symboliques n'a pas ce problème ; un jeu à forte dotation le traite à la remise du prix, en présence, avec une épreuve que le gagnant passe sans son téléphone.

---

## 9. La production des questions (I-33)

### 9.1 Le dispositif

Le direct est transcrit, **avec horodatage**. Un modèle de langage propose des questions à partir de la transcription. Un **agent humain valide** : il clique sur le time code, retrouve le passage exact, remonte au son enregistré s'il ne se souvient plus, et corrige la question si elle ne correspond pas à ce qui a été dit.

**L'humain est au centre ; le modèle est un assistant.** Rien ne part à l'antenne sans validation humaine.

### 9.2 Pourquoi cela ne contredit pas I-4

La transcription automatique a été écartée pour les réponses des joueurs (§6) précisément à cause du français ivoirien, des accents et des noms propres locaux. Ici, la même faiblesse existe — mais **un humain valide avant diffusion**. Pour les réponses joueurs, il n'y avait pas de filet.

> ⚠️ Une question fausse en direct est pire qu'une transcription ratée. L'ordre — le modèle propose, l'humain valide — n'est pas une précaution de confort : c'est la condition qui rend I-33 acceptable.

### 9.3 ⚠️ Conséquence d'exploitation

Cet agent n'est pas un accessoire : c'est un **poste de production supplémentaire**, à la charge de la chaîne, mobilisé pendant toute la durée de l'émission. Le PO indique qu'il peut s'agir d'un recrutement ou d'une migration de poste en interne. À écrire dans ce qui est remis aux chaînes, au même titre que la contrainte de conduite (§4.4).

---

## 10. Identité et cadre légal

### 10.1 Pourquoi la pièce d'identité est écartée (I-8)

**La pièce d'identité ne protège pas du piratage.** Celui qui automatise son téléphone a un compte parfaitement vérifié — c'est le sien. L'identité dit à qui appartient le compte ; elle ne dit rien sur **qui tient l'appareil à l'instant du geste**, ni même s'il s'agit d'un humain. Ce qui détecte la triche est le comportement (I-9), pas l'état civil.

En regard, collecter des pièces d'identité de dizaines de milliers de personnes est une charge réglementaire lourde, pour un bénéfice nul sur le problème visé.

### 10.2 Le principe : vérifier à la sortie, pas à l'entrée

Si le jeu comporte des **lots réels**, la vérification d'identité devient nécessaire — mais **seulement pour le gagnant, au moment de la remise du lot**. Pas pour les cent mille.

### 10.3 ⚠️ Questions à instruire par un tiers compétent

Ce document **n'apporte aucune réponse** sur ces points :

- Cadre légal des jeux-concours dotés en Côte d'Ivoire (règlement, dépôt, obligations envers les participants).
- Régime applicable aux données personnelles collectées (numéro de téléphone, historique de participation, **enregistrements audio captés par le micro**) et durées de conservation.
- Obligations propres si des mineurs participent.
- Répartition des responsabilités entre l'éditeur du produit et la chaîne diffusante.

### 10.4 ⚠️ Le tirage au sort change la nature du jeu

I-28 prévoit un tirage au sort pour départager les survivants. **Cela fait basculer le jeu de l'adresse vers le hasard**, et dans beaucoup de juridictions ce n'est pas le même régime réglementaire.

Ce document ne sait pas ce qu'il en est en Côte d'Ivoire et ne se prononce pas. **Ce point doit figurer explicitement dans ce qui part chez le juriste**, parce qu'il peut changer les obligations applicables.

---

## 11. ⚠️ L'échelle : ce qui n'est pas conçu

Le PO a posé lui-même la bonne question : « imaginons cent mille personnes qui buzzent en même temps ».

**Rien de ce document ne répond à cette question.** Ce qui est acquis (choix multiple, horodatage au geste, refus hors fenêtre, retour individuel à un bit) rend le problème *traitable* — mais l'architecture temps réel reste **entièrement à concevoir** :

- Pic de charge concentré sur quelques secondes, plusieurs fois par émission.
- Transport temps réel bidirectionnel vers un très grand nombre de clients.
- Classement et arbitrage à faible latence (format buzzer), comptage de bonnes réponses (format élimination) — les deux profils de charge sont différents.
- Diffusion du retour individuel à chaque joueur. **Désormais dimensionnable** grâce à I-29 : un bit par joueur, pas de calcul par joueur.
- Comportement en **mode dégradé** : que se passe-t-il si l'infrastructure sature **en plein direct** ?

> Un produit de direct sans mode dégradé n'est pas un produit de direct. C'est un ADR à part entière, et probablement le plus lourd du produit.

---

## 12. Le dispositif physique et l'angle commercial

### 12.1 Principe

Les 4 boutons du boîtier de studio et les 4 lignes du téléphone déclenchent **le même événement applicatif**. Ce qui change, c'est ce que le geste **actionne** sur le plateau. Le boîtier est conçu par client, selon le type d'émission (électronique embarquée à définir — une carte de type Arduino a été évoquée, sans arbitrage).

### 12.2 Exemples de dispositifs évoqués

- Quatre portes qui s'ouvrent, une seule contient le lot.
- Une roue qui s'arrête sur le choix.
- **Quatre colonnes lumineuses qui montent selon les votes du public à domicile.**

### 12.3 ⚠️ Pourquoi le troisième exemple n'est pas comme les autres

Les deux premiers fonctionnent **sans aucun téléspectateur**. Le troisième, non : il montre à l'antenne, en direct, que des dizaines de milliers de personnes jouent.

**C'est l'argument commercial du produit.** Si le dispositif physique ne rend pas le public à domicile visible sur le plateau, ce public reste invisible — et personne n'a de raison de télécharger l'application.

> En format élimination, le compteur de survivants qui descend manche après manche (I-27) sert directement cet argument : c'est le résultat du tour **et** le spectacle.

---

## 13. Chaînes, appairage et cycle de vie de la session

### 13.1 Le problème posé

L'application est vendue comme produit générique, non liée à une chaîne (I-13). Plusieurs chaînes peuvent lancer des jeux **en même temps**, et un même téléspectateur peut en suivre plusieurs. Il faut donc un mécanisme qui dise à l'application : *voici la chaîne, voici l'émission en cours*.

### 13.2 L'appairage (I-14)

Un **code QR est affiché à l'antenne**. Le téléspectateur le scanne depuis l'application.

**Un code court saisi à la main double systématiquement le QR.** Scanner un code sur un téléviseur depuis un canapé est souvent pénible — distance, reflets, taille du code à l'écran, qualité de la caméra. Sans alternative manuelle, on perd des joueurs sur un problème purement optique. Le code est annoncé à l'oral par l'animateur.

### 13.3 ⚠️ Le code est public — et doit être conçu comme tel (I-15)

Un code diffusé à l'antenne **ne peut pas être un secret** : vu par tout le monde, photographiable, partageable en quelques secondes, et il survit dans les rediffusions.

Il doit donc être un **simple pointeur** : il désigne la chaîne et l'émission, rien de plus. Il **n'autorise pas à jouer**. L'autorisation reste entièrement côté serveur, sur la fenêtre ouverte par l'animateur (I-2).

**Conséquence à assumer** : une personne ayant récupéré le code **sans regarder l'émission** peut participer.

- Gains **symboliques** → ce n'est pas un défaut, c'est de l'acquisition d'audience.
- **Lots réels** → c'est une faille de règlement.

> Cette faille se traite d'abord par la **dotation et le règlement du jeu**. **Aucun dispositif technique n'est étanche** : il n'existe pas de moyen de prouver qu'une personne regarde effectivement l'émission.
>
> ⚠️ La §14 décrit des leviers qui **augmentent le coût de la triche sans l'annuler**. L'énoncé ci-dessus reste vrai — ne pas lire la §14 comme une fermeture de la faille.

### 13.4 Cycle de vie (I-16, I-17)

La session vit et meurt avec l'émission. Le joueur peut avoir ajouté plusieurs chaînes, mais **une seule session est active à la fois** (I-17). Ce n'est pas un choix d'affichage : cela détermine le modèle de session côté serveur.

---

## 14. Présence à l'antenne et équité de mesure

> ⚠️ Toute cette section ne concerne que le **format buzzer**. Le format élimination n'en a besoin d'aucune partie.

### 14.1 Le point de départ

La §13.3 acte que le code d'appairage est public, et qu'on peut donc jouer sans regarder l'émission. Le PO a fait savoir que cela le dérangeait, et a demandé une réponse **additive** — qui s'ajoute au règlement du jeu, sans prétendre le remplacer.

**Aucun des leviers ci-dessous n'est étanche.**

### 14.2 Les leviers, par coût croissant

| Levier | Ce qu'il fait | Contrepartie |
|---|---|---|
| **Code tournant** (I-18) | Le code renouvelé en cours d'antenne rend mort tout code photographié ou partagé. Ne bloque pas un relais en temps réel, mais transforme un partage unique en travail permanent. | Quasi nulle. **Retenu dans tous les cas.** |
| **Question dépendant de l'image** | La réponse exige d'avoir vu un élément affiché quelques secondes plus tôt. | Zéro infrastructure, contrainte d'écriture — et reste relayable. Rejoint I-32. |
| **Marquage audio** (watermark) | Signal incrusté dans la bande son, détecté par le micro. Porte un horodatage. | **Écarté** — voir 14.3. |
| **Empreinte audio** (I-19, I-43) | L'application écoute le son ambiant, en calcule une empreinte, et la compare au flux réel de la chaîne. | Retenu — voir 14.3. |

### 14.3 Pourquoi l'empreinte audio plutôt que le marquage (I-19)

La différence est **commerciale avant d'être technique**.

Le marquage audio suppose d'incruster un signal dans la diffusion, donc de **modifier la chaîne technique du diffuseur**. Interflo étant vendu à plusieurs chaînes (I-13), il faudrait négocier cette modification avec chacune — un frein d'adoption majeur.

> ⚠️ **Correction apportée le 2026-09-25.** La v0.2 affirmait que l'empreinte audio « ne demande rien au diffuseur ». **C'est trop fort.** Il faut **ingérer le flux de la chaîne en temps réel** : un outil d'empreinte de chaîne live doit tourner sur un serveur pour alimenter le dépôt d'empreintes, et c'est seulement ensuite que le SDK mobile peut identifier la chaîne. Il faut donc **l'accès au flux de chaque chaîne cliente** (URL de diffusion).
>
> C'est infiniment plus léger que modifier leur chaîne technique — aucun signal à incruster — mais **ce n'est pas rien, et c'est à négocier chaîne par chaîne**.

### 14.4 Le classement au temps de réaction (I-20, I-21)

Classer à l'heure absolue revient à classer sur la qualité du transport : celui dont le téléviseur affiche l'image cinq secondes avant celui du voisin gagne toujours, quelle que soit sa vitesse de réflexion.

**Le principe retenu** : on ramène tout le monde au même point de départ en soustrayant son décalage propre, puis on compare le temps écoulé depuis ce point.

**Exemple donné par le PO** : deux joueurs, l'un à dix secondes de décalage, l'autre à cinq. On ramène le premier sur le second en retirant cinq secondes. Ensuite, celui qui met trois secondes à répondre bat celui qui en met cinq. **Le décalage est neutralisé ; le temps de réponse n'est pas touché.**

**Garde-fou** (I-21) : la valeur du décalage doit provenir du signal capté. Si l'application peut déclarer son propre décalage, elle peut se fabriquer une avance.

> ✅ **Réserve levée le 2026-09-25.** La v0.2 posait que « l'empreinte prouve qu'on entend, pas quand », et que mesurer le décalage serait du travail. **C'est faux.** Le service de détection de chaîne live d'ACRCloud renvoie un champ `timestamps_ms` — l'horodatage Unix UTC en millisecondes du flux live correspondant au son reconnu. Le décalage individuel est une soustraction, il n'y a rien à reconstruire. Un champ `result_type` distingue par ailleurs `live` de `timeshift`, ce qui identifie déjà les joueurs en différé.

### 14.5 La fenêtre d'écoute (I-22)

Le décalage **n'est pas figé** pendant l'émission : la mémoire tampon du décodeur se remplit, le réseau varie, le flux se resynchronise. Une mesure prise une fois au début serait périmée au moment de la réponse.

La réponse retenue : ouvrir le micro sur une **fenêtre courte, juste avant les questions**, pendant que l'animateur annonce le passage aux téléspectateurs. La mesure est alors fraîche.

Cela résout trois choses : la fraîcheur de la mesure, la consommation de batterie (déterminante sur les téléphones d'entrée de gamme, probablement une part importante du public visé), et l'acceptabilité de la permission micro — une application qui écoute quelques secondes au moment du jeu s'explique ; une application qui écoute toute la soirée se fait désinstaller.

> ⚠️ **I-22 est probablement sous-dimensionné.** Une source tierce évoque un extrait de **dix à quinze secondes** pour la reconnaissance. Si c'est le même ordre de grandeur pour la détection de chaîne live, « quelques secondes » ne suffit pas — et le temps de préparation obligatoire dans la conduite d'émission (§4.4) s'allonge d'autant. **À mesurer en test réel** (§16, question 3).

### 14.6 La mesure est un bonus, jamais une condition (I-23)

- Mesure réussie → classement au temps de réaction. Mode nominal.
- Mesure échouée → le joueur **joue quand même**, dans un classement séparé.
- Le studio n'affiche que le **classement mesuré**.

**Pourquoi** : exclure les non-mesurés ferait perdre une grande partie du public dès le premier soir ; les mélanger détruirait la promesse d'équité.

> ⚠️ Il faut une règle explicite pour les non-mesurés. **Elle sera contestable quelle qu'elle soit** — elle doit figurer au règlement du jeu, pas seulement dans le code.

### 14.7 Réserves à ne pas perdre

- **La précision de la mesure devient la précision du classement.** Un décalage mesuré à la demi-seconde interdit de départager deux joueurs séparés par cent millisecondes. Acceptable **si c'est assumé** — on classe alors par tranches. Ne jamais prétendre à la milliseconde ce qui est mesuré à la demi-seconde.
- **La durée de fenêtre nécessaire à une reconnaissance fiable dans le bruit d'un salon n'est pas connue**, et aucune mesure publique n'en existe. À établir par test.
- **Le fournisseur a été choisi avant le test terrain.** L'offre ACRCloud premium est souscrite (I-43) ; la question 3 reste entière, et la consommation réelle devra être surveillée par rapport aux quotas de l'offre.
- **Alternatives auto-hébergées** (Olaf, Panako, audfprint, dejavu, Chromaprint) : elles existent, mais aucune ne fournit de SDK mobile prêt à l'emploi, et **Panako signale que des brevets limitent l'usage de certains algorithmes selon les conditions et les régions**. Point juridique à ne pas ignorer si cette voie était rouverte.

---

## 15. Architecture et surfaces

> ⚠️ Aucune de ces décisions n'est adossée à une lecture du dépôt. La structure réelle de l'API BOS est **à instruire au source**.

### 15.1 Pourquoi une séparation complète (I-35)

Quatre raisons, la première décisive :

1. **Isolation de panne.** Si le jeu et la régie partagent le même point d'entrée, un pic Interflo peut faire tomber BOS **en plein direct** — or pendant l'émission BOS n'est pas au repos : c'est la conduite, c'est Voxflo qui pousse les synthés. Le jour où le jeu marche trop bien, c'est l'antenne qui tombe.
2. **Surface d'attaque.** Employés de chaînes d'un côté, grand public de l'autre. Exposer l'API BOS au grand public l'élargit énormément, pour rien.
3. **Nature technique.** BOS est du requête-réponse classique ; Interflo est du temps réel bidirectionnel avec classement à faible latence. Ce n'est pas le même type de serveur.
4. **Tenancy.** BOS est cloisonné par chaîne ; Interflo est générique et non lié à une chaîne (I-13) tout en restant multi-tenant (I-38).

> ⚠️ **Séparer l'API sans séparer la base ne protège de rien.** Si les deux points d'entrée tapent la même base, le pic Interflo la sature et BOS tombe quand même. Les trois — point d'entrée, service, base — vont ensemble ou l'exercice est décoratif.

### 15.2 Le couplage, et comment il est neutralisé (I-36)

Le PO a demandé que BOS puisse consommer les données d'Interflo et qu'Interflo consomme l'authentification du tenant. **Pris naïvement, cela annule le bénéfice de la séparation** : si Interflo interroge BOS pendant le jeu, un BOS lent ou indisponible tue le direct.

**La forme retenue** :

- **Avant l'émission** : Interflo copie ce dont il a besoin — chaîne, émission, configuration du tenant.
- **Pendant l'émission** : Interflo joue **sans rien demander à personne**. Rien de synchrone.
- **Après l'émission** : BOS lit les résultats via l'API Interflo. Sans danger.

**Sur l'authentification** (I-37), deux populations distinctes :

- L'**opérateur du tenant** qui configure le jeu → peut passer par l'auth BOS. C'est la seule qui peut se permettre une dépendance.
- Le **téléspectateur** → numéro de téléphone vérifié (I-8), jamais l'API BOS.

### 15.3 Les surfaces

| Surface | Technologie | Pour qui |
|---|---|---|
| **Application joueur** | React Native | Public studio et téléspectateurs |
| **API** | Laravel (dernière version) | — |
| **Tableau de bord d'administration** | Filament | Opérateurs tenant (configuration, banque de questions, seuils, résultats) **et** administration éditeur (réglage par tenant, ex. durée de fenêtre — I-31) |
| **Console animateur** | Surface dédiée, **tablette** | Animateur, en direct |

> ⚠️ **Filament n'est pas pour tout.** Il convient à l'administration. Il n'est **pas** fait pour la console de l'animateur, qui ouvre et ferme les fenêtres à la seconde en plein direct. Même raisonnement que pour Voxflo : écran plein, gros boutons, chemin d'accès court en cas de panne. **Deux surfaces, pas une.**

> ⚠️ **En attente d'arbitrage** : la bibliothèque de stylisation React Native. Recommandation de Claude : **NativeWind**, qui porte la grammaire Tailwind sur React Native et garde une syntaxe commune avec le web. **Non validé par le PO à ce jour.**

### 15.4 ⚠️ Question ouverte : monorepo ou dépôt séparé ?

Le PO a posé la question sans la trancher. Les autres produits de la suite sont dans le monorepo, et Voxflo y ira.

**Ce qu'il faut distinguer** : monorepo ≠ même déploiement. La séparation décidée en I-35 est une séparation **d'exécution** (point d'entrée, service, base). Placer Interflo dans le monorepo ne recrée **aucun** couplage d'exécution.

**Recommandation de Claude** : dans le monorepo, avec une **règle explicite d'interdiction d'import direct** entre Interflo et BOS — tout passe par l'API, conformément à I-36. C'est ce risque-là qui est réel : la proximité dans le dépôt invite à importer du code BOS au lieu de passer par l'API, et c'est ainsi que la séparation se dissout sans que personne ne l'ait décidé.

La décision est par ailleurs **peu coûteuse à inverser** dans les deux sens.

---

## 16. Questions ouvertes — à instruire avant tout ADR

### Ce qui ne peut pas être tranché en conversation

1. **Recouvrement avec Voxflo.** Voxflo enrôle déjà le public en studio (identité + place), et Interflo fait jouer ce même public. Réemploi ou duplication ? **Demande de lire le dépôt. Rien n'a été vérifié.**
2. **Cadre légal** (§10.3, §10.4). Tiers compétent requis.
3. **Durée de fenêtre d'écoute et précision atteignable** (§14.5, §14.7). Demande un test terrain.

### Positionnement et structure

4. **Monorepo ou dépôt séparé** (§15.4). Recommandation formulée, non tranchée.
5. **Surfaces** : application mobile seule, ou également web ? Quel repli si l'application ne s'installe pas ?
6. **Bibliothèque de stylisation React Native** (§15.3).

### Mécanique de jeu

7. Nombre de relances en format buzzer : fixe, ou à la main de l'animateur ?
8. **Calibration de difficulté** (§4.3) : qui écrit les questions, à quel rythme, et comment rattrape-t-on une manche qui élimine tout le monde ou personne ?
9. Répartition exacte questions-plateau / culture générale (I-32) — le PO a évoqué « majoritairement plateau », sans chiffre arrêté.
10. Valeur du plancher anti-automatisation (I-30) et de la fenêtre par défaut (I-31). **Toute valeur proposée sera un point de départ à corriger au premier tournage**, pas une valeur validée.

### Technique

11. Architecture temps réel et tenue de charge (§11), pour **les deux profils** de format.
12. Protocole entre le serveur et le dispositif physique de plateau.
13. Consommation de données côté téléspectateur — un jeu qui coûte cher en data exclut une partie du public visé.
14. Synchronisation d'horloge (I-6) : méthode, tolérance, seuils de rejet.
15. **Génération et rotation des codes** (I-18) : période, mécanisme, et comportement d'un joueur appairé quand le code tourne — perd-il sa session ? Longueur et alphabet du code court, lisible à l'oral sans caractères ambigus.
16. **Permission micro** : formulation exacte, moment de la demande, comportement en cas de refus.
17. **Fin d'émission** : qui déclenche la clôture — l'animateur, une durée programmée, ou les deux ? Que voit le joueur quand la session meurt ?
18. **Règle applicable aux joueurs non mesurés** (§14.6). Doit figurer au règlement du jeu.
19. **Ingestion des flux chaînes** (§14.3) : qui fournit l'URL de diffusion, sous quelle forme, avec quel engagement de disponibilité ?

---

## 17. Ce qui ne doit pas être re-fabriqué

### Sur la mécanique

- ⚠️ Le buzz seul **ne départage plus rien** (§2.1). Ne pas réintroduire un buzzer qui verrouille le tour sans réponse.
- ⚠️ « Chaque proposition est un buzzer » est une **décision du PO** (I-5), pas une proposition subie.
- ⚠️ Le vocal est écarté pour **trois** raisons cumulatives, dont la principale est le risque de refuser de bonnes réponses (§6). **Ne pas confondre avec I-33**, où un humain valide.
- ⚠️ **§7 est tranché** (I-29), plus ouvert. Retour juste/faux sans la bonne réponse, aucun resserrement.
- ⚠️ La **fenêtre de réponse est personnelle, pas absolue** (§4.2). Ne jamais implémenter une fenêtre unique qui couperait les joueurs les plus décalés.
- ⚠️ I-30 (plancher anti-automatisation) et I-31 (durée de fenêtre) sont **deux choses différentes**. Ne pas n'en implémenter qu'une.

### Sur les formats

- ⚠️ Le format élimination n'a été **ni abandonné ni relégué** : il est le premier activé (I-26).
- ⚠️ Le format élimination n'est pas écarté au motif qu'il « existe déjà » (§2.2). Ses trois vraies objections sont en §4.3.
- ⚠️ La défense « il sera bloqué par une question du plateau » vaut **en élimination seulement** (§8.1). Ne pas l'invoquer pour le buzzer.
- ⚠️ **L'objection 2 de §2.1 est partiellement invalidée** : un modèle de langage peut répondre juste (§8). Ne pas la citer comme si elle tenait encore intégralement.

### Sur la triche et la présence

- ⚠️ La pièce d'identité est écartée **parce qu'elle ne résout pas le piratage** (§10.1), pas parce qu'elle serait trop lourde.
- ⚠️ La §14 **ne ferme pas** la faille de §13.3. Ne jamais présenter l'empreinte audio comme une preuve qu'un joueur regarde l'émission.
- ⚠️ Le QR et le code court sont **publics par construction**. Ne pas tenter de les « sécuriser ».
- ⚠️ Le code court n'est **pas un secours facultatif** du QR (I-14).
- ⚠️ Le marquage audio est écarté **parce qu'il exige de modifier la chaîne de diffusion de chaque client** (§14.3), pas parce qu'il fonctionnerait mal — il fonctionnerait mieux sur l'horodatage.
- ⚠️ L'empreinte audio **exige l'accès au flux de chaque chaîne** (§14.3). Ne pas répéter qu'elle « ne demande rien au diffuseur » : c'était une erreur de la v0.2.
- ⚠️ Le décalage **n'est pas figé** pendant une émission (§14.5). Jamais de mesure unique en début d'émission.
- ⚠️ Le décalage doit venir du **signal capté**, jamais d'une valeur transmise par l'application (I-21).
- ⚠️ La mesure est un **bonus, pas une condition** (I-23). Jamais de parcours où le refus du micro empêche de jouer.
- ⚠️ L'actualité et la politique locale **ne protègent pas** d'un modèle connecté (§8.3). Seule la matière propre à l'émission est hors de portée.

### Sur l'architecture

- ⚠️ **Point d'entrée, service et base séparés vont ensemble** (§15.1). Séparer l'API seule est décoratif.
- ⚠️ La dépendance BOS↔Interflo est **unidirectionnelle et anticipée** (I-36). **Aucun appel synchrone pendant le direct.**
- ⚠️ Le téléspectateur ne touche **jamais** l'API BOS (I-37).
- ⚠️ **Filament n'est pas la console de l'animateur** (§15.3). Deux surfaces.

### Sur le statut de ce document

- ⚠️ Ce document ne traite ni le cadre légal (§10.3) ni l'architecture d'échelle (§11). Il les **désigne**.
- ⚠️ **Aucune performance d'empreinte audio n'a été mesurée** (§14.7). Ne citer aucun chiffre de fiabilité sur la foi de ce document.
- ⚠️ **Origine des décisions** : I-23, I-25, I-29 et I-36 sont des **propositions de Claude validées par le PO** ; I-5, I-19, I-20, I-22 et I-33 viennent du PO. Distinction conservée pour que le PO sache ce qu'il peut rouvrir sans coût.
- ⚠️ Ce document ne consomme **ni numéro d'ADR, ni numéro de dette, ni numéro de voie** (I-42).
