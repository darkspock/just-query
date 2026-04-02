.PHONY: up down test test-unit test-integration

up:
	docker compose up -d --wait

down:
	docker compose down

test: up
	vendor/bin/phpunit

test-unit:
	vendor/bin/phpunit --testsuite Unit

test-integration: up
	vendor/bin/phpunit --testsuite Integration
