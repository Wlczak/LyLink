FROM composer:2.9 AS composer

WORKDIR /build/composer

COPY ./ /build/composer/

RUN rm -r ./javascript

RUN composer install

FROM node:24-alpine AS npm

WORKDIR /build/npm

COPY ./javascript/ /build/npm/

RUN npm install

RUN npm run build

FROM nginx:1.29-alpine-slim AS lylink-nginx 

COPY ./public_html /var/www/html/public_html

COPY ./phpdocker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

COPY --from=npm /build/npm/dist /var/www/html/public_html/dist

FROM php:8.5-fpm-alpine AS lylink

WORKDIR /var/www/html

# RUN apk update && apk upgrade
# apk add --no-cache \
# php php-fpm php-session php-mbstring php-json php-curl php-ctype \
# php-tokenizer php-phar php-xml php-zip php-opcache php-fileinfo \
# php-pdo_sqlite

COPY --from=composer /build/composer /var/www/html

COPY ./phpdocker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./phpdocker/php-fpm/php-ini-overrides.ini /etc/php/8.5/fpm/conf.d/99-overrides.ini
COPY ./phpdocker/php-fpm/php-ini-overrides.ini /etc/php/8.5/cli/conf.d/99-overrides.ini

# COPY --chown=0:0 ./phpdocker/php-fpm/php-fpm.conf /usr/local/etc/php-fpm.conf

CMD ["php-fpm", "-R"]