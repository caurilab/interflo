# D-002 — Architecture temps réel

> **Date** : 2026-09-26
> **Auteur** : agent principal, en réponse au « Go » du PO
> **Statut** : 🔮 **PROPOSÉ — à valider par le PO.** Tant que ce document n'est pas validé, le polling provisoire de la session 4 reste le transport en vigueur.
> **Numérotation** : décision propre à Interflo (I-42). Aucun numéro d'ADR Tvflo consommé.
> **⚠️ Aucune valeur numérique de ce document n'est validée** : les ordres de grandeur sont des hypothèses de dimensionnement à corriger par test de charge.

---

## 1. Le problème

Le PO a posé la question fondatrice : « imaginons cent mille personnes qui buzzent en même temps » (`INTERFLO_PRODUCT.md` §11).

Le profil de charge d'Interflo n'a rien à voir avec une application de gestion :

- **Pic concentré** : l'essentiel des réponses arrive en quelques secondes après l'ouverture d'une fenêtre, plusieurs fois par émission. On dimensionne sur le pic, pas sur la moyenne.
- **Deux profils de jeu** (`04-architecture.md` §5.1) :
  - *Buzzer* : ordonner des horodatages à faible latence, produire un gagnant unique.
  - *Élimination* : compter des bonnes réponses sur une population qui fond à chaque manche.
- **Un direct ne se rattrape pas** : le comportement en mode dégradé fait partie de l'architecture, pas du plan de reprise après sinistre.

## 2. Ce qui borne le problème (déjà tranché)

Les décisions produit prises au cadrage rendent le problème **traitable** — c'est le point de départ de cette proposition :

| Acquis | Conséquence pour l'architecture |
|---|---|
| Choix multiple à 4 propositions (I-4) | Une réponse = un entier. Pas d'audio, pas de texte libre. |
| Verdict juste/faux calculé serveur, **retour d'un bit** (I-29, R-5) | **Pas de fan-out individuel des verdicts.** Le verdict voyage dans la réponse HTTP de la soumission elle-même — voir §4.1, c'est le point qui simplifie tout. |
| La bonne réponse ne sort jamais du serveur (CA-07) | La question servie aux joueurs ne contient aucune donnée sensible — la charge utile broadcast est **identique pour tous** et cacheable. |
| Fenêtre personnelle, pas absolue (EX-20) | Le pic est **étalé par le décalage de diffusion** : les joueurs ne voient pas la question au même instant. Le pire cas théorique (100 000 requêtes la même seconde) n'arrive pas en pratique. |
| Refus serveur hors fenêtre (I-2) | L'admission est un état serveur — pas de traitement inutile hors fenêtre. |
| Horodatage au geste, borné serveur (I-6, I-30) | Pas de dépendance à l'ordre d'arrivée réseau pour l'équité. |

**Le seul fan-out de masse qui reste** : pousser « la fenêtre est ouverte, voici la question » à N joueurs d'une population — une charge utile unique de quelques centaines d'octets, identique pour tous. C'est le problème le plus simple du temps réel (broadcast d'un message immutable), et les compteurs studio/console (EX-33).

## 3. Hypothèses de dimensionnement (non validées — à corriger par test)

| Grandeur | Hypothèse | Origine |
|---|---|---|
| Joueurs simultanés (cible V1) | 100 000 | Question du PO, §11 cadrage |
| Fenêtre de réponse | 10 s (personnelle) + enveloppe serveur | `config/interflo.php`, non validé |
| Réponses au pic | 100 000 sur ~15–25 s, soit ~4 000–7 000 req/s en crête | Déduit de l'étalement EX-20 |
| Charge utile « question » | ~300–500 octets | Mesuré sur le contrat session 4 |
| Connexions WebSocket simultanées | jusqu'à 100 000 | Tous les joueurs attachés |

## 4. La décision

### 4.1 Transport joueur : push Reverb pour la distribution, HTTP POST pour les réponses

**Ce qui est poussé (WebSocket)** : l'ouverture de fenêtre et la question (I-3 : l'ouverture est signifiée par l'application), les transitions d'état de la session. Un seul channel public par population et par session — la charge utile est identique pour tous (CA-07 le permet, §2).

**Ce qui reste en requête-réponse HTTP** : la **soumission de réponse**. Raison décisive : le verdict est synchrone (I-29) — la réponse HTTP *est* le retour individuel. Faire voyager la soumission en WebSocket n'achèterait rien et couplerait la correction au transport temps réel. Le POST est idempotent côté serveur (déjà : `ALREADY_ANSWERED`).

**Fallback** : le **polling construit en session 4 n'est pas jeté — il devient le transport dégradé documenté** (§5). Un client dont le WebSocket tombe repasse en polling sans perdre sa session. C'est la première brique du mode dégradé, déjà écrite.

**Choix retenu : Laravel Reverb** (serveur WebSocket first-party, protocole Pusher, clients Echo). Justifications :

