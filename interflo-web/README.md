# interflo-web

Console animateur (tablette) et console agent questions.

> **Statut : SQUELETTE VISUEL.** Scaffolding fait — aucune donnée réelle,
> aucun appel réseau. Le contrat d'API n'existe pas encore
> (`../docs/10-contrat-api-console-animateur.md` : « AUCUNE ROUTE N'EST DÉFINIE ») ;
> chaque branchement futur est marqué par un `TODO` dans le code.

## Avant de modifier ces surfaces

- `../docs/INTERFLO_PRODUCT.md` — source d'autorité (§5 déroulé, §9 production des questions, §15.3 surfaces)
- `../docs/10-contrat-api-console-animateur.md`
- `../docs/06-ux-ui.md` §3 et §5

## Ce n'est pas Filament

Filament sert l'administration. La console de direct a besoin d'un écran plein,
de gros boutons et d'un chemin d'accès court en cas de panne à l'antenne.

## Stack

Vite + React + TypeScript (strict) + Tailwind CSS + React Router.

## Routes

| Route | Surface |
|---|---|
| `/` | Sélection de la console |
| `/animateur` | Console animateur (tablette) |
| `/agent-questions` | Console agent questions (validation humaine, I-33) |

## Développement

```bash
pnpm install
pnpm run dev     # serveur de développement
pnpm run build   # vérifie les types (tsc) puis build de production
```

Gestionnaire de paquets : **pnpm** (champ `packageManager` : `pnpm@11.5.2`, via Corepack).

## Règles propres à ces surfaces

- **F-1** : l'état affiché reflète l'état serveur, jamais un état local optimiste.
  Tant que le contrat n'existe pas, tout est explicitement « NON CONNECTÉ ».
- **F-2** : la perte de lien est visible en permanence (placeholder « hors ligne »).
- **F-3 / F-4** : gros boutons ; toute action à effet passe par une confirmation.
- **F-5** : l'enveloppe à meubler est lue depuis `src/config/consoleConfig.ts`
  (valeur non validée, ordre de grandeur 20-25 s) — jamais en dur.
- **F-6 / I-10** : jamais de liste des répondants — gagnant du tour + compteurs.
- **I-33** : rien ne part à l'antenne sans validation humaine explicite.
