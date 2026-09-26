# 09 — Contrat d'API (cadre)

> **Statut** : 🚧 **AUCUNE ROUTE N'EST DÉFINIE.** Ce document pose les **règles** auxquelles le contrat devra se conformer. Il ne contient ni chemin, ni verbe, ni charge utile.
>
> ⚠️ **Ne pas implémenter de route à partir de ce document.** Il servira à *valider* un contrat, pas à le produire.
> **⚠️ Aucune lecture du dépôt Tvflo n'a été faite.**

---

## 1. Pourquoi le contrat n'est pas écrit

Le contrat d'API d'un produit de direct découle de son architecture temps réel. Celle-ci n'est pas conçue (`04-architecture.md` §5), et deux profils de charge cohabitent — buzzer et élimination — dont les besoins diffèrent.

Écrire des routes maintenant figerait un transport qui n'a pas été choisi.

---

## 2. Les trois frontières

Interflo expose trois surfaces de contrat, qui n'ont ni la même population, ni les mêmes exigences.

| Frontière | Population | Exigence dominante |
|---|---|---|
| **Joueur** | Grand public, volumes massifs | Latence, économie de données, robustesse au réseau dégradé |
| **Pilotage** | Animateur, agent questions, opérateur tenant | Fiabilité absolue, chemin court en cas de panne |
| **Sortie** | BOS, dispositif de plateau | Asynchrone, sans effet sur le direct |

---

## 3. Règles auxquelles tout contrat devra se conformer

| # | Règle | Source |
|---|---|---|
| **R-1** | **Le téléspectateur ne touche jamais l'API BOS**, sous aucune forme. | I-37 |
| **R-2** | **Aucun appel synchrone vers BOS pendant une fenêtre de jeu.** Le contexte est copié avant l'émission. | I-36 |
| **R-3** | L'autorisation de répondre est un **état serveur**. Hors fenêtre, le serveur refuse — jamais un simple bouton grisé côté client. | I-2 |
| **R-4** | **La bonne réponse ne sort jamais du serveur.** Le verdict juste/faux se calcule serveur et ne transite qu'en résultat. | CA-07, INV-2 |
| **R-5** | Le **retour au joueur est d'un bit** : juste ou faux. Aucun pourcentage, aucune statistique, aucun resserrement. | I-29 |
| **R-6** | Un **horodatage client** n'est jamais une donnée de confiance. Il est borné et validé avant usage. | I-6, I-30 |
| **R-7** | Une **mesure de décalage** n'est jamais acceptée depuis le client. | I-21 |
| **R-8** | L'**absence de mesure** est une réponse valide, pas une erreur. | I-23 |
| **R-9** | Le **code d'appairage n'autorise rien**. Il désigne une chaîne et une émission. | I-15 |
| **R-10** | Tout est **cloisonné par tenant**. Deux tenants ne se voient jamais. | I-38 |
| **R-11** | La charge utile côté joueur doit rester **économe en données** — c'est un critère d'adoption, pas une optimisation tardive. | §7 vision |

---

## 4. Ce qui devra figurer au contrat quand il sera écrit

Une liste de sujets, pas de routes :

- Appairage (QR et code court), et comportement à la rotation du code.
- Vérification du numéro de téléphone.
- Ouverture et fermeture de fenêtre, par l'animateur.
- Soumission d'une réponse, avec son horodatage client.
- Retour individuel juste/faux.
- Compteurs d'affichage studio (gagnant, participation, survivants).
- Soumission et validation d'une question.
- Sortie vers le dispositif de plateau (`11-contrat-api-boitier.md`).
- Lecture des résultats par BOS, **après l'émission**.

---

## 5. ⚠️ Points de vigilance

**R-4 est le plus facile à violer sans s'en rendre compte.** Si le client reçoit la bonne réponse pour afficher « faux », elle est lisible dans le trafic réseau. Une implémentation naïve — envoyer la question complète avec sa clé de correction — casse le jeu en une soirée.

**R-2 se dissout par petites touches.** Un seul appel « juste pour vérifier le tenant » pendant une fenêtre, et l'isolation de panne disparaît. Toute dépendance synchrone vers BOS introduite pendant le direct est une régression d'architecture, pas un détail d'implémentation.

**R-6 ne suffit pas seul.** Borner l'horodatage empêche la falsification grossière ; cela n'empêche pas l'assistance par modèle de langage (`INTERFLO_PRODUCT.md` §8), qui n'est pas détectable et se traite par le règlement.
