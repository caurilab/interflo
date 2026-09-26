# D-003 — Auth de la console animateur

> **Date** : 2026-09-26
> **Auteur** : agent principal
> **Statut** : 🔮 **PROPOSÉ — à valider par le PO.**
> **Numérotation** : décision propre à Interflo (I-42).

---

## 1. Le problème

L'auth de la console animateur est **PROVISOIRE** : un token opaque par session
(`X-Pilot-Token`), généré à la création de la session, porté en en-tête. Kimi
l'avait signalé comme « le point le plus sensible » ; la session 6 (D-002) a
révélé un second problème concret :

- **Le canal privé Reverb (D-002 §4.2) exige un utilisateur authentifié**
  (session/Sanctum). Le `X-Pilot-Token` n'en fournit pas → le canal pilote a
  été rendu **public** (écart documenté). Pour rendre le canal privé, il faut
  une vraie auth animateur.

## 2. Ce qui borne le choix (déjà tranché)

| Acquis | Conséquence |
|---|---|
| I-37 : « opérateur tenant → auth BOS (la seule qui peut) » | L'animateur n'est PAS l'opérateur tenant. Deux populations distinctes. |
| La console agit en direct, sans rattrapage (docs/10) | Auth **simple et fiable** : l'animateur n'est pas technique, une panne d'auth à l'antenne est grave. |
| Support : tablette (I-41) | Pas de flux d'auth complexe (SSO entreprise…) — un login court. |
| Le téléspectateur ne touche jamais l'API BOS (R-1) | L'auth animateur ne doit PAS exposer BOS au grand public. |

## 3. Options

| # | Mécanisme | Pour | Contre |
|---|---|---|---|
| **A** | **Session (email + mot de passe)** de l'animateur sur la console | Simple ; active nativement le canal privé Reverb (session) ; symétrique du panel Filament | Gestion des comptes animateurs (par chaîne ?) à concevoir |
| **B** | **Sanctum token** (jeton personnel) stocké sur la tablette | Léger ; active le canal privé (Sanctum) ; pas de mot de passe en direct | Rotation/révocation à concevoir ; token volé = accès jusqu'à révocation |
| **C** | **Garder le token de session** (statu quo provisoire) | Zéro travail | Canal pilote reste public ; pas de rotation/révocation ; dette assumée |

## 4. Recommandation

**Option A** (session email/mot de passe), pour trois raisons :

1. Elle **débloque le canal privé** D-002 §4.2 sans custom auth (la session
   suffit) — on ferme l'écart du canal public.
2. Elle est **symétrique du panel Filament** (même mécanisme d'auth déjà en
   place pour l'opérateur tenant) — une seule façon de faire pour les surfaces
   internes.
3. Elle est **simple et fiable à l'antenne** (login court, session persistante
   sur la tablette) — pas de token à recopier avant chaque émission.

⚠️ **Reste à trancher par le PO** : la **gestion des comptes animateurs** —
un compte par chaîne ? par émission ? rattaché au tenant (BOS, I-37) ou propre
à Interflo ? C'est la question qui détermine le mécanisme d'attribution des
comptes, pas le mécanisme d'auth lui-même.

## 5. Conséquences si validé

- Remplacer le middleware `pilot.token` par un middleware de session (comme le
  panel) ; le `pilot_token` de session disparaît.
- Rendre le canal pilote **privé** (`pilot.{sessionId}`, auth session).
- La console animateur passe par un login (page dédiée) au lieu du champ jeton.
- Filament : gestion des comptes animateurs (ou rattachement BOS, à instruire).
