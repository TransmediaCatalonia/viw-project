FROM php:8.2.19-fpm

RUN apt update && \
    apt install -y git libzip-dev

RUN docker-php-ext-configure zip && docker-php-ext-install -j$(nproc) zip

COPY . /var/www/symfony/

WORKDIR /var/www/symfony/

COPY --from=composer:2.3.7 /usr/bin/composer /usr/bin/composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install symfony-cli
