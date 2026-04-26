FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM php:8.5-fpm-bookworm

ARG COMPOSER_NO_DEV=1

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-libmysqlclient-dev \
        git \
        gnupg \
        libicu-dev \
        libusb-1.0-0 \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        python3 \
        python3-pip \
        python3-venv \
        nginx \
        supervisor \
        unzip \
        wget \
        zlib1g-dev; \
    wget -qO /tmp/ACCC4CF8.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc; \
    gpg --dearmor -o /usr/share/keyrings/postgresql-archive-keyring.gpg /tmp/ACCC4CF8.asc; \
    echo 'deb [signed-by=/usr/share/keyrings/postgresql-archive-keyring.gpg] http://apt.postgresql.org/pub/repos/apt bookworm-pgdg main' > /etc/apt/sources.list.d/pgdg.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends postgresql-16; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j1 \
        bcmath \
        intl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip; \
    rm -rf /var/lib/apt/lists/* /tmp/ACCC4CF8.asc

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:22-bookworm-slim /usr/local /usr/local

WORKDIR /var/www/html

COPY backend/requirements-label.txt /tmp/requirements-label.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/requirements-label.txt \
    && rm -f /tmp/requirements-label.txt

COPY backend/composer.json backend/composer.lock ./
RUN if [ "$COMPOSER_NO_DEV" = "1" ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
    else \
      composer install --optimize-autoloader --no-interaction --no-scripts; \
    fi

COPY backend/ ./
RUN composer dump-autoload --optimize --classmap-authoritative

COPY --from=frontend /app/dist /var/www/html/spa

COPY frontend/ /app/frontend/

COPY docker/prod/nginx-site.conf /etc/nginx/sites-available/barcodile
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /etc/nginx/sites-available/barcodile /etc/nginx/sites-enabled/barcodile

RUN mkdir -p /var/log/supervisor \
    && mkdir -p /etc/supervisor/dev.d /etc/supervisor/prod.d \
    && mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

COPY docker/supervisor/supervisord.dev.conf /etc/supervisor/supervisord.dev.conf
COPY docker/supervisor/supervisord.prod.conf /etc/supervisor/supervisord.prod.conf
COPY docker/supervisor/dev.d/ /etc/supervisor/dev.d/
COPY docker/supervisor/prod.d/ /etc/supervisor/prod.d/

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    BARCODILE_RUNTIME=prod

EXPOSE 80 8000 5173 5432

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
