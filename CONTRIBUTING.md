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
export DB_HOST=localhost DB_NAME=debug DB_USER=quiz DB_PASS='your-password'
```

Credentials live only in your environment (`lib/config.php` has empty-string
defaults for `DB_USER`/`DB_PASS`); see `.env.example`. Tests that need a live
database skip automatically when `DB_PASS` is unset, so CI can run the suite
before secrets are wired up. Never commit real credentials anywhere in the
tree; the suite itself asserts that `lib/config.php` keeps its empty-string
credential defaults.

## Branch flow

- Feature work happens on short-lived branches off `main`
  (`feat/<topic>`, `fix/<topic>`) and lands via pull request.
- Larger revitalization efforts use long-lived integration branches
  (e.g. `revitalize/v1`) reviewed phase by phase before merging to `main`.

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
3. Tagging `vX.Y.Z` on `main` triggers the stable release build.
4. A push to `main` without a tag produces a rolling prerelease built from
   whatever is on main at that point.
