# Feature Parity Checklist — v1 vs legacy baseline

Companion to `baseline-checklist.md` (bug-for-bug capture, 2026-08-23).
Each legacy behavior / readme feature maps to the automated test that fails if
it regresses, or carries a `MANUAL` tag for visual-only items. Proof paths are
`quiz_system_git/tests/<Suite>.php::<method>` unless noted.

Status legend: **kept** = behavior preserved · **kept+fixed** = behavior preserved,
known legacy defect repaired · **defect** = regression/bug found by T8 (see
security-regression.md) · **new** = approved addition.

## Public quiz flow

| # | Feature | Legacy ref (baseline) | v1 status | Proof |
|---|---|---|---|---|
| 1 | Instructions page shows default quiz name + duration/count line | index.php block, L41–43 | kept | `StructureParityTest::testWrapperRegionsMatchCommittedSnapshot` (snapshot-index.html); switch dynamics: `DefaultQuizSwitchTest::testSwitchingDefaultReflectsOnIndexInstructions` |
| 2 | Roll-no required client-side before submit | index.php, L46 | kept | `CorrectnessFixesTest::testIndexSubmitValidatorReturnsFalseInsteadOfExit` |
| 3 | `user_msg` echoed escaped on index/login | global quirks table, L34; L47, L54 | kept | `EscapingTest::testPublicMessageEchoesEscapeUserMsg` |
| 4 | User can't attempt the same quiz twice | readme L148; quiz.php source notes, L154–155 | kept | `OncePerQuizGuardTest::testSecondStartOfSameQuizIsBlockedWithLegacyMessage` (start-side) + `CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission` (replay-side) |
| 5 | Quiz page renders N random questions as `rads1..N` radio groups | quiz.php source notes, L148–150 | kept | `QuizRenderParityTest::testQuizBodyMatchesCommittedSnapshot` (sequential-group assertion) |
| 6 | Server-rendered hidden inputs `rollno`, `total_ques`, `total_time`, `quizID` | quiz.php source notes, L149 | kept | `QuizRenderParityTest::testQuizBodyMatchesCommittedSnapshot` (verbatim `substr_count` assertions) |
| 7 | Countdown auto-submits at zero; timer stops on manual submit | readme quiz page L155–156 | kept | `CorrectnessFixesTest::testQuizTimerUsesIntervalAndStopsOnSubmit`; dial position: PASS (E2E 2026-08-24) — live quiz render shows `<div id="countdown">` in position above form (quiz.php:297); curl cannot measure pixels, verified as markup presence |
| 8 | No negative marking; marks = count of submitted correct answer ids | result.php observed scoring, L163 | kept | `CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission` (marks recorded once) + `ExportResultsTest::testTopScopeOrdersByMarksDescDurationAscAndComputesPercentage` (percentage math); unknown-answer-id tolerance: PASS (E2E 2026-08-24) — POSTed result with rads2=999999 alongside a correct id: HTTP 200, no fatal, marks=1 counted from recognized ids only, single taker row |
| 9 | Back-button resubmission blocked (`duration != 0` guard) | result.php, L164 | kept | `CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission` |
| 10 | Result body renders `<roll>, You scored X/Y` | result.php, L163 | kept | `CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission` (`You scored`) |

## Admin surface

| # | Feature | Legacy ref (baseline) | v1 status | Proof |
|---|---|---|---|---|
| 11 | Admin pages require login; every privileged POST redirects anonymous callers | L79 + register/changePassword/etc. sections | kept | `AuthGuardTest::testUnauthenticatedPostsAreRedirectedWithoutMutatingData` (6 endpoints, zero-mutation) |
| 12 | Create TF / MC question through the real form | admin create flows, L81–113 | kept+fixed (strict-mode fatal gone) | `UiCreatedQuizListableTest::testQuizCreatedViaUiListsItsQuestions`; strict defaults: `MigrationTest::testStrictModeDefaultsSurviveHttpQuestionCreate`; MC edit prefill: `CorrectnessFixesTest::testEditAqPrefillsRealMcAnswersNotUndefinedVar` |
| 13 | View All Questions (AJAX, all-quizzes + per-quiz) | AJAX handler table, L123 | kept | `StructureParityTest::testAjaxBodyMatchesCommittedSnapshot` (`ajax-questionsQuiz-*`) |
| 14 | Edit a Question (select + prefill + save) | AJAX table, L125; editaquest section | kept | `StructureParityTest` (`ajax-editaquestion-*`) + `TransactionTest::testEditQuestionRollsBackQuestionUpdateAndAnswerDelete` + `CorrectnessFixesTest::testEditQuestionRejectsEmptyDescButAcceptsLiteralZero` |
| 15 | Delete Some Questions (checkbox set) | deleteSomeQues section | kept | `TransactionTest::testSetBasedDeleteRemovesExactlyRequestedIdsAndDecrementsEachQuiz` + `StructureParityTest` (`ajax-deleteSomeQuestions-*`) |
| 16 | Add New Quiz (duration + max questions) | addNewQuiz section | kept+fixed | `UiCreatedQuizListableTest::testQuizCreatedViaUiListsItsQuestions` |
| 17 | Update Metadata (time / question count) | updateExistingQuiz section, L206–214 | kept | `CorrectnessFixesTest::testUserMessagesCarryNoBackslashArtifacts` (`updateExistingQuiz` flow does a real POST) |
| 18 | Set Default quiz; instructions page follows | AJAX table, L126 | kept | `DefaultQuizSwitchTest::testSwitchingDefaultReflectsOnIndexInstructions` |
| 19 | Results (Top 20) ranked marks desc then duration asc, capped at 20 | readme results rules L131–135; AJAX table, L121 | kept | `AdminTopTwentyOrderTest::testUsersQuizCapsAtTwentyInCompositeOrder` |
| 20 | Results (All), uncapped, same ranking | readme L90 | kept | `AdminTopTwentyOrderTest::testUsersAllReturnsEveryTakerSameOrder` |
| 21 | Clear the Result (per quiz) | AJAX table destructive row, L130–132 | kept | `AuditLogTest::testClearResultWritesAuditRowWithActorAndAction` + `CsrfTest::testAdminClearResultWithoutTokenIsRejectedWith403` + `CorrectnessFixesTest::testClearResultStripsAllNonDigitsBeforeResolvingQuizId` |
| 22 | Register an Admin | register.php section | kept | `CsrfTest::testRegisterWithValidTokenStillSucceeds` + `AuthGuardTest::testAuthenticatedAdminCanStillRegisterNewUser` |
| 23 | Change Password (own account) | changePassword section, L186–193 | kept | `CorrectnessFixesTest::testUserMessagesCarryNoBackslashArtifacts` (`changePassword` flow) |
| 24 | Delete Your Account (self-delete allowed, no guard) | readme L99–100; destructive list L130–132 | kept | `DeleteAdminSelfDeleteTest::testSelfDeleteMatchesLegacyAllowedBehavior` |
| 25 | Reset All Tables → single bcrypt admin/'12345' | readme L101–102; admin.php resetTables handler | kept+fixed (FK-safe child-first wipe; was a blank-500 lockout defect found by T8) | `ResetTablesReseedTest::testResetTablesTruncatesAndReseedsBcryptAdmin` — see security-regression.md #20 |
| 26 | LogOut destroys session + cookie | logout section, L237–242 | kept | `SessionHardeningTest::testLogoutExpiresCookieAndRejectsOldSession` |
| 27 | Audit viewer panel (recent destructive actions) | new addition surface | kept | `AuditLogTest::testAuditRecentViewerReturnsEscapedRows` |
| 28 | Wrong-credentials login bounce `Wrong Username or Password!` | login_check table, L61 | kept | PASS (E2E 2026-08-24) — live POST admin/nope → 302 `login.php?user_msg=Wrong+Username+or+Password%21`, login.php echoes string verbatim; automated branch unchanged (`SessionHardeningTest::testEmptyInputLoginRedirectsToLoginNotAdmin`) |

## Approved additions (v1 scope)

