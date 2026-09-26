# 08 — Chaîne de mesure (empreinte audio et décalage)

> **Statut** : 🔮 CIBLE. Fournisseur choisi, **rien n'est mesuré**.
> **⚠️ Cette chaîne ne concerne que le format buzzer.** Le format élimination n'en a besoin d'aucune partie, et doit fonctionner intégralement sans elle (EX-36).
> **Recherche du 2026-09-25** — sources en §8.

---

## 1. Ce que la chaîne doit produire

Une seule valeur : **le décalage de diffusion propre à chaque joueur**, à l'instant où il répond.

De cette valeur dépend le classement au temps de réaction (I-20) : on soustrait le décalage individuel pour ne comparer que le temps écoulé depuis le moment où *chaque joueur* a vu la question.

> ⚠️ **À ne pas confondre avec le déroulé d'un tour.** Le décalage reste du **temps d'antenne pour le spectacle** — l'animateur continue son show pendant que la fenêtre court. Il n'est neutralisé que dans le **calcul du classement**. Les deux énoncés portent sur deux choses différentes (`INTERFLO_PRODUCT.md` §5).

**Exemple donné par le PO** : deux joueurs, l'un à dix secondes de décalage, l'autre à cinq. On ramène le premier sur le second en retirant cinq secondes. Ensuite, celui qui met trois secondes à répondre bat celui qui en met cinq. Le décalage est neutralisé ; le temps de réponse n'est pas touché.

---

## 2. Le mécanisme retenu (I-19, I-43)

L'application écoute le son ambiant sur une fenêtre courte, en calcule une **empreinte**, et la compare au flux réel de la chaîne. Fournisseur : **ACRCloud**, service *Live Channel Detection*, offre premium souscrite le 2026-09-25.

### 2.1 Pourquoi l'empreinte plutôt que le marquage audio

Le **marquage audio** (watermark inaudible) suppose d'incruster un signal dans la diffusion, donc de **modifier la chaîne technique du diffuseur**. Interflo étant vendu à plusieurs chaînes (I-13), il faudrait négocier cette modification avec chacune — frein d'adoption majeur.

⚠️ **Le marquage n'est pas écarté parce qu'il fonctionnerait mal.** Il fonctionnerait *mieux* sur l'horodatage, puisqu'il le porte nativement. Il est écarté pour une raison commerciale. Ne pas déformer cette raison.

### 2.2 Le champ qui rend la mesure possible

La réponse de détection de chaîne live renvoie un champ **`timestamps_ms`** : l'horodatage Unix UTC en millisecondes du flux live correspondant au son reconnu.

**Le décalage individuel est donc une soustraction** — heure serveur au moment de la capture, moins `timestamps_ms`. Il n'y a rien à reconstruire.

Un champ **`result_type`** distingue par ailleurs `live` de `timeshift`, ce qui identifie déjà les joueurs qui regardent en différé.

> ✅ **Réserve levée.** Une version antérieure du cadrage posait que « l'empreinte prouve qu'on entend, pas quand », et que retrouver le décalage serait du travail. C'était faux, et la correction a été versée le 2026-09-25.

---

## 3. ⚠️ Ce que ça exige des chaînes clientes

Une version antérieure du cadrage affirmait que l'empreinte audio « ne demande rien au diffuseur ». **C'est trop fort, et l'erreur a été corrigée.**

Il faut **ingérer le flux de la chaîne en temps réel** : un outil d'empreinte de chaîne live doit tourner sur un serveur pour alimenter le dépôt d'empreintes, et c'est seulement ensuite que le SDK mobile peut identifier la chaîne.

**Il faut donc l'accès au flux de diffusion de chaque chaîne cliente, sous forme d'URL.**

C'est infiniment plus léger que modifier leur chaîne technique — aucun signal à incruster, aucun équipement à installer chez eux — mais ce n'est pas rien, et **c'est à négocier chaîne par chaîne**.

Questions à régler par client : qui fournit l'URL, sous quelle forme, avec quel engagement de disponibilité. **Si le flux tombe, la mesure tombe avec lui.**

> 🔧 **Comment cela se branche concrètement** — création du bucket, du projet, ingestion du flux, identifiants du SDK : voir `13-integration-acrcloud.md`.

---

## 4. La fenêtre d'écoute (I-22)

### 4.1 Pourquoi une fenêtre et pas une écoute continue

Le décalage **n'est pas figé** pendant l'émission : la mémoire tampon du décodeur se remplit, le réseau varie, le flux se resynchronise. Une mesure prise une fois au début serait périmée au moment de la réponse, d'un écart possiblement supérieur à ce qu'on cherche à départager.

La réponse retenue, proposée par le PO : ouvrir le micro sur une **fenêtre courte, juste avant les questions**, pendant que l'animateur annonce le passage aux téléspectateurs.

Cela résout trois choses d'un coup :

- **Fraîcheur** — la mesure est prise quelques secondes avant le geste.
- **Batterie** — déterminante sur les téléphones d'entrée de gamme, probablement une part importante du public visé.
- **Acceptabilité** — une application qui écoute quelques secondes au moment du jeu s'explique ; une application qui écoute toute la soirée se fait désinstaller.

### 4.2 ⚠️ La durée est probablement sous-dimensionnée

