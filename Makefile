# Interflo — raccourcis de développement
# Monorepo : API Laravel (interflo-api), app React Native (interflo-app),
# consoles web (interflo-web). Le firmware est hors V1.

.PHONY: help install \
        api-test api-serve api-migrate api-lint \
        web-dev web-build web-lint web-i18n \
        app-start app-ios app-android app-test app-lint \
        test

help: ## Afficher cette aide
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

install: ## Installer les dépendances des trois surfaces
	cd interflo-api && composer install
	cd interflo-web && pnpm install
	cd interflo-app && pnpm install

# --- API (interflo-api) -------------------------------------

api-test: ## Lancer la suite de tests API (Pest)
	cd interflo-api && php artisan test

api-serve: ## Démarrer le serveur de dev API
	cd interflo-api && php artisan serve

api-migrate: ## Appliquer les migrations
	cd interflo-api && php artisan migrate

api-lint: ## Formater / vérifier le style PHP (Pint)
	cd interflo-api && vendor/bin/pint

# --- Consoles web (interflo-web) ----------------------------

web-dev: ## Démarrer le serveur de dev web (Vite)
	cd interflo-web && pnpm dev

web-build: ## Compiler la production web
	cd interflo-web && pnpm build

web-lint: ## Linter web (oxlint)
	cd interflo-web && pnpm lint

web-i18n: ## Vérifier la cohérence des clés i18n fr/en
	cd interflo-web && pnpm check:i18n

# --- Application mobile (interflo-app) ----------------------

app-start: ## Démarrer Metro
	cd interflo-app && pnpm start

app-ios: ## Lancer sur simulateur iOS
	cd interflo-app && pnpm ios

app-android: ## Lancer sur émulateur Android
	cd interflo-app && pnpm android

app-test: ## Lancer les tests mobiles (Jest)
	cd interflo-app && pnpm test

app-lint: ## Linter mobile (ESLint)
	cd interflo-app && pnpm lint

# --- Tout ---------------------------------------------------

test: ## Lancer tous les tests (API + mobile)
	cd interflo-api && php artisan test
	cd interflo-app && pnpm test
