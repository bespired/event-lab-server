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

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "ServerName bespired.com" >> /etc/apache2/apache2.conf

EXPOSE 80
EXPOSE 443

COPY ./ratchet.entrypoint /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

CMD ["docker-entrypoint"]



