FROM composer:2 AS composer
    FROM php:7.2-alpine

COPY . /app

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN composer install

ENV APP_URL="http://localhost"
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE="/app/storage/app/acdc.sqlite"
ENV CACHE_DRIVER=file
ENV QUEUE_CONNECTION=sync
ENV SESSION_DRIVER=file
ENV SESSION_LIFETIME=120
ENV LOG_CHANNEL=errorlog


ENTRYPOINT ["php"]
CMD ["-S", "0.0.0.0:80", "-t", "/app/public"]
