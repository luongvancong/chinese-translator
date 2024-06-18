FROM justinluong/php-8.2.1-fpm

RUN apt-get update && apt-get install -y libpq-dev && apt-get install -y libzip-dev && apt-get install -y zip && apt-get install -y unzip && docker-php-ext-install pdo pdo_pgsql && docker-php-ext-install zip

COPY . /var/www/html

EXPOSE 9000
