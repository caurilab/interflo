# D-001 — Direction visuelle : « fun, gaming, mais corporate »

> **Date** : 2026-09-25
> **Auteur** : décision PO en session, consignée par l'agent principal
> **Statut** : ✅ TRANCHÉ (direction). Les tokens ci-dessous sont des points de départ ajustables, pas des valeurs scellées.
> **Numérotation** : première décision propre à Interflo (I-42 — aucun numéro d'ADR Tvflo consommé).

---

## 1. Décision

La direction visuelle d'Interflo est **fun et gaming, mais corporate** :

- **Fun / gaming** : thème sombre, accents saturés issus du logo, dégradés d'énergie, gros éléments tactiles, feedbacks visuels francs (succès/échec), typographie assertive.
- **Corporate** : lisibilité avant tout, hiérarchie sobre, pas d'effets qui nuisent à la fiabilité perçue, consoles de pilotage (animateur, agent questions) sérieuses et sans distraction — une erreur là passe à l'antenne.

## 2. Palette (tokens — dérivée du logo officiel)

| Token | Valeur | Usage |
|---|---|---|
| `brand.primary` | `#d6007d` (magenta) | Actions principales, accents forts |
| `brand.gradient.from` | `#d96034` (orange) | Dégradés d'énergie (boutons de jeu, moments forts) |
| `brand.gradient.to` | `#d6007d` (magenta) | — |
| `brand.cream` | `#fceaac` | Highlights, texte sur accents, éléments « gain » |
| `surface.base` | `#0b0f14` / `#101418` | Fond sombre (web / mobile) |
| `surface.raised` | dérivés sombres | Cartes, zones d'action |

⚠️ Le wordmark du logo est **blanc** : surfaces sombres obligatoires là où il est affiché.

## 3. Animation et effets (amendement PO, 2026-09-25)

Le PO demande que **tout soit bien animé, avec des effets** — sur l'app mobile comme sur les consoles web. C'est la direction : le produit doit être vivant.

Garde-fous non négociables :

- **Console animateur** : aucune animation ne retarde un geste de direct. Les contrôles d'action (ouvrir, fermer, manche suivante, terminer) répondent instantanément au clic (feedback visuel ≤ 150 ms, zéro délai sur le handler). Les *changements d'état* (fenêtre ouverte, compteurs, gagnants) peuvent et doivent être animés — c'est ce qui les rend lisibles à l'antenne.
- **Mobile** : animations sur le thread UI (Reanimated), pas de flous ni d'effets coûteux animés — téléphones d'entrée de gamme et batterie d'abord. Aucun asset lourd téléchargé (R-11).
- **Web** : `prefers-reduced-motion` respecté sur toutes les animations.
- **M-4 inchangé** : l'animation de « faux » reste encourageante — pas de rouge agressif, pas de secousse punitive.

## 4. Ce qui ne change pas

Les invariants d'interface priment sur la direction visuelle :

- **M-1** : un geste accidentel est une réponse — zones tactiles larges et espacées, aucun effet visuel ne peut réduire la zone ou la clarté.
- **M-4** : « faux » reste encourageant — le gaming n'humilie pas.
- **F-3 / F-4** : console animateur en gros boutons, aucune action destructrice par erreur, pas d'animation qui retarde un geste de direct.
- **R-11** : économie de données — pas d'images de fond lourdes sur l'app mobile.
- Public à téléphones d'entrée de gamme : les effets coûteux (flous, ombres portées animées) sont proscrits sur mobile.

## 5. Application

- Mobile (`interflo-app`) : tokens centralisés (`src/config/colors.js` → `tailwind.config.js`), dégradés via composants dédiés, composants de jeu (propositions, feedback) refondus, **transitions et effets Reanimated entre les états de jeu** (§3).
- Web (`interflo-web`) : tokens dans `src/styles/_variables.scss`, partials par composant, **animations CSS** (entrées, transitions d'état, compteurs — §3).
- Filament (`interflo-api`) : couleur primaire `#d6007d`, thème sombre par défaut (déjà appliqué pour le logo).
