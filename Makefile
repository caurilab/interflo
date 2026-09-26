# Interflo — raccourcis de développement
# ⚠️ Squelette. Les cibles seront complétées au scaffolding.

.PHONY: help
help: ## Afficher cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

.PHONY: docs
docs: ## Lister la documentation
	@ls -1 docs/*.md

.PHONY: setup
setup: ## Installer les dépendances (à compléter au scaffolding)
	@echo "Non implémenté — le scaffolding n'a pas été fait."
	@echo "Voir PROMPT-DEMARRAGE.md."

.PHONY: test
test: ## Lancer les tests (à compléter au scaffolding)
	@echo "Non implémenté — aucun code à tester."
