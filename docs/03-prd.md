# 03 — PRD (exigences produit)

> **Statut** : 🔮 CIBLE — dérivé de `INTERFLO_PRODUCT.md` v0.3.
> **Source d'autorité** : en cas de divergence, `INTERFLO_PRODUCT.md` fait foi.
> **Traçabilité** : chaque exigence renvoie à la décision PO qui la fonde (`I-nn`). Une exigence sans renvoi est une **proposition de rédaction**, signalée comme telle et **à valider**.
> **⚠️ Aucune lecture du dépôt Tvflo n'a été faite.**

---

## 1. Périmètre V1

**Dans le périmètre** : les deux formats de jeu (buzzer et élimination), développés tous les deux, avec activation progressive — l'élimination d'abord (I-26). Les **surfaces logicielles** (§3). Le multi-tenant (I-38). L'empreinte audio et la mesure de décalage (I-19, I-43), nécessaires au seul format buzzer.

⚠️ **Le boîtier de plateau n'est pas dans le périmètre V1.** Rien n'y est arbitré, pas même la connectivité (`05-materiel-et-sourcing.md` §3), et I-39 le rend optionnel : le public studio peut jouer sur téléphone seul.

**Hors périmètre V1** : voir §9.

---

## 2. Acteurs

| Acteur | Qui | Surface |
|---|---|---|
| **Téléspectateur** | Public à domicile | Application mobile |
| **Public studio** | Public présent sur le plateau | Application mobile **et/ou** boîtier physique (I-39) |
| **Animateur** | Présentateur, en direct | Console tablette |
| **Agent questions** | Valide les questions produites pendant l'émission (I-33) | Surface dédiée, **non spécifiée** |
| **Opérateur tenant** | Configure les jeux de sa chaîne, **dont la durée de fenêtre** (I-31) | Tableau de bord Filament |
| **Administrateur éditeur** | Règle les paramètres réservés à l'éditeur | Tableau de bord Filament |

> ⚠️ L'**agent questions** est un acteur nouveau, apparu le 2026-09-25. C'est un poste de production à la charge de la chaîne. Sa surface n'est pas encore spécifiée — voir `06-ux-ui.md` §5.

---

## 3. Les surfaces

| Surface | Technologie | Périmètre V1 | Décision |
|---|---|---|---|
| Application joueur | React Native | ✅ | I-41 |
| Console animateur | Surface dédiée, tablette | ✅ | I-41 |
| Console agent questions | Non spécifiée | ✅ | I-33 |
| Tableau de bord (tenant + éditeur) | Filament | ✅ | I-41 |
| Boîtier de plateau | Électronique embarquée | ❌ hors V1 | I-11 |

> ⚠️ Les quatre premières sont des surfaces logicielles ; la cinquième est du matériel et suit son propre calendrier. `06-ux-ui.md` §1 en donne le détail d'usage.

---

## 4. Exigences — appairage et session

| # | Exigence | Source |
|---|---|---|
| **EX-01** | Le téléspectateur s'appaire à une émission par **QR code affiché à l'antenne**. | I-14 |
| **EX-02** | Un **code court saisi à la main** double systématiquement le QR. Il n'est pas un secours facultatif : il est toujours disponible et annoncé à l'oral par l'animateur. | I-14 |
| **EX-03** | Le code court doit être **lisible à l'oral** : pas de caractères ambigus (0/O, 1/I/l, 5/S…). Longueur et alphabet à arrêter. | I-14 |
| **EX-04** | Le code est **renouvelé périodiquement en cours d'antenne**. Un code photographié est mort en quelques dizaines de secondes. | I-18 |
| **EX-05** | La rotation du code **ne doit pas déconnecter** un joueur déjà appairé. *(Proposition de rédaction — à valider.)* | — |
| **EX-06** | Le code est un **pointeur public**, jamais un secret et jamais un droit de jouer. L'autorisation vient uniquement de la fenêtre ouverte par l'animateur, côté serveur. | I-15 |
| **EX-07** | La session est **adossée à l'émission**. Émission terminée, l'accès est clos et l'application n'offre plus rien sur cette chaîne. | I-16 |
| **EX-08** | Le joueur peut avoir plusieurs chaînes ajoutées, mais **une seule session active à la fois**. | I-17 |
| **EX-09** | L'identification du joueur se fait par **numéro de téléphone vérifié**. Aucune pièce d'identité n'est demandée à l'inscription. | I-8 |

---

## 5. Exigences — déroulé d'un tour

| # | Exigence | Source |
|---|---|---|
| **EX-10** | L'**animateur ouvre et ferme** les fenêtres de participation. | I-2 |
| **EX-11** | Hors fenêtre, **le serveur refuse**. Ce n'est pas un bouton grisé côté client. | I-2 |
| **EX-12** | L'ouverture d'une fenêtre est signifiée **par l'application**, jamais par ce que le joueur voit à l'écran de télévision. | I-3 |
| **EX-13** | La réponse est un **choix multiple à 4 propositions**. | I-4 |
| **EX-14** | **Chaque proposition est elle-même un buzzer** : un seul geste vaut buzz et réponse. | I-5 |
| **EX-15** | Le geste est **horodaté sur l'appareil**, pas à l'arrivée serveur. | I-6 |
| **EX-16** | L'horloge de l'appareil est **synchronisée sur l'horloge serveur** à l'ouverture du tour. | I-6 |
| **EX-17** | Le serveur **rejette tout horodatage physiquement impossible** (horloge falsifiée). | I-6 |
| **EX-18** | Le serveur rejette toute réponse sous un **seuil de temps minimum** — aucun humain ne répond aussi vite. Seuil paramétrable. | I-30 |
| **EX-19** | La **durée de la fenêtre de réponse est configurable par tenant**. | I-31 |
| **EX-20** | ⚠️ La fenêtre est **personnelle, pas absolue** : elle court à partir du moment où *chaque joueur* voit la question. L'enveloppe serveur doit couvrir la fenêtre **plus le pire décalage attendu**. | §4.2 cadrage |
| **EX-21** | Chaque joueur reçoit un **retour individuel immédiat : juste ou faux**, sans révélation de la bonne réponse. | I-29 |
| **EX-22** | **Aucun resserrement** des propositions aux relances. Les 4 propositions restent. | I-29 |
| **EX-23** | Les deux populations (studio, domicile) **ne concourent jamais l'une contre l'autre**. Chacune a ses propres tours. | I-1 |

> ⚠️ **EX-18 et EX-19 sont deux choses différentes.** EX-18 est un plancher anti-automatisation ; EX-19 est la durée de la fenêtre. Ne pas n'en implémenter qu'une.

---

## 6. Exigences — format buzzer

| # | Exigence | Source |
|---|---|---|
| **EX-24** | Le **premier qui répond s'affiche au studio même s'il se trompe**, avec son nom et sa réponse. | I-7 |
| **EX-25** | L'animateur peut **relancer le tour** (ordre de grandeur : jusqu'à 3 fois, à sa main). Le nombre exact reste ouvert. | I-7 |
| **EX-26** | Le classement se fait sur le **temps de réaction** : temps absolu moins décalage individuel du joueur. | I-20 |
| **EX-27** | Le décalage individuel est **déduit du signal capté**, jamais d'une valeur envoyée par l'application. | I-21 |
| **EX-28** | Si la mesure de décalage échoue, le joueur **joue quand même**, dans un **classement séparé**. | I-23 |
| **EX-29** | Le studio n'affiche que le **classement mesuré**. | I-23 |
| **EX-30** | Le mode mesuré est **activable par chaîne** pour la première saison. ⚠️ I-25 ne fixe **aucune valeur par défaut** — à trancher. | I-25 |

---

## 7. Exigences — format élimination

| # | Exigence | Source |
|---|---|---|
| **EX-31** | Le format se joue en **5 manches**. | I-27 |
| **EX-32** | Le joueur qui se trompe est **verrouillé jusqu'à la fin du thème** ; il ne peut plus répondre aux manches suivantes. | I-27 |
| **EX-33** | Après chaque manche, le studio affiche le **nombre de joueurs ayant trouvé**. | I-27 |
| **EX-34** | Les questions **se complexifient** à chaque manche. | I-27 |
| **EX-35** | La fin de partie produit **plusieurs gagnants**. Selon la configuration du tenant : tous les survivants, ou **tirage au sort** pour en retenir 1, 3 ou 5. | I-28 |
| **EX-36** | Ce format **ne requiert aucune mesure de décalage**. Il doit fonctionner intégralement avec le mode mesuré désactivé. | §2.2 cadrage |

> ⚠️ **EX-35 a une conséquence réglementaire.** Le tirage au sort fait basculer le jeu de l'adresse vers le hasard. Voir `02-modele-economique.md` §4.

---

## 8. Exigences — questions, affichage et boîtier

