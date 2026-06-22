FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
