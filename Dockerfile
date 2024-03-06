FROM php:8.3-fpm
WORKDIR "/application"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
    libpq-dev \
    libsqlite3-dev \
    unzip \
    wget \
    default-mysql-client \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && wget https://fastdl.mongodb.org/tools/db/mongodb-database-tools-debian92-x86_64-100.3.1.deb \
    && apt install ./mongodb-database-tools-*.deb \
    && apt-get clean \
    && rm -rf mongodb-database-tools-*.deb /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
