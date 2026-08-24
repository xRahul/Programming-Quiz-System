# Programming-Quiz-System Revitalization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (or superpowers:executing-plans) to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Preserve 100% of existing features while fixing all security holes, correctness bugs, schema debt, architecture duplication, docs rot, and adding CI/CD, release process, and seven approved feature additions.

**Architecture:** Modern vanilla PHP 8.3 + PDO/MariaDB — no framework. Thin `lib/` layer (config, db, auth, csrf, audit, views) under existing `quiz_system_git/` webroot (kept as docroot). Schema migrates via numbered idempotent SQL scripts; destructive column drops land only after code stops referencing them. New features build on the lib layer after the architecture refactor.

**Tech Stack:** PHP 8.3, MariaDB 10.11, PHPUnit 10, CodeMirror 5.65.x, Prism.js 1.29, Docker Compose, GitHub Actions, Dependabot.

## Global Constraints

- Feature freeze baseline: every behavior in current `quiz_system_git/readme.md` §Features must survive (checklist in `docs/qa/feature-checklist.md`).
- Approved additions (v1 scope ONLY): results CSV export; answered-progress indicator; JSON question-bank import/export; config-driven branding; audit log (NO login rate-limiting); admin-assisted password reset; light responsive/a11y pass. Rejected: per-question analytics, practice mode, dark mode, login throttle, REST API, i18n.
- Additions MUST NOT alter grading/timer semantics of core quiz flow.
- PHP >= 8.2 syntax; no framework; only dev deps: PHPUnit + phpstan.
- All admin state-changing endpoints MUST require auth (`require_admin()`) + CSRF verification.
- Pre-escaped DB data MUST be entity-decoded exactly once (migration 005) BEFORE escape-on-output conversion lands.
- Every task ends green: `docker compose run --rm php vendor/bin/phpunit` passes + affected pages curl-smoke-checked.
- Conventional Commits; commit per verified step-group.
- Webroot stays `quiz_system_git/`; repo root holds compose, CI, docs.
- DCP discipline: controller runs context compression at each phase boundary; final whole-branch review before merge is mandatory.

---

## Phase 0 — Environment & Baseline

### Task 0.1: Docker toolchain + compose stack
**Files:**
- Create: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/php/php.ini`, `docker/php/opcache.ini`
- Modify: `.gitignore` (drop Laravel leftovers; add `.env`, `vendor/`, `node_modules/`, `.docker/data/`, `.superpowers/`)

Steps:
- [ ] Install docker engine if absent: `sudo apt-get update && sudo apt-get install -y docker.io docker-compose-v2`; add user to docker group (`sudo usermod -aG docker $USER`)
- [ ] Compose services: `php` (php:8.3-apache, pdo_mysql+opcache, bind-mount `quiz_system_git/` -> `/var/www/html`, port 8080:80), `db` (mariadb:10.11, MYSQL_DATABASE=debug, volume `.docker/data/`, healthcheck `healthcheck.sh --connect --innodb_initialized`)
- [ ] Verify: `docker compose up -d && sleep 10 && curl -sf http://localhost:8080/index.php | grep -qi roll`

### Task 0.2: Composer + PHPUnit skeleton
**Files:**
- Create: `quiz_system_git/composer.json` (PSR-4 `App\ => lib/`, require-dev `phpunit/phpunit ^10`), `tests/bootstrap.php`, `tests/BaselineTest.php`, `phpunit.xml`

- [ ] Verify: `docker compose run --rm php composer install && docker compose run --rm php vendor/bin/phpunit` exits 0

### Task 0.3: Behavior baselines + hygiene
**Files:**
- Create: `docs/qa/baseline-checklist.md` — scripted curl pass over all 14 pages capturing current behavior bug-for-bug (form fields, redirect targets, message strings, timer JS, right-click disable, video overlay refs)
- Delete: root `sql_log.txt`

Commit phase. **[DCP: compress Phase 0]**

---

## Phase 1 — Security Foundation

### Task 1.1: Central config + safe DB bootstrap
**Files:** Create `quiz_system_git/lib/config.php` (env DB_HOST/DB_NAME/DB_USER/DB_PASS/APP_ENV w/ compose defaults; SITE_NAME/SITE_LOGO/FOOTER_HTML constants); Modify `scripts/db.php` (creds from config, utf8mb4 PDO, generic error page, keep `db_connect()` signature)
**Test:** `tests/DbConnectTest.php` — connects vs compose MariaDB; bad creds = sanitized failure, no exception text.

### Task 1.2: Auth guard on orphan endpoints (CRITICAL)
**Files:** Create `lib/auth.php` — `require_admin(): string` (hardened session_start, $_SESSION check, DB-validate admins row, redirect `login.php?user_msg=` + exit on fail, sets `$login_session`). Modify FIRST STATEMENT of: `register.php`, `addNewQuiz.php`, `updateExistingQuiz.php`, `deleteSomeQues.php`, `editaquest.php`, `changePassword.php`.
**Test:** `tests/AuthGuardTest.php` — unauthenticated POST per endpoint = redirect, zero rows mutated; authenticated proceeds.

### Task 1.3: CSRF infrastructure
**Files:** Create `lib/csrf.php` — `csrf_token(): string`, `csrf_field(): string`, `csrf_verify(): void` (hash_equals, 403+exit). Modify ALL POST consumers: `admin.php` (12 handlers), `login_check.php`, six Task-1.2 files; forms embed `csrf_field()`, XHR bodies append token.
**Interfaces produced:** `csrf_token(): string`, `csrf_field(): string`, `csrf_verify(): void`.
**Test:** `tests/CsrfTest.php` — valid token OK; missing/mismatch 403; token stable within session.

### Task 1.4: Session hardening
**Files:** Modify `session.php` (cookie httponly=1, samesite=Lax, secure-on-https, strict mode), `login_check.php` (`session_regenerate_id(true)` on success; empty-input redirect target fixed to `login.php`).
**Test:** `tests/SessionHardeningTest.php` — cookie flags post-login; session id changes across login.

### Task 1.5: Security headers
**Files:** Create `lib/headers.php` — `send_security_headers(): void` (nosniff, X-Frame-Options DENY, Referrer-Policy strict-origin-when-cross-origin, CSP self + unsafe-inline until T4.5). Wire into index/quiz/result/login/admin.
**Test:** curl asserts headers on index/admin/login.

Commit phase. **[DCP: compress Phase 1]**

---

## Phase 2 — Correctness Bugs

Each task: failing assertion -> fix -> green -> commit.

| Task | Bug | Fix |
|---|---|---|
| 2.1 | `admin.php:478` undefined `$gaq_answer` -> MC edit prefill null | `$ga_answer` + regression assert on editAQ payload |
| 2.2 | `preg_replace('/^[a-z]/')` strips only first char (`admin.php:684,715`) | `preg_replace('/[^0-9]/','',$x)` matching L37 |
| 2.3 | `'0'` value bypasses required validation (`admin.php:60-82`, `editaquest.php:52-74`) | validate `isset()`/`=== ''` so literal `'0'` valid |
| 2.4 | `quiz.php:420` unclosed `</html>` | close tag; HTML-validate public pages |
| 2.5 | Broken `' \ '` msg escaping x5 files (register/addNewQuiz/updateExistingQuiz/changePassword/editaquest) | clean sentence strings |
| 2.6 | Timer counts negative forever after submit (`quiz.php` JS); no-op `exit()` in index JS validator | clearInterval on submit; DOM-level guard |
| 2.7 | Resubmission race `result.php` duration=0 guard | UNIQUE(username,quiz_id) from migration 003 + affected-rows check |

---

## Phase 3 — Schema Migrations

**Files:** Create `database/migrations/001..007_*.sql`, `database/debug-v2.sql` (fresh base dump), `database/migrate.sh` (idempotent applier via docker db).

