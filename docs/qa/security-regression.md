# Security Regression Matrix — finding → fix commit → protecting test

Every row cites an automated test that FAILS if the fix regresses. Tests live in
`quiz_system_git/tests/`. Commit hashes are on `revitalize/v1`. Baseline line
numbers refer to `docs/qa/baseline-checklist.md`.

| # | Original finding | Closing commit | Protecting test (fails on regression) |
|---|---|---|---|
| 1 | No auth on privileged endpoints: register, addNewQuiz, updateExistingQuiz, deleteSomeQues, editaquest, changePassword (baseline "Security baseline" notes L184, L213, plus unauth create/delete flows) | 653763b require admin authentication on all privileged endpoints | `AuthGuardTest::testUnauthenticatedPostsAreRedirectedWithoutMutatingData` — 302 + zero DB mutation for all 6 |
| 2 | Unauthenticated admin.php access (GET/POST) via missing session revalidation | 653763b (+ session.php re-validate on every include) | `SessionHardeningTest::testLogoutExpiresCookieAndRejectsOldSession` (stale session bounced from admin surface); `AuthGuardTest` for the endpoint files |
| 3 | CSRF: 12+ state-changing sinks accepted token-less POSTs (register, changePassword, addNewQuiz, updateExistingQuiz, deleteSomeQues, editaquest, import/reset endpoints, and every destructive admin.php handler: clearResult / resetTables / deleteAdmin / defaultQuiz / deleteQuiz / question create-edit) | 5618ac9 enforce CSRF tokens on state-changing endpoints | Depth: `CsrfTest::testRegisterWithoutTokenIsRejectedWith403AndNoMutation`, `::testRegisterWithWrongTokenIsRejectedWith403`, `::testAdminClearResultWithoutTokenIsRejectedWith403`. Breadth at the gate level: admin.php opens with a single unconditional `csrf_verify()` that covers every handler it exposes, so no per-handler bypass is possible there; `CsrfEnforcementCoverageTest::testEveryStateChangingEndpointRejectsMissingToken` additionally sweeps a representative subset of entry points/handlers (403 + zero mutation across all 9 entry points / 11 handler keys) |
| 4 | Session fixation: session id not regenerated at login | 076a3b7 harden session lifecycle | `SessionHardeningTest::testLoginRegeneratesSessionIdAndMarksCookieHttpOnly` |
| 5 | Session cookie flags (no HttpOnly/SameSite at login) | 076a3b7 | `SessionHardeningTest::testLoginRegeneratesSessionIdAndMarksCookieHttpOnly` + `SecurityHeadersTest::testLoginSessionCookieIsHardened` |
| 6 | Logout left cookie/session reusable | baseline logout section L237–242 | `SessionHardeningTest::testLogoutExpiresCookieAndRejectsOldSession` |
| 7 | No security headers on public/admin pages | b3e5191 send security headers on public and admin pages | `SecurityHeadersTest::testEveryRenderedPageSendsSecurityHeaders` |
| 8 | XSS sink: index/login `user_msg` echo | 47e59b3 switch to escape-on-output | `EscapingTest::testPublicMessageEchoesEscapeUserMsg` |
| 9 | XSS sink: taker usernames rendered into results tables | 47e59b3 | `EscapingTest::testAdminAjaxBodiesEscapeTheProbe` (usersAll rows asserted escaped) |
| 10 | XSS sink: quiz names / question text in AJAX bodies and admin menu JS args | 47e59b3 + 8e04197 cast db ints before htmlspecialchars | `EscapingTest::testAdminAjaxBodiesEscapeTheProbe`, `EscapingTest::testAdminPageMenuHexEncodesNamesInJsArgsAndNeverEmitsRawProbe` |
| 11 | XSS sink: `$login_session` in admin greeting (`span#usr`) | 47e59b3 | `EscapingTest::testAdminGreetingEscapesHostileLoginSession` (added T8 — hostile admin name through real register/login/render flow) |
| 12 | XSS sink: audit-log viewer rows | 1e66fbe audit log feature | `AuditLogTest::testAuditRecentViewerReturnsEscapedRows` |
| 13 | Escape-at-input corrupted stored data (double-encoding fragility) | 47e59b3 | `EscapingTest::testStoredValuesAreRawAfterHttpCreateFlows` (probe must round-trip RAW into DB) |
| 14 | SQL injection posture: legacy interpolated queries; DB bootstrap leaked credential errors | b243e81 centralize config and harden database bootstrap (PDO + prepared statements everywhere) | `DbConnectTest::testBadCredentialsFailSanitized`; quote-laden probes survive every HTTP create/read path via `EscapingTest::testStoredValuesAreRawAfterHttpCreateFlows` + `::testAdminAjaxBodiesEscapeTheProbe` |
| 15 | Strict-mode fatal INSERTs (admin.php:87, quiz.php:43, addNewQuiz.php:26) left write paths dead | 6d9e452 migrations (005a strict-mode defaults) | `MigrationTest::testStrictModeDefaultsSurviveHttpQuestionCreate`; end-to-end writes proven by `UiCreatedQuizListableTest::testQuizCreatedViaUiListsItsQuestions` and the resubmission E2E (#17) |
| 16 | Duplicate taker rows possible on double submit | 6d9e452 (unique key `uq_takers_user_quiz`) | `MigrationTest::testDuplicateTakerInsertRejectedByUniqueKey` |
| 17 | Result replay when the marks UPDATE matched 0 rows | 98ef20e block replayed quiz submissions when update claims no row | `CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission` |
| 18 | Empty-field login landed ON admin.php (`Sorry, but Something went wrong`) leaking page context | phase-2 login hardening | `SessionHardeningTest::testEmptyInputLoginRedirectsToLoginNotAdmin` |
| 19 | Raw client-supplied ids interpolated in clearResult/reset handlers | 63d3589 strip all non-digits in clearResult and reset handlers | `CorrectnessFixesTest::testClearResultStripsAllNonDigitsBeforeResolvingQuizId` |

## Defect found by the T8 gap sweep — CLOSED in fix round 1

| # | Finding | Status | Evidence |
|---|---|---|---|
| 20 | **resetTables half-resets and locks out the database.** admin.php's handler ran bare `TRUNCATE TABLE` in parent-before-child order (admins, answers, questions, quizes, quiz_takers). Under the FK schema shipped by migration runner (fk_answers_question/fk_answers_quiz/fk_questions_quiz/fk_takers_quiz), `TRUNCATE TABLE questions` aborted with MariaDB error 1701 even though answers was empty — InnoDB refuses TRUNCATE on any referenced table. Sequence died as a blank `HTTP/1.0 500` AFTER admins was truncated but BEFORE the default-admin INSERT: verified repro left `admins=0, quizes=2, questions=31` — no admin could log in again without manual SQL. Legacy pre-FK schema never hit this because unconstrained InnoDB allowed the truncates. | **CLOSED** (fix round 1, same commit as this matrix update): handler now truncates child-first (answers → quiz_takers → questions → quizes → admins) inside a session-scoped `SET FOREIGN_KEY_CHECKS=0 … =1` window, so the reseed INSERT + audit row always follow a successful wipe; success message and audit wiring byte-identical | Protecting test (fails if the fix regresses): `ResetTablesReseedTest::testResetTablesTruncatesAndReseedsBcryptAdmin` — armed end-to-end: authed+CSRF POST via UI → 200 "your database is now reset", exactly one bcrypt-verifiable admin/'12345', all five content tables at zero, reset_tables audit row present (21 assertions) |

## Test-data note

During ad-hoc verification of #20 the manual repro accidentally ran against the
shared `debug` database instead of a scratch one; `debug.admins` and
`debug.answers` were restored to seed state from `database/debug-v2.sql`
(admin/'12345' bcrypt row re-inserted, 116 answer rows re-imported) and the full
suite re-run green afterwards. Committed tests are unaffected —
`ResetTablesReseedTest` provisions its own scratch DB via `DB_NAME`.
