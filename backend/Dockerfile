# Development image: source is bind-mounted by docker-compose.yml.
FROM php:8.4-cli-alpine

RUN apk add --no-cache unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Installed at build time so the first start is fast. Scripts and the autoloader
# need the full source, so the entrypoint's `composer install` runs those.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader

RUN mkdir -p /data

EXPOSE 8000

CMD ["sh", "docker/entrypoint.sh"]
