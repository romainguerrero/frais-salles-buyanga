-include .env
-include .env.local

DOCKER_COMPOSE        ?= docker compose
RUN_WITH_ADMIN        ?= $(DOCKER_COMPOSE) run -T
RUN                   ?= $(RUN_WITH_ADMIN) --user=www-data:www-data

RUN_IN_APP            ?= $(RUN) --rm app /bin/bash -c
RUN_IN_APP_WITH_ADMIN ?= $(RUN_WITH_ADMIN) --rm app /bin/bash -c

define main_title
	@{ \
    set -e ;\
    msg="Make $@";\
    echo "\n\033[34m$$msg" ;\
    for i in $$(seq 1 $${#msg}) ; do printf '=' ; done ;\
    echo "\033[0m\n" ;\
    }
endef

# $(call replace_in_file,filepath,old_string,new_string)
define replace_in_file
    sed s/$(2)/$(3)/g $(1) > tmp && mv tmp $(1)
endef

# $(call uncomment_in_file,filepath,start_string,end_string)
define uncomment_in_file
    sed "/###> $(2)/, /###< $(3)/ s/^#//" "$(1)" > tmp && mv tmp "$(1)"
endef

##@ Project

# Install the project for docker
install:
	${MAKE} vendor

run: ## Run calculation of the current month
	$(RUN_IN_APP) "bin/console calcul:frais --ansi --env=prod"

run-previous: ## Run calculation of previous month
	$(RUN_IN_APP) "bin/console calcul:frais --ansi --previous-month --env=prod"

##@ Interactive Commands

terminal: ## Launch a bash terminal
	$(call main_title,)
	$(RUN_IN_APP) "/bin/bash"

terminal-root: ## Launch a root bash terminal
	$(call main_title,)
	$(RUN_IN_APP_WITH_ADMIN) "/bin/bash"

##@ Utils

git-hooks: ## Configure git to use the hooks versionned folder
	$(call main_title,)
	git config core.hooksPath .git-hooks

acl: ## Set filesystem access rights
	$(call main_title,)
	@echo "Setting permissions..."
	@$(RUN_IN_APP_WITH_ADMIN) "mkdir -p var/log/"
	$(RUN_IN_APP_WITH_ADMIN) "chmod -R 755 var/log/"
	$(RUN_IN_APP_WITH_ADMIN) "chown -R www-data:www-data ./"

##@ Dependencies

.PHONY: vendor
vendor: ## Composer install
vendor: composer.json
	$(call main_title,)
	@if [ "$$APP_ENV" = "prod" ]; then \
		$(RUN_IN_APP) "php -d memory_limit=-1 /usr/bin/composer --ansi install --optimize-autoloader --no-progress --no-suggest --classmap-authoritative --no-interaction" \
	else \
		$(RUN_IN_APP) "php -d memory_limit=-1 /usr/bin/composer --ansi install"; \
	fi

##@ Tests

.PHONY: tests
tests: ## Launch a set of tests
tests: php-cs lint validate-composer-config phpstan

lint: ## Lint Yaml files
	$(call main_title,)
	$(RUN_IN_APP) "bin/console --ansi lint:yaml config *.yml --parse-tags"

validate-composer-config: ## Validate Composer config file
	$(call main_title,)
ifdef DO_NOT_VALIDATE_COMPOSER_CONFIG
		@# Variable defined in the `symfony-stack` repo to ignore this test (the test fails because `flex-require` isn't a standard property of composer)
		@echo 'Variable "DO_NOT_VALIDATE_COMPOSER_CONFIG" is defined so we skip this test'
else
		# Options disponibles pour la commande composer validate :
		# --no-check-all        Do not validate requires for overly strict/loose constraints
		# --no-check-lock       Do not check if lock file is up to date
		# --no-check-publish    Do not check for publish errors
		$(RUN_IN_APP) "php -d memory_limit=-1 /usr/bin/composer --ansi validate --strict --no-check-all"
endif

phpstan: ## Launch phpstan tests
	$(call main_title,)
	$(RUN_IN_APP) "php -d memory_limit=-1 vendor/bin/phpstan --ansi analyse --configuration=phpstan.neon --level=7 src"

php-cs: ## Launch php-cs without fixing
	$(call main_title,)
	$(RUN_IN_APP) "vendor/bin/php-cs-fixer --ansi fix --show-progress=dots --diff --dry-run"

php-cs-fixer: ## Launch php-cs-fixer
	$(call main_title,)
	$(RUN_IN_APP) "vendor/bin/php-cs-fixer --ansi fix --show-progress=dots --diff"

##@ Helpers

.PHONY: help
help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
.DEFAULT_GOAL := help
