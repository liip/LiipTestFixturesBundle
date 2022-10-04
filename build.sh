#!/usr/bin/env bash

docker build ./ --tag ltfb

#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app ltfb sh -c 'rm -rf tests/App*/var/cache/* vendor/ composer.lock ; composer update'

#exit 0

#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app ltfb sh -c 'rm -rf tests/App*/var/cache/* ; composer update ; php vendor/phpunit/phpunit/phpunit --filter testLoadNonexistentFixturesFilesPaths --exclude-group "" --testdox'
docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app ltfb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group "" --testdox'
docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app ltfb sh -c 'rm -rf tests/App*/var/cache/*'