| # | Exigence | Source |
|---|---|---|
| **EX-37** | Les questions sont **majoritairement tirées de l'émission elle-même** (plateau, invité, ce qui vient d'être dit ou montré), avec un petit pourcentage de culture générale. Répartition exacte non arrêtée. | I-32 |
| **EX-38** | Le direct est **transcrit avec horodatage**. Un modèle de langage propose des questions à partir de la transcription. | I-33 |
| **EX-39** | Un **agent humain valide chaque question** : il accède au time code, retrouve le passage, peut remonter au son enregistré, et corrige. | I-33 |
| **EX-40** | **Aucune question ne part à l'antenne sans validation humaine.** | I-33 |
| **EX-41** | Le studio n'affiche **pas la liste des répondants** : le gagnant du tour et un **compteur global** de participation. | I-10 |
| **EX-42** | Les 4 boutons du boîtier et les 4 lignes du téléphone déclenchent **le même événement applicatif**. | I-11 |
| **EX-43** | Le dispositif physique doit rendre le public à domicile **visible à l'antenne**. | I-12 |
| **EX-44** | Le public studio peut jouer **sur téléphone, sur boîtier, ou les deux** selon l'émission. | I-39 |
| **EX-45** | La détection de triche est **comportementale** : régularité anormale des réponses, temps sous le seuil humain. Jamais identitaire. ⚠️ Seul le second volet est spécifié (EX-18) ; la **détection de régularité anormale n'est pas conçue**. | I-9 |
| **EX-46** | Le **classement persistant entre émissions** est configurable par tenant, selon la ligne éditoriale. ⚠️ Aucune mécanique n'est spécifiée. | I-40 |

---

## 9. Hors périmètre V1

- **Vérification d'identité à l'inscription** — écartée définitivement (I-8), pas repoussée.
- **Réponse vocale** — écartée définitivement (I-4).
- **Marquage audio incrusté** (watermark) — écarté (I-19).
- **Sponsoring télécom** — abandonné (I-34).
- **Départage final au temps en format élimination** — non retenu ; c'est le tirage au sort qui départage (I-28).

---

## 10. ⚠️ Ce que ce PRD ne spécifie pas

Ces points sont **ouverts** et ne doivent pas être comblés par supposition :

1. **Architecture temps réel et tenue de charge.** Voir `04-architecture.md`.
2. **Modèle de données définitif.** Voir `07-modele-de-donnees.md` — c'est un squelette, pas un schéma.
3. **Recouvrement fonctionnel avec Voxflo.** Voxflo enrôle déjà le public studio (identité + place), Interflo fait jouer ce même public. Réemploi ou duplication ? **Demande de lire le dépôt.**
4. **Valeurs numériques** : seuil anti-automatisation, durée de fenêtre par défaut, période de rotation du code, longueur du code court, nombre de relances. Toute valeur proposée ailleurs dans ces documents est un **point de départ à corriger au premier tournage**, jamais une valeur validée.
5. **Répartition questions-plateau / culture générale** (EX-37).
6. **Calibration de difficulté** : qui écrit, à quel rythme, et comment rattraper une manche qui élimine tout le monde ou personne.
7. **Surface de l'agent questions.**
8. **Mode dégradé** : comportement si l'infrastructure sature en plein direct.
9. **Synchronisation d'horloge** (I-6) : méthode, tolérance, seuils de rejet. EX-16 et EX-17 énoncent le *quoi* ; le *comment* est ouvert (§16 question 14).
10. **Détection de régularité anormale** (EX-45) : rien n'est conçu.
11. **Mécanique du classement persistant** (EX-46).
12. **Comportement d'un joueur appairé quand le code tourne** (EX-05, CA-06).

---

## 11. Critères d'acceptation V1

Un critère qui dépend d'une inconnue de §10 est marqué ⏸ et ne peut pas être évalué aujourd'hui.

| # | Critère |
|---|---|
| **CA-01** | Une émission complète en format élimination se joue de bout en bout, **avec le mode mesuré désactivé**. |
| **CA-02** | Le serveur refuse toute réponse hors fenêtre, y compris avec un client modifié. |
| **CA-03** | Un joueur dont l'horloge est falsifiée est rejeté, pas classé. |
| **CA-04** | Deux tenants ne voient jamais les joueurs ni les données l'un de l'autre. |
| **CA-05** | Un joueur qui refuse la permission micro **peut jouer intégralement dans les deux formats** — en élimination sans restriction, en buzzer dans le classement séparé (I-23). |
| **CA-06** ⏸ | La rotation du code ne déconnecte aucun joueur appairé. ⚠️ **Repose sur EX-05, qui est une proposition non validée** : §16 question 15 de `INTERFLO_PRODUCT.md` demande encore si un joueur appairé perd sa session quand le code tourne. Non évaluable tant que le PO n'a pas tranché. |
| **CA-07** | Chaque joueur reçoit son retour juste/faux, sans que la bonne réponse ne fuite dans la charge utile réseau. |
| **CA-08** ⏸ | Tenue de charge au pic — **non évaluable** tant que §10, point 1 est ouvert. |
| **CA-09** ⏸ | Fiabilité de la reconnaissance audio en conditions domestiques — **non évaluable** sans test terrain. |

> ⚠️ **CA-07 mérite attention à l'implémentation** : si le client reçoit la bonne réponse pour afficher « faux », elle est lisible dans le trafic. Le calcul doit rester serveur.
