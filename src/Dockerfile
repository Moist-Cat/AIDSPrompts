FROM php:apache
RUN a2enmod rewrite
RUN docker-php-ext-install mysqli

COPY . /var/www/html/