1. Même stack, même équipe (I-41 : Laravel) — broadcasting natif du framework, pas de service tiers entre Interflo et ses joueurs (cohérent avec I-35 : pas de dépendance externe dans le chemin critique du direct).
2. Scaling horizontal éprouvé : Redis pub/sub entre nœuds Reverb derrière un load balancer à sessions persistantes (sticky).
3. Point de vigilance vérifié : au-delà de ~1 000 connexions par process, l'event loop par défaut (`stream_select`, limite de descripteurs) ne suffit pas — **ext-uv obligatoire** en production. [Laravel — Reverb, Running in Production](https://laravel.com/framework/docs/reverb "citation")
4. Coût : self-hosté = coût fixe serveur, pas de facturation par message/connexion (à 100 000 connexions quotidiennes, les services managés type Pusher/Ably deviennent un poste récurrent significatif — ordre de grandeur non évalué, à chiffrer si l'option managée est préférée).

**Alternative documentée** : Laravel Cloud propose des clusters WebSocket managés propulsés par Reverb (developer preview au 2026-07). [Laravel Cloud — Changelog](https://cloud.laravel.com/docs/changelog "citation") — si l'exploitation ne veut pas gérer le clustering, c'est la porte de sortie sans changement de code.

### 4.2 Transport pilotage : channel privé

Console animateur et console agent questions : channels Reverb **privés** (authentifiés). Les compteurs (participations, survivants — EX-33) sont poussés au changement de valeur, pas pollés. Le polling 2 s actuel de la console devient son propre fallback (F-2 inchangé : la perte de lien reste visible immédiatement).

### 4.3 Persistance au pic : Redis autoritaire pendant la fenêtre, PostgreSQL après

C'est la réponse à « ne pas modéliser la réponse comme une simple ligne insérée » (`07-modele-de-donnees.md` §5) :

- **Pendant la fenêtre** : l'état de jeu (fenêtre ouverte, qui a répondu, verdicts, compteurs) vit dans **Redis** — écritures atomiques en mémoire (`SETNX` par joueur et manche pour l'unicité, `INCR` pour les compteurs). PostgreSQL n'est **pas** dans le chemin critique du pic.
- **Après la fermeture de la fenêtre** : un job (queue Laravel + Horizon) **flush par lots** les réponses de la manche vers PostgreSQL, qui reste le **système d'enregistrement** (résultats consultables par BOS après l'émission — I-36).
- **Buzzer (quand il viendra)** : classement au temps de réaction (I-20) = **sorted set Redis** par tour (`ZADD` sur le temps de réaction corrigé du décalage) — ordonner 100 000 horodatages est alors une opération native, pas un tri applicatif.
- **Le droit de jouer reste dans les deux mondes cohérent** : le refus hors fenêtre (I-2) et le plancher anti-automatisation (I-30) sont évalués au moment de la soumission, sur l'état Redis — les règles déjà testées en session 4 sont portées telles quelles, seule leur source d'état change.

Conséquence bienveillante sur le modèle de données : les tables de jeu PROVISOIRES de la session 4 restent **exactement ce qu'il faut** pour le stockage refroidi (résultats, historique) — la décision « où vivent les réponses chaudes » étant « pas dans PostgreSQL », l'inconnue n°1 de `07-modele-de-donnees.md` §1 se referme partiellement.

### 4.4 Runtime HTTP : Octane

