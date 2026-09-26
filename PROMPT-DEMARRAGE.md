# PROMPT DE DÉMARRAGE — Interflo

> À donner à Claude Code au premier lancement dans ce dépôt.

---

## Contexte

Tu démarres **Interflo**, une application de participation en direct aux jeux d'une émission de télévision. Produit de la suite Tvflo (Cauri Lab), mais **techniquement séparé** de Tvflo BOS.

**Rien n'est codé.** Aucun scaffolding n'a été fait. Tout le contenu actuel du dépôt est documentaire.

---

## À lire avant toute chose, dans cet ordre

1. **`docs/INTERFLO_PRODUCT.md`** — la note de cadrage. **Source d'autorité** : en cas de divergence avec n'importe quel autre document, c'est elle qui fait foi. Lis-la en entier, y compris sa dernière section (« Ce qui ne doit pas être re-fabriqué »).
2. **`docs/etat-du-projet.md`** — ce qui bloque quoi, et ce qui peut avancer.
3. **`docs/03-prd.md`** — les exigences, tracées aux décisions.
4. **`docs/04-architecture.md`** — les principes, et ce qui n'est pas conçu.

Les autres documents se lisent au moment où ils servent.

---

## Les règles de ce projet

### 1. Ne jamais inventer un acquis

Beaucoup de choses ne sont **pas** décidées, et les documents le disent explicitement. Quand tu rencontres un ⚠️ ou un 🚧, c'est un trou réel, pas une omission à combler.

**Si une information manque : dis-le, ne suppose pas.**

### 2. Aucune valeur numérique n'est validée

Seuil anti-automatisation, durée de fenêtre, période de rotation du code, longueur du code court, nombre de relances, enveloppe à meubler.

Toute valeur que tu écris est un **point de départ paramétrable**, jamais une constante en dur, et tu la signales dans ton rapport.

⚠️ **Exception** : trois valeurs *sont* scellées par une décision — **4 propositions** (I-4), **5 manches** (I-27), **1, 3 ou 5 gagnants** (I-28). Celles-là sont des règles du jeu, pas des réglages.

### 3. Rien n'a été lu du dépôt Tvflo

Toute la documentation a été produite **sans accès au code de BOS**. Ce qui concerne BOS est marqué « à instruire au source ». Si tu as accès au dépôt Tvflo en local, **vérifie avant d'affirmer** — et signale les écarts au PO.

### 4. Les deux invariants qui protègent le produit

- **La bonne réponse ne sort jamais du serveur.** Si le client la reçoit pour afficher « faux », elle est lisible dans le trafic et le jeu est cassé en une soirée.
- **Aucun appel synchrone vers BOS pendant une fenêtre de jeu.** Un seul appel « juste pour vérifier le tenant » et l'isolation de panne disparaît — avec le risque de faire tomber l'antenne.

Ces deux-là ne se négocient pas au moment de l'implémentation.

### 5. Deux paramètres à ne pas confondre

- **Plancher anti-automatisation** (I-30) : seuil minimum sous lequel le serveur rejette.
- **Durée de fenêtre** (I-31) : configurable par tenant.

Ce sont deux choses différentes.

---

## Par où commencer

Le PO attend d'abord le **scaffolding**, puis le **format élimination** — c'est le chemin qui ne dépend d'aucune inconnue d'infrastructure.

```
interflo-api/        Laravel (dernière version) + Filament
interflo-app/        React Native
interflo-web/        Console animateur (tablette) + console agent questions
interflo-firmware/   Boîtier de plateau — rien d'arbitré, ne pas commencer
```

⚠️ **Avant le scaffolding**, deux questions sont en attente du PO :

1. **Monorepo ou dépôt séparé ?** Recommandation dans `04-architecture.md` §6, non tranchée.
2. **Stylisation React Native** : NativeWind recommandé, non validé.

Si le PO n'a pas répondu, demande-lui plutôt que de choisir.

### Ce qui peut avancer sans blocage

1. Scaffolding — **une fois les deux questions ci-dessus tranchées par le PO**. Elles ne bloquent rien d'autre.
2. Format élimination, mode mesuré désactivé.
3. Appairage : QR, code court, rotation.
4. Vérification du numéro de téléphone.
5. Tableau de bord Filament : tenants, configuration, seuils.

### Ce qu'il ne faut **pas** commencer

- La **couche temps réel** : non conçue. C'est le chantier le plus lourd, il mérite sa décision d'architecture d'abord.
- Le **format buzzer** : dépend de la chaîne de mesure, dont les performances réelles ne sont pas testées.
- Les **migrations** : `07-modele-de-donnees.md` est un squelette, pas un schéma. Trois inconnues l'en empêchent.
- Le **boîtier** : rien n'est arbitré, pas même la connectivité.

---

## Agents disponibles

| Agent | Pour quoi |
|---|---|
| `interflo-architect` | Décisions structurantes. Ne code pas. |
| `interflo-backend` | Laravel, temps réel, Filament. |
| `interflo-mobile` | Application joueur React Native. |
| `interflo-frontend` | Console animateur et console agent questions. |
| `interflo-tester` | Tests et tentatives de casse. |
| `interflo-archivist` | Documentation, décisions, cohérence. |

---

## Numérotation

⚠️ Ce projet **ne consomme aucun numéro d'ADR, de dette ou de voie Tvflo**. Les décisions prises ici vont dans `docs/decisions/` avec une numérotation propre à Interflo.

⚠️ **Conséquence à assumer** (I-42) : les pratiques d'anti-collision multi-voie appliquées à BOS **ne s'appliquent pas ici**. C'est à toi de tenir ton propre découpage.

---

## Rendre compte

Le PO suit le projet depuis un autre fil. Après chaque étape significative, produis un rapport dans `docs/rapports/` avec :

- ce qui a été fait,
- **ce que tu as dû supposer** (liste explicite, ou « aucune »),
- ce qui reste bloqué et par quoi,
- les valeurs paramétrables introduites, avec leur valeur par défaut.

La section « ce que tu as dû supposer » est la plus importante du rapport.
