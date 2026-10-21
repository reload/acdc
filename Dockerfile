FROM composer:2 AS composer
FROM php:8.5-alpine

COPY . /app

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN composer install

ENV APP_URL="http://localhost"
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE="/app/storage/app/acdc.sqlite"
ENV CACHE_STORE=file
ENV QUEUE_CONNECTION=sync
ENV SESSION_DRIVER=file
ENV SESSION_LIFETIME=120
ENV LOG_CHANNEL=errorlog

EXPOSE 80

ENTRYPOINT ["php"]
CMD ["-S", "0.0.0.0:80", "-t", "/app/public"]
