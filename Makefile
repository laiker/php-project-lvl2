install:
	composer install

console:
	composer exec --verbose psysh

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src tests

validate:
	composer validate

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src tests

gendiff:
	./bin/gendiff

test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml