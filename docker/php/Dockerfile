FROM php:8.3-cli

RUN apt-get update
RUN apt-get install -y libzip-dev zip git wget gpg

RUN pear update-channels \
    && pecl update-channels \
    && pecl install xdebug

RUN docker-php-ext-enable xdebug

RUN echo "xdebug.mode=coverage,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN git config --global --add safe.directory /app

# Create a new user
RUN useradd -m php

# Set the new user as the default user for the container
USER php
