# ops

Exploitation et déploiement.

> **Vide.** Le dimensionnement dépend de la couche temps réel, qui n'est pas
> conçue (`../docs/04-architecture.md` §5).

## Ce qui est déjà connu

- Le dimensionnement se fait sur le **pic**, pas sur la moyenne : quelques
  secondes de charge massive, plusieurs fois par émission.
- **Base séparée de celle de BOS** (I-35). Partager la base annule
  l'isolation de panne.
- Un **flux par chaîne cliente** doit être ingéré en continu pendant ses
  émissions. Si le flux tombe, la mesure de décalage tombe avec lui.
- Un **mode dégradé en plein direct** est obligatoire. Un produit de direct
  sans mode dégradé n'est pas un produit de direct.
