# Chabrin Lease System — Common targets
# Run: make test | make lint | make test-coverage

.PHONY: test lint test-coverage install

# Run all unit and feature tests (single command for "test everything at once")
test:
	php artisan config:clear --ansi && php artisan test

# Run tests in parallel (faster on multi-core)
test-parallel:
	php artisan config:clear --ansi && php artisan test --parallel

# Run tests with coverage (requires PCOV or Xdebug)
test-coverage:
	php artisan config:clear --ansi && php artisan test --coverage

# Lint: Pint + PHPStan
lint:
	composer run lint

# Full install (composer + npm)
install:
	composer install
	cp -n .env.example .env 2>/dev/null || true
	php artisan key:generate
	npm install
