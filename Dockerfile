FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev \
 && docker-php-ext-install pdo_mysql mysqli mbstring \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY docker/apache-security.conf /etc/apache2/conf-available/studenthub-security.conf
RUN a2enconf studenthub-security
COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-studenthub.ini

COPY . /var/www/html/
# Code stays owned by root and is not writable by the web server.
# config/ can be read by www-data, but cannot be modified by it.
RUN chown -R root:root /var/www/html \
 && find /var/www/html -type d -exec chmod 0755 {} + \
 && find /var/www/html -type f -exec chmod 0644 {} + \
 && chgrp www-data /var/www/html/config \
 && chmod 0750 /var/www/html/config
