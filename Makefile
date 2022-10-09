help:  ## Print the help documentation
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: clean
clean:  ## clean
	rm -rf vendor

.PHONY: composer
composer:  ## Composer Install
	composer install --prefer-dist --no-progress --no-suggest

.PHONY: lint
lint:  ## PHP Lint
	vendor/squizlabs/php_codesniffer/bin/phpcs

.PHONY: lint-fix
lint-fix:  ## PHP Lint Fix
	vendor/squizlabs/php_codesniffer/bin/phpcbf
