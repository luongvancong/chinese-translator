FROM php:8.2.1-apache

RUN apt-get update && apt-get install -y libpq-dev && apt-get install zip && apt-get install unzip && docker-php-ext-install pdo pdo_pgsql && docker-php-ext-install zip

COPY . /var/www/html

COPY ./vhost.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

RUN a2enmod rewrite && service apache2 restart
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer
RUN apt-get install -y git
