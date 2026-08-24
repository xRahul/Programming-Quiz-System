# Local Dev Setup / Removal — Programming-Quiz-System

Native Ubuntu 24.04 stack (no Docker). Run the `sudo` commands yourself; everything else can be done by any agent/session. Applies to the repo checkout you are working in (`WORKTREE` below = your checkout root).

## 1. Install toolchain (one-time)

```bash
sudo apt-get update
sudo apt-get install -y php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl mariadb-server composer
sudo systemctl enable --now mariadb
```

What you get: PHP 8.3 CLI (+ pdo_mysql, mbstring, xml, curl), MariaDB server, Composer (PHPUnit installs per-project via composer.json).

## 2. Bootstrap database

Ubuntu's MariaDB lets the local OS user connect as MySQL `root` with no password over the unix socket (unix_socket auth) — the app's legacy `scripts/db.php` creds (`localhost` / `root` / empty) therefore work unchanged:

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS debug CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql debug < quiz_system_git/database/debug-v2.sql   # canonical post-migration seed; adjust path for worktree copies
mysql debug -e "SELECT COUNT(*) FROM quizes"          # expect 2
```

Coming from the legacy dataset instead? Import `database/debug.sql`, then
apply the migration chain: `bash quiz_system_git/database/migrate.sh`
(idempotent; records applied steps in `schema_migrations`). Before running
migrate.sh, export `DB_USER` / `DB_PASS` (the credentials created in §2) — its
PDO step needs them; unix-socket root auth does not apply. `debug-v2.sql`
already bakes in the fully migrated schema, so prefer it for fresh installs.

Optional dedicated user instead of root/socket (choose your own strong password — nothing is stored in the repo):

```bash
sudo mysql -e "CREATE USER IF NOT EXISTS 'quiz'@'localhost' IDENTIFIED BY '<choose-a-password>'; GRANT ALL ON debug.* TO 'quiz'@'localhost'"
# then export DB_USER=quiz and DB_PASS=<the password you chose> in your shell
# before running tests/server (lib/config.php reads these; see .env.example)
```

The app reads `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` / `APP_ENV` from the environment. Tests that need a live database skip gracefully when `DB_PASS` is unset or empty.

## 3. Run things daily

```bash
cd WORKTREE/quiz_system_git
composer install                # once; creates vendor/ incl. PHPUnit
vendor/bin/phpunit              # full test suite
php -S localhost:8080           # dev server from quiz_system_git/, then open http://localhost:8080/index.php
mariadb debug                   # SQL shell
```

Reset data anytime: `mysql debug < quiz_system_git/database/debug-v2.sql` (re-imports the canonical seed; destructive).

## 4. Full removal / cleanup

```bash
# App-level (safe, no sudo)
rm -rf WORKTREE/quiz_system_git/vendor

# Remove the worktree copy entirely (run from main checkout)
git worktree remove .worktrees/revitalize-v1
git branch -D revitalize/v1

# Purge packages + data (DESTRUCTIVE: deletes all MariaDB databases)
sudo systemctl disable --now mariadb
sudo apt-get purge -y 'php8.3-*' mariadb-server mariadb-client composer
sudo apt-get autoremove --purge -y
sudo rm -rf /var/lib/mysql   # only if you want database files gone too
```

Keep-installed-but-unused risk: none — everything above is standard Ubuntu repo packages, no PPAs.
