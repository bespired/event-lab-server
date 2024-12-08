FROM php:8.3.4-apache

RUN a2enmod ssl && a2enmod rewrite

RUN apt-get update && apt-get upgrade -y \
    curl unzip nano

RUN mkdir -p /etc/apache2/ssl
COPY ./ssl/*.pem /etc/apache2/ssl/
COPY ./ssl/*.crt /etc/apache2/ssl/
COPY ./ssl/*.key /etc/apache2/ssl/

COPY ./admin.conf /etc/apache2/sites-available/000-default.conf

RUN echo "ServerName bespired.com" >> /etc/apache2/apache2.conf

EXPOSE 80
EXPOSE 443