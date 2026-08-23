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
mysql debug < quiz_system_git/database/debug.sql   # adjust path if importing into a worktree copy
mysql debug -e "SELECT COUNT(*) FROM quizes"       # expect 2
```

Optional dedicated user instead of root/socket:

```bash
sudo mysql -e "CREATE USER IF NOT EXISTS 'quiz'@'localhost' IDENTIFIED BY 'quizpass'; GRANT ALL ON debug.* TO 'quiz'@'localhost'"
# then export DB_USER=quiz DB_PASS=quizpass before running tests/server (lib/config.php reads these)
```

## 3. Run things daily

```bash
cd WORKTREE/quiz_system_git
composer install                # once; creates vendor/ incl. PHPUnit
vendor/bin/phpunit              # full test suite
php -S localhost:8080           # dev server from quiz_system_git/, then open http://localhost:8080/index.php
mariadb debug                   # SQL shell
```

Reset data anytime: `mysql debug < database/debug.sql` (re-imports seeds; destructive).

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
