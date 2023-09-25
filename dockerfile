FROM php:7.3.31-cli

RUN docker-php-source extract \
    && docker-php-ext-install pcntl mysqli pdo pdo_mysql \
    && docker-php-source delete