Une source tierce évoque un extrait de **dix à quinze secondes** soumis pour la reconnaissance musicale. Si c'est le même ordre de grandeur pour la détection de chaîne live, « quelques secondes » ne suffit pas.

**Conséquence directe** : le temps de préparation obligatoire dans la conduite d'émission s'allonge d'autant. L'animateur devra meubler davantage.

> ⚠️ **À mesurer en test réel, pas à supposer.** C'est la première chose à établir avec l'offre souscrite (`INTERFLO_PRODUCT.md` §16, question 3).

---

## 5. Les garde-fous

| # | Garde-fou | Pourquoi |
|---|---|---|
| **G-1** | Le décalage vient du **signal capté**, jamais d'une valeur envoyée par l'application (I-21). | Une valeur déclarée par le client est une avance fabriquée. Soustraire un décalage ouvre cette porte ; il faut la fermer d'emblée. |
| **G-2** | La mesure est un **bonus, jamais une condition pour jouer** (I-23). | Micro refusé, téléviseur coupé, pièce bruyante : le joueur joue quand même, dans un classement séparé. |
| **G-3** | Le studio n'affiche que le **classement mesuré** (I-23). | Mélanger mesurés et non-mesurés détruit la promesse d'équité ; exclure les non-mesurés fait perdre une grande partie du public dès le premier soir. |
| **G-4** | Le mécanisme n'est **pas expliqué au grand public**, mais **documenté précisément pour les chaînes** (I-24). | C'est la chaîne qui devra répondre le jour où un joueur conteste un résultat. Obligation contractuelle. |
| **G-5** | Le mode mesuré est **activable par chaîne** pour la première saison (I-25). ⚠️ Aucune valeur par défaut n'est fixée par I-25. | On observe le comportement réel et le taux de réussite avant de faire dépendre un classement de cette mesure. |

---

## 6. ⚠️ Réserves à ne pas perdre

**La précision de la mesure devient la précision du classement.** Un décalage mesuré à la demi-seconde interdit de départager deux joueurs séparés par cent millisecondes. C'est acceptable **si c'est assumé** — on classe alors par tranches. Il ne faut jamais prétendre à la milliseconde ce qui est mesuré à la demi-seconde.

**Aucune mesure publique n'existe** sur la précision temporelle atteignable ni sur le taux de réussite en salon bruyant réel. Ces deux points ne se referment pas par la recherche — ils se referment par un test.

**Le fournisseur a été choisi avant le test terrain.** L'offre premium est souscrite ; les deux questions ci-dessus restent entières, et la consommation réelle devra être surveillée par rapport aux quotas.

**La règle applicable aux joueurs non mesurés sera contestable quelle qu'elle soit.** Elle doit figurer au règlement du jeu, pas seulement dans le code.

**Cette chaîne ne prouve pas qu'un joueur regarde l'émission.** Elle prouve qu'un téléviseur diffusant l'émission joue à proximité du téléphone. Ne jamais la présenter autrement.

---

## 7. Alternatives auto-hébergées

Elles existent : **Olaf**, **Panako**, **audfprint**, **dejavu**, **Chromaprint**.

Deux réserves sérieuses avant d'envisager cette voie :

- ⚠️ **Point juridique** : Panako signale que des brevets limitent l'usage de certains algorithmes selon les conditions et les régions, et invite à consulter un spécialiste en propriété intellectuelle.
- **Aucune ne fournit de SDK mobile prêt à l'emploi.** Toute l'intégration serait à construire.

Une curiosité utile pour le boîtier : **Olaf** tourne sur microcontrôleur (type ESP32, à partir d'environ 250 ko de mémoire) et se compile en WebAssembly pour le navigateur.

---

## 8. Sources

- [Live Channels — métadonnées de détection (ACRCloud)](https://docs.acrcloud.com/reference/identification-api/metadata/live-channels.md) — champs `timestamps_ms`, `result_type`, `score`
- [Live Channel Detection (ACRCloud)](https://www.acrcloud.com/live-channel-detection/)
- [Service Usage — FAQ (ACRCloud)](https://docs.acrcloud.com/faq/service-usage) — ingestion du flux, outil d'empreinte de chaîne live
- [Detect Live & Timeshift TV Channels (ACRCloud)](https://docs.acrcloud.com/get-started/tutorials/detect-live-and-timeshift-tv-channels.md)
- [Panako (dépôt)](https://github.com/INNLAB-KZ/panako) — mention des restrictions de brevets
- [Olaf (JOSS)](https://joss.theoj.org/papers/10.21105/joss.05459.pdf) — empreinte sur microcontrôleur et WebAssembly

---

## 9. Questions ouvertes

1. **Durée minimale de fenêtre** pour une reconnaissance fiable en conditions domestiques réelles.
2. **Précision atteignable** sur la mesure de décalage, et granularité de classement qui en découle.
3. **Règle applicable aux joueurs non mesurés** — classement séparé, décalage par défaut, exclusion du lot principal ?
4. **Ingestion des flux** : qui fournit l'URL, sous quelle forme, avec quel engagement ?
5. **Consommation réelle** par émission et par joueur, face aux quotas de l'offre souscrite.
6. **Permission micro** : formulation exacte, moment de la demande, comportement en cas de refus.
7. **Conservation des enregistrements audio** captés — durée, base légale. Voir `02-modele-economique.md` §4.
