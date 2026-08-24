# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

#### Fixed

- Scratch-database test suites are credential-portable: the admin identity
  now resolves through explicit `DB_ADMIN_*` variables, CI's elevated app
  user, or the local OS socket account (`tests/TestEnv.php`) instead of
  assuming a local superuser exists.
- `database/migrate.sh` authenticates its `mysql` client from `DB_HOST` /
  `DB_USER` / `DB_PASS` when set, falling back to socket auth locally,
  instead of silently assuming a socket connection everywhere.
- Grant/revoke principals inside scratch suites interpolate correctly for
  both `user@localhost` (socket) and `user@%` (TCP) MariaDB accounts.
- CI: PHPUnit executes under a watchdog that reports wedged runs with the
  PHPUnit event-log tail; failing-run logs reach check-run annotations even
  when no known failure pattern matches, and step diagnostics survive
  non-zero exits and unmatched greps under `bash -e`/`pipefail`.

#### Changed

- CI tests job is bounded at 25 minutes with an always-on diagnostics
  artifact; the watchdog gives up at 20 minutes so it fires first.
- CONTRIBUTING.md documents the complete `DB_*` / `DB_ADMIN_*`
  environment contract, the four-tier resolution order, and why exporting
  `DB_HOST` locally is discouraged outside TCP-only setups.

## [1.0.0] - 2026-08-23

Revitalization of the legacy quiz system

Modernization pass over the original 2014 XAMPP-era codebase: every legacy
feature preserved with bug-for-bug repairs, plus a security, testing, and
operations overhaul. Tracked on the `revitalize/v1` branch; see
[CONTRIBUTING.md](CONTRIBUTING.md) for the release process.

#### Added

- PHPUnit test suite (~94 tests / ~1100 assertions): page-structure and
  rendering parity vs the legacy baseline, CSRF enforcement coverage,
  auth-guard, session-hardening, security-header, escaping, transaction,
  correctness, migration, and performance (query-count) suites.
- JSON question-bank import/export for admins (transactional, validated,
  audit-logged).
- CSV results export with top/all scopes and a stable filename contract.
- Audit log for destructive admin actions + escaped viewer panel.
- Admin-assisted password reset flow.
- Quiz progress counter wired into the quiz-page boot island.
- Branding configuration via `SITE_NAME` / `SITE_LOGO` / `FOOTER_HTML`.
- Responsive/accessibility pass: form labels, focus outlines, overflow
  containment.
- Ordered schema migrations (`database/migrations/`) applied idempotently by
  `database/migrate.sh`; refreshed seed dataset `database/debug-v2.sql`.
- Environment-based configuration (`DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`/
  `APP_ENV`) with `.env.example`; no credentials committed; live-DB tests skip
  gracefully when credentials are absent.
- Project docs: root README, SECURITY.md, CONTRIBUTING.md, this changelog,
  GPLv3 LICENSE; historical readme preserved under `docs/history/`.

#### Changed

- Frontend vendors pinned under `assets/vendor/`: Prism 1.29 replaces the
  unmaintained SyntaxHighlighter; CodeMirror stays at 5.65.
- Stored values stay raw in the database; all HTML escaping moved to output
  time.
- Answers fetched in exactly one batched query per quiz render.
- Results ranking fixed to marks-descending then duration-ascending, Top-20
  capped at 20 rows.
- Reset-tables wipe reordered to survive foreign-key constraints and reseed
  with a bcrypt-hashed default admin.
- Deleting a quiz now cascades its `quiz_takers` rows too (FK semantic drift:
  previously those rows were orphaned).

#### Security

- PDO prepared statements throughout; strict-mode-safe inserts.
- CSRF token verification on every state-changing endpoint.
- Session hardening: cookie flags (HttpOnly/SameSite), ID rotation on login,
  logout destroys session and cookie.
- Central security headers (CSP, frame/media/referrer policies).
- Sanitized database-failure output in production mode (no driver errors or
  stack traces leaked).
- Removed the baked-in database password from defaults, tests, and setup docs;
  credentials now come strictly from the environment.

## [0.x] — legacy

Pre-2026 XAMPP-era releases. See
[docs/history/v0-readme.md](docs/history/v0-readme.md) for behavior notes.
Not supported for security fixes — see [SECURITY.md](SECURITY.md).
