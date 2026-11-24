# Makefile — local Docker workflow. Run `make help` for the list of targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

COMPOSE := docker compose
# Run as the host user so files written into the mounted project stay editable; -T works without a TTY (CI).
RUN := $(COMPOSE) run --rm -T -u $(shell id -u):$(shell id -g) app
YEAR ?=

.PHONY: help build install run test stan cs fix check sh

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

build: ## Build the PHP 8.5 image
	$(COMPOSE) build

install: build ## Install Composer dependencies
	$(RUN) composer install --no-interaction --no-progress

run: ## Print the vacation days, e.g. `make run YEAR=2025` (default: the current year)
	$(RUN) php bin/console.php calculate:vacation $(YEAR)

test: ## Run the PHPUnit tests
	$(RUN) composer test

stan: ## Run PHPStan (level max)
	$(RUN) composer stan

cs: ## Check code style (PHP CS Fixer, PER-CS, dry run)
	$(RUN) composer cs

fix: ## Fix code style (PHP CS Fixer)
	$(RUN) composer fix

check: cs stan test ## Run all checks: code style, static analysis, tests

sh: ## Open a shell in a container
	$(COMPOSE) run --rm app bash
