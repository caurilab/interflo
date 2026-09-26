# 12 — Perspectives V2

> **Statut** : 📌 PARKING. Rien ici n'est engagé. Ce document existe pour que les idées écartées ne soient pas **re-proposées comme neuves**, et pour que celles qui ont été repoussées ne soient pas perdues.

---

## 1. Repoussé après la V1

> ⚠️ « Repoussé » veut dire **activé plus tard**, pas « écarté ». Le format buzzer en particulier est **développé en V1** (I-26). Ne pas le reléguer sur la foi de ce titre.

### 1.1 Activation du format buzzer

Le format buzzer est **développé en V1** mais **activé après l'élimination** (I-26).

Conditions d'activation :

- La durée de fenêtre d'écoute nécessaire est mesurée en conditions réelles (`08-chaine-de-mesure.md` §4.2).
- La précision atteignable sur le décalage est connue, et la granularité de classement assumée.
- La consommation ACRCloud réelle est vérifiée face aux quotas.
- L'architecture temps réel est conçue et éprouvée au pic.

⚠️ **Ne pas activer le buzzer sur la foi d'une démonstration.** C'est le profil de charge le plus exigeant du produit, et le seul qui dépende d'une mesure externe.

### 1.2 Surface web joueur

Évoquée comme repli si l'application ne s'installe pas. Non tranchée.

### 1.3 Personnalisation par tenant

Une chaîne peut-elle habiller l'application à ses couleurs ? Non abordé. Conséquences sur l'architecture de thème si la réponse est oui.

### 1.4 Mécanique du classement persistant

⚠️ **Le principe n'est pas repoussé** : I-40 est une décision scellée, et EX-46 le porte au PRD — le classement persistant entre émissions est **configurable par tenant** selon la ligne éditoriale.

Ce qui est repoussé, c'est sa **mécanique** : aucune n'a été spécifiée.

---

## 2. Écarté — ne pas re-proposer sans rouvrir la raison

| Idée | Pourquoi écartée | Ce qu'il faudrait rouvrir |
|---|---|---|
| **Buzzer sans réponse** (le premier qui appuie gagne) | Départage sur la latence réseau, pas la connaissance. Le téléspectateur à domicile ne gagnerait jamais. | Les deux objections de `INTERFLO_PRODUCT.md` §2.1 |
| **Réponse vocale** | Trois raisons cumulatives, dont la principale : risque de **refuser de bonnes réponses** en français ivoirien. | `INTERFLO_PRODUCT.md` §6 |
| **Pièce d'identité à l'inscription** | **Ne protège pas du piratage** — celui qui automatise son téléphone a un compte vérifié, le sien. Ce n'est pas « trop lourd », c'est inefficace. | `INTERFLO_PRODUCT.md` §10.1 |
| **Marquage audio incrusté** (watermark) | Exige de **modifier la chaîne de diffusion de chaque client**. Il fonctionnerait pourtant *mieux* sur l'horodatage. | `08-chaine-de-mesure.md` §2.1 |
| **Sponsoring télécom** | Assume publiquement que les mieux connectés gagnent plus souvent, ce que I-20 neutralise. Et sans course à la vitesse, il n'y a plus de latence à vendre. | I-20 et I-34 ensemble |
| **Départage final au temps en élimination** | Réintroduirait toute la chaîne de mesure. Le tirage au sort départage (I-28). | I-28 et sa conséquence réglementaire |
| **UWB pour la localisation** *(Voxflo)* | Coût, calibration, et surtout : rend des coordonnées, pas un numéro de siège — le mapping est à refaire à chaque changement de décor. | `VOXFLO_PRODUCT.md` |

---

## 3. Idées non traitées, conservées

### 3.1 Récompenser autrement que par le lot principal

Le PO a évoqué « récompenser sur le digital, avec une liste de noms », sans préciser. C'est la piste qui répondrait à la question *« le non-premier peut-il gagner quelque chose ? »* autrement que par le format élimination.

Non spécifié, mais noté : c'est une piste qui ne coûterait rien à l'architecture.

### 3.2 Questions dépendant de l'image

Une question dont la réponse exige d'avoir vu un élément affiché quelques secondes plus tôt. Zéro infrastructure, mais contrainte d'écriture — et reste relayable par quelqu'un qui regarde.

Rejoint partiellement I-32, sans être identique : I-32 porte sur la **matière** (le plateau), celle-ci sur la **modalité** (il faut avoir vu).

### 3.3 Empreinte audio auto-hébergée

Olaf, Panako, audfprint, dejavu, Chromaprint. Écartée pour la V1 au profit d'ACRCloud (I-43).

⚠️ Si rouverte : **point juridique** — Panako signale que des brevets limitent l'usage de certains algorithmes selon les régions. Et aucune de ces briques ne fournit de SDK mobile prêt à l'emploi.

---

## 4. ⚠️ Le débat à ne pas rejouer

Le format élimination a failli être abandonné au motif qu'il « existe déjà ailleurs ».

**Cet argument a été écarté** : un format éprouvé est une bonne nouvelle — le public le comprend, les modes de défaillance sont documentés — et l'information venait d'un souvenir non vérifié.

Les **vraies** objections au format élimination sont au nombre de trois, et elles sont en `INTERFLO_PRODUCT.md` §4.3 : le hasard qui survit longtemps, la calibration de difficulté en direct, et la convergence vers un groupe plutôt que vers une personne.

Si le format doit être abandonné un jour, que ce soit pour celles-là.
