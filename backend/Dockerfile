FROM dunglas/frankenphp:builder-php8.3 AS builder
COPY --from=caddy:builder /usr/bin/xcaddy /usr/bin/xcaddy

RUN apt-get update && apt-get install --no-install-recommends -y git

ENV CGO_ENABLED=1 XCADDY_SETCAP=1 XCADDY_GO_BUILD_FLAGS="-ldflags \"-w -s -extldflags '-Wl,-z,stack-size=0x80000'\""
RUN xcaddy build \
    --output /usr/local/bin/frankenphp \
    --with github.com/dunglas/frankenphp=./ \
    --with github.com/dunglas/frankenphp/caddy=./caddy/ \
    --with github.com/dunglas/mercure/caddy \
    --with github.com/dunglas/vulcain/caddy \
    --with github.com/dunglas/caddy-cbrotli \
    --with github.com/darkweak/souin/plugins/caddy \
    --with github.com/darkweak/storages/otter/caddy

FROM dunglas/frankenphp:php8.3 AS frankenphp_upstream
COPY --from=builder --link /usr/local/bin/frankenphp /usr/local/bin/frankenphp

#force non-https
ENV SERVER_NAME=":80"

RUN apt-get update &&  \
    apt upgrade -y && \
    apt-get install -y libssl-dev libldap2-dev libsodium-dev zlib1g-dev curl libcurl4-openssl-dev git unzip libxml2-dev libpq-dev  \
                libzip-dev libaio1 dos2unix libldap-common gnupg2 libxml2 libxml2-dev \
                procps acl tzdata

ENV TZ="Europe/Paris"

RUN install-php-extensions apcu zip opcache intl ldap soap pdo_pgsql pgsql bcmath oci8 pcntl sockets

# Récupération du dossier application
COPY ./ /app
WORKDIR /app

# composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer \
    && /usr/bin/composer install --ignore-platform-reqs --optimize-autoloader

# droits du dossier var de symfony
RUN chmod -R a+rw ./var/

# copie de l'entrypoint au bon endroit
COPY --link --chmod=755 ./docker/entrypoint.sh /usr/local/bin/docker-entrypoint
RUN dos2unix /usr/local/bin/docker-entrypoint && chmod 755 /usr/local/bin/docker-entrypoint

# params php additionnels
COPY --link --chmod=755 ./docker/php-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini
COPY --link --chmod=755 ./docker/php-overrides-prod.ini /usr/local/etc/php/conf.d/99-overrides-prod.ini

ENV FRANKENPHP_CONFIG="worker ./public/index.php"

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]

CMD [ "frankenphp", "run", "--config", "/app/docker/Caddyfile" ]

# Nettoyage
RUN rm -rf /var/lib/apt/lists/* \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
