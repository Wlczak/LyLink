FROM php:8.3-alpine

WORKDIR /var/www/html

COPY ./ /var/www/html

RUN apk update 
    # apk add --no-cache \
    # php php-fpm php-session php-mbstring php-json php-curl php-ctype \
    # php-tokenizer php-phar php-xml php-zip php-opcache php-fileinfo \
    # php-pdo_sqlite

# CMD ["sh", "-c", "php-fpm8 -F & nginx -g 'daemon off;'"]