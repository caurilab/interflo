# interflo-api

API Laravel + tableau de bord Filament + couche temps réel.

> **Scaffolding fait** (Laravel + Filament + Pest + Pint). Aucune logique métier, aucune migration métier, aucune route de jeu.

## Avant de commencer

- `../docs/04-architecture.md` — principes, et ce qui n'est pas conçu
- `../docs/09-contrat-api.md` — règles auxquelles tout contrat devra se conformer
- `../docs/07-modele-de-donnees.md` — squelette d'entités, **pas un schéma**

## Deux invariants qui ne se négocient pas

- **La bonne réponse ne sort jamais du serveur.** Le verdict juste/faux se calcule serveur.
- **Aucun appel synchrone vers BOS pendant une fenêtre de jeu.** Le contexte est copié avant.

## Ne pas commencer

- Les **migrations métier** : trois inconnues bloquent le schéma (`07-modele-de-donnees.md` est un squelette, pas un schéma). Seules les tables du framework (users, cache, jobs) existent.
- La **couche temps réel** : non conçue, mérite sa décision d'architecture d'abord.

## Installation (développement)

```bash
composer install
cp .env.example .env        # si .env absent
php artisan key:generate    # si APP_KEY vide
touch database/database.sqlite
php artisan migrate         # tables du framework uniquement (users, cache, jobs)
npm install && npm run build
```

La base de développement est **SQLite**. PostgreSQL est une option envisagée pour la
production, **non arbitrée** pour Interflo — voir les commentaires dans `.env.example`.

## Utilisateur d'administration Filament

Le tableau de bord est servi sous `/admin`. Pour créer un compte administrateur :

```bash
php artisan make:filament-user
```

(commande interactive — nom, e-mail, mot de passe demandés au prompt).

Un compte de développement local a été créé au scaffolding :
`admin@interflo.local` / `interflo-admin-dev` — **développement local uniquement,
jamais en production**. **Aucune donnée métier n'est seedée.**

## Qualité

```bash
php artisan test --compact   # suite Pest
vendor/bin/pint              # style de code
```

## Réglages du jeu

Toutes les valeurs de réglage du jeu vivent dans `config/interflo.php`, pilotées par
l'environnement (préfixe `INTERFLO_`). **Aucune valeur numérique n'est validée** : chaque
entrée est un point de départ paramétrable, sauf les trois valeurs scellées par décision PO
(4 propositions, 5 manches, 1/3/5 gagnants). Ne jamais écrire ces valeurs en dur dans le code.

## Documentation Laravel

Le cadriciel est [Laravel](https://laravel.com/docs) (licence MIT). Documentation Filament :
[filamentphp.com/docs](https://filamentphp.com/docs).
