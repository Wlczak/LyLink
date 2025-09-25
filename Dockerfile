FROM composer:latest AS composer

WORKDIR /build/composer

COPY ./ /build/composer/

RUN rm -r ./javascript

RUN composer install

FROM node:latest AS npm

WORKDIR /build/npm

COPY ./javascript/ /build/npm/

RUN npm install

RUN npm run build

FROM php:8.4-alpine

WORKDIR /var/www/html

# RUN apk update && apk upgrade
# apk add --no-cache \
# php php-fpm php-session php-mbstring php-json php-curl php-ctype \
# php-tokenizer php-phar php-xml php-zip php-opcache php-fileinfo \
# php-pdo_sqlite

COPY --from=composer /build/composer /var/www/html

COPY --from=npm /build/npm/dist /var/www/html/public_html/dist

# CMD ["sh", "-c", "php-fpm8 -F & nginx -g 'daemon off;'"]