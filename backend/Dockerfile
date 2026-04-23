FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install bcmath

RUN mkdir -p /var/log/supervisor /etc/supervisor/conf.d

WORKDIR /var/www/html

COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/supervisor/conf.d/dev-php-server.conf \
     docker/supervisor/conf.d/scanner-listen.conf \
     /etc/supervisor/conf.d/

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
