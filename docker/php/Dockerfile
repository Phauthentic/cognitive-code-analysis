FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y libzip-dev zip git wget gpg \
    && rm -rf /var/lib/apt/lists/*

RUN pear update-channels \
    && pecl update-channels \
    && pecl install xdebug

RUN docker-php-ext-enable xdebug

RUN echo "xdebug.mode=coverage,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN git config --system --add safe.directory /app

RUN useradd --create-home --no-log-init --shell /bin/bash php \
    && mkdir -p /app \
    && chown -R php:php /app

WORKDIR /app

USER php
