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
docker-compose exec php-fpm composer install
```

Now you can execute the tests with the following command:

```bash
docker-compose exec php-fpm ./vendor/bin/phpunit --exclude-group ""
```
