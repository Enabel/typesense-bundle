ARG PHP_VERSION
FROM dunglas/frankenphp:1-php${PHP_VERSION}-alpine
RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=ghcr.io/phpstan/phpstan:latest /composer/vendor/phpstan/phpstan/phpstan.phar /usr/local/bin/phpstan
RUN install-php-extensions opcache pcov pdo_sqlite
