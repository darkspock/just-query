.PHONY: up down test test-unit test-integration test-mysql test-pgsql

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

test-mysql: up
	vendor/bin/phpunit tests/Integration/Mysql

test-pgsql: up
	vendor/bin/phpunit tests/Integration/Pgsql
