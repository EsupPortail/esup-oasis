FROM dunglas/frankenphp:1.11.3-builder-php8.4 AS builder
COPY --from=caddy:builder /usr/bin/xcaddy /usr/bin/xcaddy

RUN apt-get update && \
    apt-get install -y --no-install-recommends git && \
    rm -rf /var/lib/apt/lists/*

RUN CGO_ENABLED=1 \
    XCADDY_GO_BUILD_FLAGS="-ldflags \"-w -s -extldflags '-Wl,-z,stack-size=0x80000'\" -tags=nobadger,nomysql,nopgx" \
    CGO_CFLAGS=$(php-config --includes) \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    xcaddy build \
    --output /usr/local/bin/frankenphp \
    --with github.com/dunglas/frankenphp=./ \
    --with github.com/dunglas/frankenphp/caddy=./caddy/ \
    --with github.com/dunglas/mercure/caddy \
    --with github.com/dunglas/vulcain/caddy \
    --with github.com/dunglas/caddy-cbrotli \
    --with github.com/darkweak/souin/plugins/caddy \
    --with github.com/darkweak/storages/otter/caddy

FROM dunglas/frankenphp:1.11.3-php8.4 AS frankenphp_upstream
COPY --from=builder --link /usr/local/bin/frankenphp /usr/local/bin/frankenphp

#force non-https, TZ, ...
ENV SERVER_NAME=":80" \
    TZ="Europe/Paris" \
    COMPOSER_ALLOW_SUPERUSER=1 \
    FRANKENPHP_CONFIG="worker ./public/index.php"

RUN apt-get update && \
    apt-get install -y --no-install-recommends tzdata dos2unix && \
    install-php-extensions apcu zip opcache intl ldap soap pdo_pgsql pgsql bcmath oci8 pcntl sockets && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# D’abord les fichiers de dépendances pour maximiser le cache Docker
COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Puis le reste de l’application
COPY ./ ./

RUN php bin/console assets:install # suivant les envs il est nécessaire de forcer l'installation des assets

COPY --link --chmod=755 ./docker/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link --chmod=644 ./docker/php-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY --link --chmod=644 ./docker/php-overrides-prod.ini /usr/local/etc/php/conf.d/99-overrides-prod.ini

RUN dos2unix /usr/local/bin/docker-entrypoint && \
    chmod 755 /usr/local/bin/docker-entrypoint

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/app/docker/Caddyfile"]