Order (dependency-encoded):
1. `001_charset.sql` — 5 tables latin1 -> utf8mb4 + conn collation
2. `002_constraints.sql` — orphan/duplicate cleanup, then UNIQUE(admins.username), UNIQUE(quiz_takers.username,quiz_id)
3. `003_indexes.sql` — idx answers(question_id), questions(quiz_id), quiz_takers(quiz_id)
4. `004_fk.sql` — FKs answers->questions, questions->quizes, quiz_takers->quizes
5. `005_decode_entities.sql` + one-shot `bin/decode-stored-entities.php` — html_entity_decode question/answer/quiz_name; backup table first. MUST precede Phase 4 escape-on-output.
6. `006_drop_denorm.sql` — DROP quizes.quiz_id, questions.question_id (runs in Task 6.2 only, refs=zero verified by grep)
7. `007_audit_log.sql` — audit_log(id PK AI, actor varchar50, action varchar50, detail varchar255, created_at DEFAULT CURRENT_TIMESTAMP) (used by Task 5.3)

**Test:** migrate.sh idempotent; fresh dump == migrated legacy result; FK violations rejected.

## Phase 4 — Architecture Refactor

- **Task 4.1 Shared partials:** Create `lib/views/{head,header,footer,favicon}.php`; kill x5 favicon dup + x4 GPL footer dup; viewport meta lands here.
- **Task 4.2 Renderer collapse:** Extract `render_questions_table(array $rows, string $mode /*radio|checkbox|none*/)` + `render_results_table(array $rows, ?int $limit)` replacing 3 question renderers (~330L) + byte-twin usersQuiz/usersAll; helper `fetch_answers_by_question_ids(array $ids)` chunk-500 consistent. Snapshot tests: HTML identical pre/post on seeded fixture DB.
- **Task 4.3 Transactions + errors:** beginTransaction/commit/rollBack + try/catch -> flash msg on multi-write flows (create-question x2, editaquest, deleteSomeQues loop -> set-based deletes, deleteQuiz cascade); replace SELECT-after-DELETE verifies with DML rowCount().
- **Task 4.4 Escape-on-output:** Remove htmlspecialchars-on-input everywhere; escape every sink `htmlspecialchars($x, ENT_QUOTES)`; onclick quiz names via json_encode JSON_HEX_*; $login_session + taker usernames escaped; script prefills stay json_encode'd. Depends on migration 005.
- **Task 4.5 JS extraction:** One `assets/js/xhr-post(key, value, {confirm?, onDone})` replaces 12 XHR fns; move 7 inline blocks -> assets/js/{admin-actions,toggle,overlay,validation}.js; dedupe CodeMirror init; tighten CSP where feasible.
- **Task 4.6 Forms dedupe:** TF/MC forms share `views/question-form.php`; single language-dropdown source array. Target admin.php <= ~800 lines. **[DCP: compress Phases 2-4]**

---

## Phase 5 — Feature Additions (all inherit auth+CSRF)

- **Task 5.1 Branding config:** SITE_NAME/SITE_LOGO/FOOTER_HTML from lib/config.php consumed by partials; zero hardcoded college strings remain.
- **Task 5.2 Results CSV export:** Create `export_results.php` — require_admin, streams text/csv `results-{quiz}-{date}.csv`, honors top-20 vs all; download buttons on both results views.
- **Task 5.3 Audit log:** `lib/audit.php::audit_log(string $action, string $detail=''): void` wired into destructive handlers (resetTables, reset, deleteQuiz, clearResult, deleteAdmin, deleteSomeQues, password reset); recent-50 AJAX viewer panel in admin page.
- **Task 5.4 JSON question bank:** Create `export_questions.php?quiz=ID` (GET, admin session) + `import_questions.php` (POST upload, schema-validated, transactional, name-collision suffix `-imported`). Format: {quiz:{name,time_allotted,display_questions},questions:[{question,type,code,code_type,answers:[{text,correct}]}]}. Tests: round-trip export->import->identical render; malformed/oversized/wrong-type rejections.
- **Task 5.5 Admin-assisted password reset:** Create `reset_password.php` — require_admin + CSRF; set temp password for any account; audited via Task 5.3; small overlay form in admin page.
- **Task 5.6 Progress indicator:** "X/Y answered" counter on quiz page; pure JS over radio groups; no server change.
- **Task 5.7 Responsive/a11y light pass:** form labels, focus states, no horizontal scroll at 375px viewport; visual style otherwise preserved.

