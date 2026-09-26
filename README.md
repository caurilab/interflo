# Interflo

Application de participation en direct aux jeux d'une émission de télévision — pour le public en studio et pour les téléspectateurs à domicile.

Produit de la suite **Tvflo** (Cauri Lab), techniquement séparé de Tvflo BOS.

> **Statut : cadrage.** Zéro code. Tout le contenu de ce dépôt est documentaire.

---

## Démarrer

Lire **`PROMPT-DEMARRAGE.md`**, puis **`docs/INTERFLO_PRODUCT.md`**.

---

## Structure

```
docs/                Documentation produit et technique
  decisions/         Décisions techniques (numérotation propre à Interflo)
  rapports/          Rapports d'avancement
ops/                 Exploitation et déploiement
interflo-api/        API Laravel + Filament
interflo-app/        Application joueur React Native
interflo-web/        Console animateur + console agent questions
interflo-firmware/   Boîtier de plateau
```

---

## Documentation

| Fichier | Contenu |
|---|---|
| **`docs/INTERFLO_PRODUCT.md`** | **Source d'autorité.** Décisions scellées et leurs justifications. |
| `docs/VOXFLO_PRODUCT.md` | Cadrage du produit voisin (recouvrement à instruire). |
| `docs/01-vision-et-concept.md` | Le produit, ce qu'il résout, ce qui a été écarté. |
| `docs/02-modele-economique.md` | Postes de coût et cadre légal. Largement ouvert. |
| `docs/03-prd.md` | Exigences, tracées aux décisions. |
| `docs/04-architecture.md` | Principes tranchés, couche temps réel non conçue. |
| `docs/05-materiel-et-sourcing.md` | Boîtier de plateau. Rien d'arbitré. |
| `docs/06-ux-ui.md` | Les quatre surfaces, contraintes du public visé. |
| `docs/07-modele-de-donnees.md` | Squelette d'entités. **Pas un schéma.** |
| `docs/08-chaine-de-mesure.md` | Empreinte audio et mesure du décalage. |
| `docs/09-contrat-api.md` | Règles du contrat. Aucune route définie. |
| `docs/10-contrat-api-console-animateur.md` | La surface de direct. |
| `docs/11-contrat-api-boitier.md` | Protocole de plateau. Non instruit. |
| `docs/12-perspectives-v2.md` | Repoussé, écarté, et pourquoi. |
| `docs/13-integration-acrcloud.md` | **Comment brancher ACRCloud**, côté console et côté code. |
| `docs/etat-du-projet.md` | Ce qui bloque quoi. |

---

## Les deux formats de jeu

| | **Élimination** | **Buzzer** |
|---|---|---|
| Produit | Un groupe de finalistes | Un gagnant unique |
| Manches | 5 | Un tour, avec relances |
| Départage | Bonnes réponses cumulées | Temps de réaction |
| Mesure de décalage | **Aucune** | Requise |
| Statut | **Premier activé** | Développé, activé ensuite |

---

## Les quatre blocages

1. 🔴 **Architecture temps réel** — non conçue. Bloque le modèle de données et le contrat d'API.
2. 🟠 **Test terrain de la chaîne de mesure** — aucun. Bloque l'activation du buzzer.
3. 🟠 **Recouvrement avec Voxflo** — demande de lire le dépôt Tvflo.
4. 🟡 **Cadre légal** — non instruit. Bloque la production avec lots réels.

Détail dans `docs/etat-du-projet.md`.

---

## Trois règles

1. **Ne jamais inventer un acquis.** Les ⚠️ et 🚧 signalent des trous réels, pas des omissions.
2. **Aucune valeur de réglage n'est validée** — seuils, durées, périodes, longueurs. Tout cela est paramétrable, jamais en dur.
   Trois valeurs *sont* scellées et se codent comme telles : **4 propositions** (I-4), **5 manches** (I-27), **1, 3 ou 5 gagnants** (I-28).
3. **Rien n'a été lu du dépôt Tvflo.** Ce qui concerne BOS est à instruire au source.
