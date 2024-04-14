# Contributing
This is for people who want to contribute to the project.

## How to get started

First of all, you need to fork the repository and clone it locally.
After that, we've prepared a docker setup to help you get started quickly.

Install docker and docker-compose on your machine, then run the following commands:

```bash
docker-compose up --detach
```

Install the dependencies with composer:

```bash
docker-compose exec php composer install
```

Install the lowest dependencies with composer:

```bash
docker-compose exec php composer update --prefer-lowest
```

Now you can execute the tests with the following command:

```bash
docker-compose exec php ./vendor/bin/phpunit --process-isolation
```

If one test fails, run it without the `--process-isolation` option

```bash
docker-compose exec php ./vendor/bin/phpunit tests/Test/ConfigMongodbTest.php
```

You can also use the `--filter` option to run tests matching a class and name:

```bash
docker-compose exec php ./vendor/bin/phpunit --process-isolation --filter=ConfigSqliteTest::testAppendFixtures
```

You can also use the `--filter` option to run tests matching a name:

```bash
docker-compose exec php ./vendor/bin/phpunit --process-isolation --filter=testAppendFixtures
```

## Delete the cache

If you change the version of PHP or dependencies, the caches may cause issues, they can be deleted:

```bash
docker-compose exec php bash -c "rm -rf tests/App*/var/cache/*"
```

## Apply changes suggested by PHP-CS-Fixer

Use it through Docker:

```bash
docker run --rm -it --volume .:/app --workdir /app jakzal/phpqa:1.96.3-php8.2-alpine php-cs-fixer --diff --no-interaction --ansi fix --show-progress none
```
