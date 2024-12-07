FROM httpd:2.4-alpine

ENV TZ=${TZ:-UTC}
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

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

RUN pecl install yaml \
    && echo "extension=yaml.so" > /usr/local/etc/php/conf.d/docker-php-ext-yaml.ini \
    && docker-php-ext-enable yaml

RUN pecl install -o -f redis \
    && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod ssl && a2enmod socache_shmcb

COPY ./ssl/mycert.crt /etc/ssl/certs/mycert.crt
COPY ./ssl/mycert.key /etc/ssl/private/mycert.key

# Update default-ssl.conf to point to the certificate and key
RUN sed -i '/SSLCertificateFile.*snakeoil\.pem/c\SSLCertificateFile /etc/ssl/certs/mycert.crt' /etc/apache2/sites-available/default-ssl.conf && \
    sed -i '/SSLCertificateKeyFile.*snakeoil\.key/cSSLCertificateKeyFile /etc/ssl/private/mycert.key\' /etc/apache2/sites-available/default-ssl.conf

# Enable the SSL-enabled site
RUN a2ensite default-ssl

# Update Apache configurations for localhost

RUN echo "ServerName tracker" >> /etc/apache2/apache2.conf

ENV APP_ROOT=/var/www

ENV SERVER_NAME=localhost
ENV DOCUMENT_ROOT=${APP_ROOT}/html
ENV APACHE_LOG_DIR=${APP_ROOT}/docker/apache/logs
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_RUN_USER=www-data

# RUN apk add --update --no-cache tzdata

WORKDIR ${APP_ROOT}

RUN mkdir -p ${DOCUMENT_ROOT}
RUN chown -R ${APACHE_RUN_USER}:${APACHE_RUN_USER} ${DOCUMENT_ROOT}

# RUN ln -s ${APP_ROOT}/html/your_file_root/ ${DOCUMENT_ROOT}

COPY ./httpd.conf /usr/local/apache2/conf/httpd.conf

COPY ./configs/html.conf /etc/apache2/sites-enabled/000-default.conf
COPY ./configs/ssl.conf  /etc/apache2/sites-enabled/default-ssl.conf

# Expose ports
EXPOSE 80
EXPOSE 443


