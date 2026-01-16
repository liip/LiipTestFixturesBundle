# Contributing
This is for people who want to contribute to the project.

## How to get started

First of all, you need to fork the repository and clone it locally.
After that, we've prepared a Docker setup to help you get started quickly.

Install `docker` and `docker compose` (or the older version `docker-compose`) on your machine, then run the following commands:

> [!TIP]
> If you use a Mac with ARM architecture (M1, M2, etc.), you may need to [modify the `docker-compose.yml` file](https://github.com/bitnami/containers/issues/40947#issuecomment-1927013148). Please don’t commit the changes to this file, keep them on your local environment.

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
docker run --rm -it --volume .:/app --workdir /app jakzal/phpqa:1.116.2-php8.4-alpine php-cs-fixer --config=./.qa/.php-cs-fixer.dist.php --diff --no-interaction --ansi fix --show-progress none
```

## Run PHPStan

Use it through Docker:

```bash
docker run --rm -it --volume .:/app --workdir /app jakzal/phpqa:1.116.2-php8.4-alpine phpstan analyse --configuration ./.qa/phpstan.neon --no-progress
```
