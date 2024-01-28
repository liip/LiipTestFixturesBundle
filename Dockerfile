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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN wget -O /usr/local/bin/composer https://getcomposer.org/composer-2.phar && chmod +x /usr/local/bin/composer
