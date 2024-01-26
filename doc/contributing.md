# Contributing
This is for people who want to contribute to the project.

## How to get started

First of all, you need to fork the repository and clone it locally.
After that, we've prepared a docker setup to help you get started quickly.

Install docker and docker-compose on your machine, then run the following commands:

```bash
docker-compose up -d
docker-compose exec php-fpm bash
```

When you are on the container, you can install the dependencies with composer:

```bash
composer install
```

Now you can execute the tests with the following command:

```bash
./vendor/bin/phpunit
```