FROM dunglas/frankenphp:php8.4-alpine AS base

RUN install-php-extensions \
    pdo_mysql \
    intl \
    opcache \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev

RUN install-php-extensions xdebug
