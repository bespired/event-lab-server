FROM php:8.3.4-apache

# Install PHP extensions and other dependencies
RUN apt-get update && apt-get upgrade -y

# Install PHP extensions and other dependencies
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y libzip-dev libicu-dev libyaml-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    curl unzip nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo_mysql zip intl gd exif opcache \
    && a2enmod rewrite

RUN a2enmod ssl && a2enmod rewrite

RUN pecl install yaml \
    && echo "extension=yaml.so" > /usr/local/etc/php/conf.d/docker-php-ext-yaml.ini \
    && docker-php-ext-enable yaml

RUN pecl install -o -f redis \
    && docker-php-ext-enable redis


RUN mkdir -p /etc/apache2/ssl
COPY ./ssl/*.pem /etc/apache2/ssl/
COPY ./ssl/*.crt /etc/apache2/ssl/
COPY ./ssl/*.key /etc/apache2/ssl/

COPY ./profile.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
EXPOSE 443