# 13 — Branchement ACRCloud

> **Statut** : 🔧 OPÉRATIONNEL. Ce document décrit **ce que le PO doit faire dans la console ACRCloud** pour que Claude Code puisse ensuite brancher le SDK et l'API.
> **Pré-requis** : offre premium souscrite (I-43, 2026-09-25).
> **⚠️ Rien de ceci n'a été testé.** Les étapes viennent de la documentation ACRCloud, pas d'une mise en œuvre.
> Complète `08-chaine-de-mesure.md`, qui explique *pourquoi*. Ce document dit *comment*.

---

## 1. La chose à comprendre avant de cliquer

La reconnaissance ne compare pas le son du téléphone à « la chaîne » dans l'absolu. Elle le compare à **un dépôt d'empreintes que vous alimentez vous-même, en continu, à partir du flux de la chaîne**.

Il y a donc **deux moitiés** :

| Moitié | Où ça tourne | Qui s'en occupe |
|---|---|---|
| **Ingestion** — fabriquer les empreintes du direct | **Sur votre serveur**, en permanence | Vous (exploitation) |
| **Reconnaissance** — comparer le son ambiant capté | Dans l'application, via le SDK | Claude Code (développement) |

⚠️ **Sans la première moitié, la seconde ne renvoie rien.** C'est le point que le cadrage avait initialement sous-estimé.

---

## 2. Ce que vous faites dans la console

### Étape 1 — Créer un bucket « Live Channels »

Dans le service **Live Channel Detection**, créez un bucket de type **Live Channels**.

Choisissez le type **Live Ingest** pour ajouter vos chaînes à partir de rien — c'est votre cas, les chaînes ivoiriennes ne sont pas dans le catalogue public.

### Étape 2 — Créer un projet « Live Channel Detection »

Sous **Projects**, créez un projet de type **Live Channel Detection**, et **rattachez-lui le bucket** créé à l'étape 1.

> Si vous vouliez un jour reconnaître aussi des contenus pré-enregistrés (publicités, génériques), le type **Hybrid Recognition** permet de rattacher plusieurs buckets au même projet. Inutile pour Interflo aujourd'hui.

### Étape 3 — Ajouter une chaîne au bucket

Ouvrez le bucket, cliquez **Add**, et renseignez les informations de la chaîne.

⚠️ **Le champ demandé est l'URL interne du flux de la chaîne sur votre serveur local** — pas un lien public de replay, pas une page web. C'est ici que la négociation avec chaque chaîne cliente se matérialise (`08-chaine-de-mesure.md` §3).

### Étape 4 — Récupérer les trois identifiants

Dans le tableau de bord du projet, relevez :

- `host`
- `access_key`
- `access_secret`

**Ce sont ces trois valeurs que vous transmettez à Claude Code.** Elles vont dans `.env`, jamais dans le code :

```
ACRCLOUD_HOST=
ACRCLOUD_ACCESS_KEY=
ACRCLOUD_ACCESS_SECRET=
```

---

## 3. L'outil d'empreinte — la partie que vous devez héberger

C'est le **Live Channel Fingerprinting Tool**.

| Point | Ce qu'il faut savoir |
|---|---|
| **Où** | Sur **votre serveur**, celui qui a un accès réseau rapide aux flux. |
| **Configuration** | Un fichier `client.conf` : la paire de clés de la console, et le nom du bucket Live. |
| **Lancement** | `python stream.py client.conf`, ou en arrière-plan avec `nohup`. |
| **Durée** | **En continu.** Ce n'est pas un traitement ponctuel. |
| **Changer une URL** | Il faut **arrêter et relancer** le processus. Pas de rechargement à chaud. |
| **Vérification** | Dans la console, une chaîne passe de **Processing** à **Ready**. Tant qu'elle n'est pas *Ready*, aucune reconnaissance ne fonctionne. |

> ⚠️ **Conséquence d'exploitation à ne pas découvrir en direct** : si le processus s'arrête pendant une émission, ou si une chaîne repasse en *Processing*, la mesure de décalage tombe — et avec elle le classement du format buzzer. Il faut une supervision et une alerte sur cet outil, au même titre que sur l'API.

---

## 4. Ce que Claude Code branchera ensuite

Trois chemins d'accès existent, et ils n'ont pas le même rôle :

| Chemin | Usage chez nous |
|---|---|
| **SDK mobile** | L'application joueur : capture le son ambiant et interroge la reconnaissance. **C'est le principal.** |
| **SDK backend** | Traitements côté serveur, si besoin. |
| **Identification API** | Appel direct, sans SDK. Utile en test ou en repli. |

Tous utilisent les mêmes trois identifiants.

**La réponse utile** est décrite en `08-chaine-de-mesure.md` §2.2 :

- `timestamps_ms` — horodatage Unix UTC en millisecondes du flux live reconnu. **Le décalage individuel est la soustraction avec l'heure serveur.**
- `result_type` — `live` ou `timeshift`.
- `score` — confiance de la correspondance.

---

## 5. Ce que vous pouvez faire dès maintenant

Dans l'ordre, et sans attendre le développement :

1. **Créer le bucket et le projet** (étapes 1 et 2). Quelques minutes.
2. **Brancher une seule chaîne** en test — la plus facile d'accès. Vérifier qu'elle passe en **Ready**.
3. **Mesurer la durée de fenêtre réellement nécessaire** dans un salon : téléviseur allumé, volume normal, un peu de bruit ambiant. C'est la question 3 de `INTERFLO_PRODUCT.md` §16, celle qui décide si I-22 tient (`08-chaine-de-mesure.md` §4.2).
4. **Relever la consommation** sur ces essais, et la rapporter aux quotas de l'offre.

> Le point 3 est le plus important. Il ne demande aucun développement, et il débloque l'activation du format buzzer.

---

## 6. ⚠️ Ce que ce document ne règle pas

- **Combien de chaînes** peuvent être ingérées simultanément sur un serveur, et avec quelles ressources. Non documenté publiquement.
- **Les formats de flux** acceptés. Non spécifiés dans la documentation consultée.
- **La conduite à tenir si une chaîne repasse en *Processing*** pendant une émission.
- **Le coût réel** à notre volume — voir `02-modele-economique.md` §2.1.
- **Qui fournit l'URL du flux** chez chaque chaîne cliente, et avec quel engagement de disponibilité.

---

## 7. Sources

- [Detect Live & Timeshift TV Channels (ACRCloud)](https://docs.acrcloud.com/get-started/tutorials/detect-live-and-timeshift-tv-channels.md)
- [Live Channel Fingerprinting Tool (ACRCloud)](https://docs.acrcloud.com/tools/live-channel-fingerprinting-tool.md)
- [Live Channels — métadonnées (ACRCloud)](https://docs.acrcloud.com/reference/identification-api/metadata/live-channels.md)
- [Service Usage — FAQ (ACRCloud)](https://docs.acrcloud.com/faq/service-usage)
