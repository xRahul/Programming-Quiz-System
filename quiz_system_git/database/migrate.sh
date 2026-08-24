#!/usr/bin/env bash
#
# database/migrate.sh -- apply pending schema migrations in sorted filename
# order.
#
# Target database: $DB_NAME (default: debug), matching lib/config.php. The mysql
# client additionally honors its standard environment overrides (MYSQL_HOST,
# MYSQL_PWD, MYSQL_TCP_PORT, ...) and ~/.my.cnf credentials.
#
# Credentials: every variable is OPTIONAL. When DB_HOST/DB_USER/DB_PASS are
# exported they are passed to the mysql client explicitly; when omitted the
# client falls back to its own defaults (unix-socket OS auth locally). The PDO
# decode step (bin/decode-stored-entities.php) reads the same variables via
# lib/config.php and does need real TCP credentials where socket auth cannot
# apply.
#
# Applied files are recorded in a schema_migrations table, so running this a
# second time is a no-op. Any failing statement aborts the run non-zero before
# that file is recorded.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB="${DB_NAME:-debug}"
DIR="$ROOT/database/migrations"

# Client auth mirrors lib/config.php: DB_HOST/DB_USER/DB_PASS when exported
# (CI), default socket/OS-auth otherwise. Without --host the client targets a
# local unix socket that does not exist in containerized environments.
MYSQL_ARGS=()
if [ -n "${DB_HOST:-}" ]; then MYSQL_ARGS+=(--host="$DB_HOST"); fi
if [ -n "${DB_USER:-}" ]; then MYSQL_ARGS+=(--user="$DB_USER"); fi
if [ -n "${DB_PASS:-}" ]; then MYSQL_ARGS+=(--password="$DB_PASS"); fi

mysql_q() { mysql "${MYSQL_ARGS[@]}" -N "$DB" -e "$1"; }

mysql_q "CREATE TABLE IF NOT EXISTS schema_migrations (
  name VARCHAR(64) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4"

applied=0
for file in "$DIR"/*.sql; do
  [ -e "$file" ] || continue
  name="$(basename "$file")"

  # Filenames are interpolated into SQL below; repo-controlled input, but a
  # stray quote would corrupt the statement -- refuse anything unusual.
  if ! printf '%s' "$name" | grep -qE '^[A-Za-z0-9._-]+$'; then
    echo "migrate: refusing unsafe migration filename: $name" >&2
    exit 1
  fi

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
  mysql "${MYSQL_ARGS[@]}" "$DB" < "$file"
  mysql_q "INSERT INTO schema_migrations (name) VALUES ('$name')"
  applied=$((applied + 1))
done

echo "migrate: done (applied=$applied)"
