FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM php:8.5-fpm-bookworm

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

COPY --from=frontend /usr/local /usr/local

WORKDIR /var/www/html

COPY backend/requirements-label.txt /tmp/requirements-label.txt
RUN pip3 install --no-cache-dir --break-system-packages -r /tmp/requirements-label.txt \
    && rm -f /tmp/requirements-label.txt

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --no-scripts

COPY backend/ ./
RUN composer dump-autoload

COPY --from=frontend /app/dist /var/www/html/spa

COPY frontend/ /app/frontend/

RUN mkdir -p /var/log/supervisor \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=dev \
    APP_DEBUG=1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
