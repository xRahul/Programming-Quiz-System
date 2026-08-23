#!/usr/bin/env bash
#
# database/migrate.sh -- apply pending schema migrations in sorted filename
# order.
#
# Target database: $DB_NAME (default: debug), matching lib/config.php. The mysql
# client additionally honors its standard environment overrides (MYSQL_HOST,
# MYSQL_PWD, MYSQL_TCP_PORT, ...) and ~/.my.cnf credentials.
#
# Applied files are recorded in a schema_migrations table, so running this a
# second time is a no-op. Any failing statement aborts the run non-zero before
# that file is recorded.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB="${DB_NAME:-debug}"
DIR="$ROOT/database/migrations"

mysql_q() { mysql -N "$DB" -e "$1"; }

mysql_q "CREATE TABLE IF NOT EXISTS schema_migrations (
  name VARCHAR(64) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4"

applied=0
for file in "$DIR"/*.sql; do
  [ -e "$file" ] || continue
  name="$(basename "$file")"

  if [ "$(mysql_q "SELECT COUNT(*) FROM schema_migrations WHERE name = '$name'")" != "0" ]; then
    echo "migrate: skip   $name (already applied)"
    continue
  fi

  echo "migrate: applying $name"
  case "$name" in
    *decode_entities*)
      php "$ROOT/bin/decode-stored-entities.php"
      ;;
  esac
  mysql "$DB" < "$file"
  mysql_q "INSERT INTO schema_migrations (name) VALUES ('$name')"
  applied=$((applied + 1))
done

echo "migrate: done (applied=$applied)"
