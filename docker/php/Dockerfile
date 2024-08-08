FROM php:8.3-cli

RUN apt-get update
RUN apt-get install -y libzip-dev zip git

RUN pear update-channels \
    && pecl update-channels \
    && pecl install xdebug

RUN docker-php-ext-enable xdebug

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
