#!/bin/sh
set -e
mkdir -p var/cache var/log var/share
chown -R www-data:www-data var
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console cache:clear
exec /usr/bin/supervisord -c /etc/supervisord.conf
