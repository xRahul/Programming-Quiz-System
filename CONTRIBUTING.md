# Contributing

Thanks for helping revitalize the Programming Quiz System.

## Development setup

Follow [setup-remove.md](setup-remove.md) for the full native Ubuntu 24.04
toolchain install (PHP 8.3 CLI + extensions, MariaDB, Composer). The short
version:

```bash
cd quiz_system_git
composer install
mysql -e "CREATE DATABASE IF NOT EXISTS debug CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql debug < database/debug-v2.sql
export DB_NAME=debug DB_USER=quiz DB_PASS='your-password'
```

Credentials live only in your environment (`lib/config.php` has empty-string
defaults for `DB_USER`/`DB_PASS`); see `.env.example`. Tests that need a live
database skip automatically when `DB_PASS` is unset, so CI can run the suite
before secrets are wired up. Never commit real credentials anywhere in the
tree; the suite itself asserts that `lib/config.php` keeps its empty-string
credential defaults.

## Test database credentials

The app reads `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` (see
`lib/config.php`, `.env.example`). The test suite additionally needs a
**database-admin** identity for its scratch-DB suites (they create/drop
databases, grant/revoke, and toggle the general log). Resolution lives in
`tests/TestEnv.php` and walks four tiers:

| Tier | When | Admin identity used |
| --- | --- | --- |
| 1 | `DB_ADMIN_USER` is set | Exactly those creds (`DB_ADMIN_PASS` / `DB_ADMIN_HOST`); a failed connection **fails loud** |
| 2 | CI only (`CI=true`/`1`, or opt-in `DB_ADMIN_ASSUME_ELEVATED=1`) **and** `DB_HOST` is exported | Same as the app user — valid because CI's *Elevate test user* step grants it `ALL PRIVILEGES ON *.* WITH GRANT OPTION` (scratch provisioning and `SET GLOBAL general_log` need this) |
| 3 | Local default | Your OS account over the unix socket (`root` on stock Ubuntu MariaDB) — zero-config |
| 4 | Fallback | App credentials at 127.0.0.1 |

Suites that cannot obtain a working admin identity **skip gracefully**;
tier 1 (explicitly supplied credentials that do not work) is the one case
that errors loudly, because you asked for it explicitly.

> **Footgun avoided:** do not export `DB_HOST` locally just to be explicit.
> An exported `DB_HOST` switches PDO (and tier 2 when in CI) from socket to
> TCP; locally your user likely lacks the elevation grants tier 2 assumes,
> which would turn graceful skips into hard GRANT errors. Leave `DB_HOST`
> unset locally unless your MariaDB really is TCP-only.

## Branch flow

- Feature work happens on short-lived branches off `master`
  (`feat/<topic>`, `fix/<topic>`) and lands via pull request.
- Larger revitalization efforts use long-lived integration branches
  reviewed phase by phase before merging to `master`.

## Commit style

[Conventional Commits](https://www.conventionalcommits.org/) —
`type: imperative subject`, e.g. `fix: scrub baked-in credentials`,
`docs: rewrite root readme`, `test: guard resettables fk defect`.
Types in use: `feat`, `fix`, `test`, `refactor`, `perf`, `docs`, `chore`.

## Running the tests

```bash
cd quiz_system_git
vendor/bin/phpunit        # full suite; ~1-2 min typical, longer on first run
                          # (scratch-DB suites provision fresh databases); needs local MariaDB
```

The suite is green at ~94 tests / ~1100 assertions. Every commit that touches
code or tests must keep it green.

## Release process

1. Versions follow [semver](https://semver.org/).
2. Releases are **CHANGELOG-first**: land the release notes under a new
   `[X.Y.Z]` heading in [CHANGELOG.md](CHANGELOG.md) as part of the release
   PR.
3. Tagging `vX.Y.Z` on `master` triggers the stable release build.
4. A push to `master` without a tag produces a rolling prerelease built from
   whatever is on master at that point.