| # | Addition | v1 status | Proof |
|---|---|---|---|
| 29 | CSV results export (top/all scopes, percentage, filename contract) | new | `ExportResultsTest` (5 methods incl. ordering + auth redirect + unknown-quiz rejection) |
| 30 | Quiz progress counter wired to boot island | new | `ProgressIndicatorTest::testQuizPageRendersProgressSpanAndBootTotal` + `::testQuizJsCountsAnsweredRadioGroupsIntoProgressSpan` |
| 31 | JSON question-bank import/export | new | `ImportExportQuestionsTest` (6 methods: shape contract, round-trip, invalid rejection, collision ladder, audits, auth) |
| 32 | Branding config (`SITE_NAME`, `SITE_LOGO`, `FOOTER_HTML`) | new | `DbConnectTest::testConfigDefinesExpectedConstants`; rendered copy: PASS (E2E 2026-08-24) — live pages show `<title>Programming Quiz System - Admin Login</title>` (SITE_NAME), `img/header.jpg` header on index/admin (SITE_LOGO), footer FOOTER_HTML slot echoes configured value (empty string → renders nothing, per lib/views/footer.php:11) |
| 33 | Audit log for destructive actions | new | `AuditLogTest` (2 methods) + `ImportExportQuestionsTest::testBothImportOutcomesAreAudited` + `ResetPasswordTest::testSuccessfulResetChangesHashAndWritesAuditRow` |
| 34 | Admin-assisted password reset | new | `ResetPasswordTest` (3 methods) |
| 35 | Responsive/a11y pass (labels, focus outlines, overflow containment) | new | PASS (E2E 2026-08-24, static CSS method — curl cannot measure pixels) — containment blocks verified: `overflow-x:auto` + `max-width:100%` on wide tables (master.css:253-258, login.css:245-250, admin.css:504-509), login card `max-width:calc(100vw - 16px)` (login.css:72), `:focus-visible` outline (addNewQuiz.css:218); question-form inline widths all clamped (`width:400px;max-width:100%` textareas, `width:100%;max-width:780px` tables) — no unclamped >viewport width remains; commits 1d65b81, 46abeb3 |

## Cross-cutting correctness (bug-for-bug repairs)

| # | Item | Legacy ref | Status | Proof |
|---|---|---|---|---|
| 36 | Strict-mode INSERT fatals (admin.php:87 / quiz.php:43 / addNewQuiz.php:26) | global quirks L28 | kept+fixed | `MigrationTest::testStrictModeDefaultsSurviveHttpQuestionCreate`, `UiCreatedQuizListableTest`, resubmission E2E (#4) |
| 37 | Duplicate taker rows impossible | result.php double-submit era | kept+fixed | `MigrationTest::testDuplicateTakerInsertRejectedByUniqueKey` |
| 38 | Multi-write flows transactional (create/edit/delete question, delete quiz cascade) | legacy partial-write risk | kept+fixed | `TransactionTest` (5 methods) |
| 39 | Stored values stay RAW (escape moved to output) | global quirks L34 escaping style | kept+fixed | `EscapingTest::testStoredValuesAreRawAfterHttpCreateFlows` |
| 40 | `\ ` backslash artifacts removed from all user messages | global quirks L31 | kept+fixed | `CorrectnessFixesTest::testUserMessagesCarryNoBackslashArtifacts` (5 flows) |
| 41 | Literal `'0'` passes required-field validation | admin validation strings, L100–103 | kept+fixed | `CorrectnessFixesTest::testCreateQuestionRejectsEmptyDescButAcceptsLiteralZero` + `::testEditQuestion…` |
| 42 | Answers fetched in exactly one query on quiz render | perf work phase 7 | kept | `QuizQueryCountTest::testQuizRenderHitsAnswersTableExactlyOnce` |
| 43 | Page structure parity (wrapper regions, AJAX fragments) | whole baseline | kept | `StructureParityTest` (snapshots, 13 data sets) + `QuizRenderParityTest` |
| 44 | Right-click disable, video overlay markup, favicon set | global quirks L32–33 | kept | PASS (E2E 2026-08-24, static presence) — `contextmenu` guards live in index.js + login.js (externalized, commit 36cb0fc), `open_overlay()` wired from footer.php:15 with handler in admin.js, faviconit set present on disk and linked via lib/views/favicon.php |