Le pic de soumissions (~4 000–7 000 req/s en crête, §3) dépasse le confort d'un PHP-FPM classique. **Laravel Octane (FrankenPHP)** : application résidente en mémoire, amorcée une fois. Contrepartie connue : vigilance sur les fuites d'état entre requêtes (pas d'état statique) — à intégrer à la revue de code.

### 4.5 Vue d'ensemble

```
Joueurs (RN)                Pilotage (consoles web)
   │ WS (channel population)        │ WS (channel privé)
   │ + POST réponses                │
   ▼                                ▼
        Reverb (ext-uv, ×N nœuds, Redis pub/sub, sticky LB)
                ▲
                │ broadcast
   API Laravel (Octane) ──► Redis (état chaud : fenêtres, réponses, compteurs, sorted sets buzzer)
        │                         │
        │                         └── flush par lots (Horizon) ──► PostgreSQL (système d'enregistrement)
        └── Filament admin (hors chemin critique)
   
BOS : lecture des résultats APRÈS l'émission via l'API (I-36). Jamais dans ce diagramme pendant le direct.
```

## 5. Mode dégradé — partie intégrante de la décision

« Un produit de direct sans mode dégradé n'est pas un produit de direct » (`04-architecture.md` §5.1). Quatre niveaux, chacun avec un comportement **écrit** :

| Niveau | Déclencheur | Comportement |
|---|---|---|
| **D-1 Transport dégradé** | WebSocket injoignable côté client | Bascule polling (déjà construit, session 4). La fenêtre étant personnelle (EX-20), le joueur n'est pas pénalisé : son enveloppe court depuis la réception effective. |
| **D-2 Saturation Reverb** | Connexions refusées au-delà de la capacité | Les nouveaux venus basculent en polling. La console animateur affiche le nombre de connexions WS actives vs capacité (indicateur à ajouter au contrat). |
| **D-3 Panne Redis** | Redis injoignable pendant une fenêtre | ⚠️ Le point le plus dur. Décision : **on ne finit pas une fenêtre en mode démuni** — la fenêtre courante est clôturée proprement avec les réponses déjà encaissées (elles sont en mémoire Redis… perdues si Redis est mort : voir réplication ci-dessous), l'animateur est notifié en rouge (F-2), la manche est **rejouée** (c'est un direct : l'animateur meuble et relance — la conduite d'émission absorbe). Parade structurelle : Redis avec réplication + failover (replica promu), persistence AOF sur le master. |
| **D-4 Saturation HTTP** | Latence soumission > seuil | File d'admission bornée : réponses acceptées jusqu'à saturation, au-delà **rejet propre** avec message joueur « réessaie » (réponse non comptée, joueur non verrouillé — jamais d'élimination par défaut technique). Seuil paramétrable (non validé). |

**Règle d'or** : un joueur ne peut **jamais être éliminé par une défaillance d'infrastructure**. Verrouillage (EX-32) uniquement sur verdict réel ou fenêtre personnelle réellement expirée en régime nominal.

## 6. Conséquences

- **Modèle de données** (`07`) : inconnue n°1 partiellement refermée (§4.3). Restent : recouvrement Voxflo (n°2) et liste de ce qui est copié de BOS (n°3).
- **Contrat d'API** (`09`) : peut maintenant être écrit — channels, événements, et les routes déjà livrées. Prochaine itération documentaire.
- **Ops** : Redis obligatoire (répliqué), Octane, ext-uv, Supervisor/Horizon, LB sticky pour WS. `docker-compose.yml` du dépôt à compléter.
- **Coûts** : self-hosté Reverb = serveurs fixes ; option managée Laravel Cloud à chiffrer si préférée.
- **Buzzer** : ce document couvre son profil de charge (sorted sets, mesure) ; son **activation** reste bloquée par le test terrain ACRCloud (`etat-du-projet.md` §2.2), pas par l'architecture.

## 7. Plan de validation (avant toute prétention de tenue de charge)

1. **Test de charge** par paliers — 1 000 / 10 000 / 100 000 clients simulés (k6 ou équivalent, clients WebSocket + soumissions au pic). Critères de passage chiffrés à fixer à cette occasion (latence de distribution, taux de soumissions acceptées, exactitude des compteurs).
2. **Test de panne en plein direct** simulé : kill Redis, kill Reverb, kill API — vérifier chaque niveau de §5.
3. **Revue de la conduite d'émission** : les niveaux D-1 à D-4 écrits dans le document remis aux chaînes (au même titre que l'enveloppe à meubler, §4.4 cadrage).

**Aucun chiffre de ce document n'est une promesse** tant que le palier 100 000 n'a pas été exécuté.

---

## Rapport de sortie (charte architecte)

**Proposition** : Reverb (push joueur/pilotage) + HTTP POST synchrone pour les réponses + Redis autoritaire pendant la fenêtre avec flush PostgreSQL + Octane, avec le polling de session 4 promu transport dégradé et un mode dégradé à quatre niveaux écrit.

**Ce qui la fonde** : I-2, I-3, I-4, I-6, I-20, I-29, I-30, I-35, I-36, I-41 ; EX-20, EX-33 ; CA-07 ; R-5, R-11. Le point décisif est I-29 : le verdict à un bit, synchrone, supprime le fan-out individuel — le seul broadcast de masse est une charge utile unique identique pour tous.

**Ce qui reste ouvert** : dimensionnement réel (test de charge non fait) ; hébergement (self-host vs Laravel Cloud) ; taille du cluster Redis et stratégie de failover exacte ; métriques de supervision embarquées ; recouvrement Voxflo (inconnue n°2, inchangée) ; liste des données copiées de BOS (inconnue n°3, inchangée).

**Hypothèses faites** : les ordres de grandeur du §3 (100 000 joueurs, 4 000–7 000 req/s en crête, ~400 octets par question) ; l'étalement naturel du pic par le décalage de diffusion (EX-20) ; la capacité de l'équipe à exploiter Redis + Octane (ou à choisir l'option managée).

**Ce que je n'ai pas pu vérifier** : les performances réelles de Reverb à 100 000 connexions sur l'infrastructure cible (aucun benchmark public fiable à cette échelle — d'où le plan de validation §7) ; l'état de maturité exact de l'offre WebSocket managée Laravel Cloud (developer preview au moment de l'écriture) ; aucune lecture du dépôt Tvflo (frontière BOS supposée conforme à I-36).
