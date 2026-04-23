#!/bin/sh
set -e
PGDATA=/var/lib/postgresql/data
export PGDATA
mkdir -p /var/log/supervisor /var/lib/postgresql
chown postgres:postgres /var/lib/postgresql
mkdir -p "$PGDATA"
chown postgres:postgres "$PGDATA"
if [ ! -s "$PGDATA/PG_VERSION" ]; then
  su postgres -c "LC_ALL=C.UTF-8 /usr/lib/postgresql/16/bin/initdb -D $PGDATA -E UTF8 --locale=C.UTF-8 --username=postgres"
  echo "listen_addresses = '*'" >> "$PGDATA/postgresql.conf"
  echo "host all all 0.0.0.0/0 scram-sha-256" >> "$PGDATA/pg_hba.conf"
fi
su postgres -c "/usr/lib/postgresql/16/bin/pg_ctl -D $PGDATA -l $PGDATA/startup.log -w start"
POSTGRES_USER="${POSTGRES_USER:-barcodile}"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-barcodile}"
POSTGRES_DB="${POSTGRES_DB:-barcodile}"
su postgres -c "createuser \"$POSTGRES_USER\"" || true
su postgres -c "psql -U postgres -c \"ALTER USER \\\"$POSTGRES_USER\\\" WITH PASSWORD '$POSTGRES_PASSWORD';\""
su postgres -c "createdb -O \"$POSTGRES_USER\" \"$POSTGRES_DB\"" || true
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/share
chown -R www-data:www-data /var/www/html/var
cd /var/www/html
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console cache:clear
su postgres -c "/usr/lib/postgresql/16/bin/pg_ctl -D $PGDATA -w stop -m fast"
if [ "$BARCODILE_RUNTIME" = "dev" ]; then
  cp /etc/supervisor/supervisord.dev.conf /etc/supervisord.conf
else
  cp /etc/supervisor/supervisord.prod.conf /etc/supervisord.conf
fi
exec /usr/bin/supervisord -c /etc/supervisord.conf
