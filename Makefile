.DEFAULT_GOAL := help
.PHONY: help up down restart logs shell install db-migrate db-fixtures db-reset test lint

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Démarrer les conteneurs
	docker compose up -d

down: ## Arrêter les conteneurs
	docker compose down

restart: ## Redémarrer les conteneurs
	docker compose restart

logs: ## Suivre les logs de l'app
	docker compose logs -f app

shell: ## Ouvrir un shell dans le conteneur PHP
	docker compose exec app sh

install: ## Installer les dépendances Composer et importmap
	docker compose exec app composer install
	docker compose exec app php bin/console importmap:install

db-migrate: ## Lancer les migrations Doctrine
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Charger les fixtures
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

db-reset: db-migrate db-fixtures ## Migrer + charger les fixtures

test: ## Lancer les tests PHPUnit
	docker compose exec app php bin/phpunit

lint: ## Vérifier YAML, Twig et le conteneur Symfony
	docker compose exec app php bin/console lint:yaml config/ --no-debug
	docker compose exec app php bin/console lint:twig templates/ --no-debug
	docker compose exec app php bin/console lint:container --no-debug
