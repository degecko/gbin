FROM php:8.2-fpm-alpine

ARG APP_ENV=local

WORKDIR /var/www

ADD server/php/${APP_ENV}/www.conf /usr/local/etc/php-fpm.d/
ADD server/php/${APP_ENV}/opcache.ini /usr/local/etc/php/conf.d/
ADD server/php/${APP_ENV}/php.ini /usr/local/etc/php/

RUN apk --no-cache add $PHPIZE_DEPS openssh rsync libzip-dev libpng-dev ghostscript git

RUN docker-php-ext-install pdo pdo_mysql zip gd pcntl
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install openswoole && docker-php-ext-enable openswoole
RUN docker-php-ext-install opcache

RUN apk --no-cache add libwebp libwebp-tools libavif libavif-apps jpegoptim pngquant

RUN apk --no-cache add nodejs npm

RUN rm -rf /var/cache/apk/*

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
