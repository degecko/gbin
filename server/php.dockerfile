FROM php:8.2-fpm-alpine

ARG APP_ENV=local

WORKDIR /var/www

ADD server/php/local/www.conf /usr/local/etc/php-fpm.d/
ADD server/php/local/opcache.ini /usr/local/etc/php/conf.d/
ADD server/php/local/php.ini /usr/local/etc/php/

RUN apk --no-cache add $PHPIZE_DEPS openssh rsync libzip-dev libpng-dev

RUN docker-php-ext-install pdo pdo_mysql zip gd pcntl

RUN rm -rf /var/cache/apk/*

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
