#!/bin/sh
set -e

PGDATA="${PGDATA:-/var/lib/postgresql/data}"
POSTGRES_USER="${POSTGRES_USER:-barcodile}"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-barcodile}"
POSTGRES_DB="${POSTGRES_DB:-barcodile}"
export PGDATA

prepare_postgres_directories() {
  mkdir -p /var/log/supervisor /var/lib/postgresql "$PGDATA"
  chown postgres:postgres /var/lib/postgresql "$PGDATA"
}

initialize_postgres() {
  if [ -s "$PGDATA/PG_VERSION" ]; then
    return
  fi

  su postgres -c "LC_ALL=C.UTF-8 /usr/lib/postgresql/16/bin/initdb -D $PGDATA -E UTF8 --locale=C.UTF-8 --username=postgres"
  echo "listen_addresses = '*'" >> "$PGDATA/postgresql.conf"
  echo "host all all 0.0.0.0/0 scram-sha-256" >> "$PGDATA/pg_hba.conf"
}

start_postgres_for_setup() {
  su postgres -c "/usr/lib/postgresql/16/bin/pg_ctl -D $PGDATA -l $PGDATA/startup.log -w start"
}

create_database() {
  su postgres -c "createuser \"$POSTGRES_USER\"" || true
  su postgres -c "psql -U postgres -c \"ALTER USER \\\"$POSTGRES_USER\\\" WITH PASSWORD '$POSTGRES_PASSWORD';\""
  su postgres -c "createdb -O \"$POSTGRES_USER\" \"$POSTGRES_DB\"" || true
}

prepare_app_var() {
  mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/share
  chown -R www-data:www-data /var/www/html/var
}

run_app_setup() {
  cd /var/www/html
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
  php bin/console cache:clear
}

stop_postgres_setup() {
  su postgres -c "/usr/lib/postgresql/16/bin/pg_ctl -D $PGDATA -w stop -m fast"
}

prepare_postgres_directories
initialize_postgres
start_postgres_for_setup
create_database
prepare_app_var
run_app_setup
stop_postgres_setup

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
