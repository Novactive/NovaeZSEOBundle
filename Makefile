# === Makefile Helper ===

# Styles
YELLOW=$(shell echo "\033[00;33m")
RED=$(shell echo "\033[00;31m")
RESTORE=$(shell echo "\033[0m")

# Variables
PHP_BIN := php
COMPOSER := composer
CURRENT_DIR := $(shell pwd)
.DEFAULT_GOAL := list
SYMFONY := symfony
EZ_DIR := $(CURRENT_DIR)/ezplatform

.PHONY: list
list:
	@echo "******************************"
	@echo "${YELLOW}Available targets${RESTORE}:"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf " ${YELLOW}%-15s${RESTORE} > %s\n", $$1, $$2}'
	@echo "${RED}==============================${RESTORE}"

.PHONY: installez
installez: ## Install eZ as the local project
	@docker run -d -p 3364:3306 --name ezdbnovaezseocontainer -e MYSQL_ROOT_PASSWORD=ezplatform mariadb:10.3
	@composer create-project ezsystems/ezplatform --prefer-dist --no-progress --no-interaction --no-scripts $(EZ_DIR)
	@curl -o tests/provisioning/wrap.php https://raw.githubusercontent.com/Plopix/symfony-bundle-app-wrapper/master/wrap-bundle.php
	@WRAP_APP_DIR=./ezplatform WRAP_BUNDLE_DIR=./ php tests/provisioning/wrap.php
	@rm tests/provisioning/wrap.php
	@echo "Please set up this way:"
	@echo "\tenv(DATABASE_HOST)     -> 127.0.0.1"
	@echo "\tenv(DATABASE_PORT)     -> 3364"
	@echo "\tenv(DATABASE_PASSWORD) -> ezplatform"
	@cd $(EZ_DIR) && COMPOSER_MEMORY_LIMIT=-1 composer update --lock
	@cd $(EZ_DIR) && bin/console ezplatform:install clean
	@cd $(EZ_DIR) && bin/console cache:clear

.PHONY: serveez
serveez: stopez ## Clear the cache and start the web server
	@cd $(EZ_DIR) && rm -rf var/cache/*
	@docker start ezdbnovaezseocontainer
	@cd $(EZ_DIR) && bin/console cache:clear
	@cd $(EZ_DIR) && $(SYMFONY) local:server:start -d

.PHONY: stopez
stopez: ## Stop the web server if it is running
	@cd $(EZ_DIR) && $(SYMFONY) local:server:stop
	@docker stop ezdbnovaezseocontainer


.PHONY: codeclean
codeclean: ## Coding Standard checks
	$(PHP_BIN) ./vendor/bin/php-cs-fixer fix --config=.cs/.php_cs.php
	$(PHP_BIN) ./vendor/bin/phpcs --standard=.cs/cs_ruleset.xml --extensions=php bundle tests
	$(PHP_BIN) ./vendor/bin/phpmd bundle,tests text .cs/md_ruleset.xml

.PHONY: tests
tests: ## Run the tests
	$(PHP_BIN) ./vendor/bin/phpunit ./tests --exclude-group behat

.PHONY: install
install: ## Install vendors
	$(COMPOSER) install

.PHONY: clean
clean: ## Removes the vendors, and caches
	rm -f .php_cs.cache
	rm -rf vendor
	rm -f composer.lock