---

## Phase 6 — Frontend Modernization

- **Task 6.1 Vendor swap:** Replace codemirror/ v3 -> vendored CodeMirror 5.65.16 trimmed to needed modes (clike, javascript, php, python, sql, xml, css, htmlmixed, shell + addons matchbrackets/closebrackets). Replace sh/ -> Prism 1.29 core + autoloader + components covering the 25 brush languages (verify parity list; community components for ColdFusion/Delphi/VB; retain SH file only for verified gaps). DELETE sh/tests, compass scss, jquery-1.4.2, qunit, webrick.rb, CM3 tests/lint/acorn. Visual parity vs baseline screenshots (code coloring on seeded questions).
- **Task 6.2 Drop denorm columns:** Run migration 006_drop_denorm.sql; grep-zero verify refs; full regression suite. **[DCP: compress Phases 5-6]**

---

## Phase 7 — Performance

- Batch quiz.php answer fetches (kills last N+1 at L145-146); EXPLAIN before/after vs 003 indexes captured into docs/qa/perf-notes.md; consistent 500-chunking; opcache enabled in php image.
- Verify: query-count assertion test on quiz render (was N+1, now <=3 queries).

## Phase 8 — Tests & QA Gate

- **Task 8.1 Feature-parity suite:** docs/qa/feature-checklist.md from readme features + approved additions; automated where possible via PHP built-in server in container + curl cookie-jar: rollno gate, once-per-quiz enforcement (constraint-backed), timed submit path, marks math incl subset answering, top-20 sort marks-then-duration, all CRUD paths, resetTables reseeds admin/12345, change/delete own account, default-quiz switch, CSV export shape, import round-trip, audit entries written, progress counter present.
- **Task 8.2 Security regression suite:** unauthenticated-block set (T1.2 endpoints), CSRF-reject set, XSS fixtures (stored <script> username/quizname/question renders inert), session-flag asserts, header asserts, password-reset requires auth+CSRF+audits.
- **Task 8.3 Full local green:** phpunit + `find . -name '*.php' -print0 | xargs -0 -n1 php -l` + manual browser pass logged into checklist. **[DCP: compress Phases 7-8]**

## Phase 9 — Docs & Repo Hygiene

Rewrite root `readme.md` (canonical): badges, 2-min Docker quickstart, feature table (legacy + v1 additions), security model, upgrade guide (legacy dump -> migrations), JSON format spec, screenshots. Delete stale docs/*.pdf/.docx/.html. Add SECURITY.md, CONTRIBUTING.md (release process: semver, changelog-first, tag -> auto-release), CHANGELOG.md, LICENSE (GPL text). Archive original readme -> docs/history/v0-readme.md.

## Phase 10 — CI/CD & Release

**Files:** `.github/workflows/ci.yml`, `.github/workflows/release.yml`, `.github/dependabot.yml`
- ci.yml: push/PR -> jobs php-lint (matrix 8.2/8.3), tests (mariadb service, import debug-v2.sql, phpunit), static-analysis (phpstan level 5, committed baseline, non-blocking initially), docker-build smoke.
- release.yml: tag v* -> zip quiz_system_git/ minus dev files, sha256 checksums, draft GH Release from CHANGELOG.
- dependabot.yml: composer + github-actions weekly.
- First release tag: v1.0.0 after Phase 11.

## Phase 11 — Final Verification Sweep

Fresh-subagent whole-branch code review + critique of ALL changes vs original findings list (every critical closed?), CI green on branch, browser E2E pass logged, perf notes finalized, docs accuracy read-through, then merge + tag v1.0.0 + publish release. **[DCP: compress wrap-up]**

---

## Risks

1. Entity-decode migration semi-irreversible -> backup table in 005.
2. Prism language gaps -> SH fallback retained if found.
3. Docker install may need interactive sudo password -> controller pauses and hands command to user.
4. CSP stays permissive until T4.5; tightening is in-scope there, not silent creep.
