# Baseline Behavior Checklist — quiz_system_git (pre-revitalization)

Captured **bug-for-bug** on 2026-08-23 via scripted `curl` against
`php -S localhost:8090 -t quiz_system_git` (PHP 8.3.6 CLI server, MariaDB `debug`
DB with host-default `STRICT_TRANS_TABLES` sql_mode). Seed state: quizes
`1=LEVEL1(EASY)` (30 min, 20 shown), `2=LEVEL2(HARD)` (20 min, 10 shown,
`set_default=1`); 31 questions; admin user; sample takers.

Method: black-box requests only (`curl -is`, cookie jar for authed flow), plus
read-only DB SELECTs and PHP error-log capture to attribute fatal errors.
No app code was changed to produce this document.

> Environment note: DB credentials in `scripts/db.php` (root/'') cannot connect on
> this host (unix_socket auth). A temporary local-only patch to the seeded dev
> credentials was applied while testing and reverted before commit
> (`git diff scripts/db.php` verified empty).

Message strings are quoted exactly as emitted/received unless marked *(source)*.
Redirect targets are quoted verbatim from `Location:` headers.

---

## Global quirks

| Quirk | Detail |
|---|---|
| Fatal errors render as blank 500 | Uncaught PDOException → `HTTP/1.0 500 Internal Server Error` with **empty body** (display_errors off). Protocol note: fatals answer `HTTP/1.0`, normal pages `HTTP/1.1`. |
| Strict-mode INSERT failures | Host MariaDB runs `STRICT_TRANS_TABLES`. Legacy INSERTs omit NOT-NULL columns that historically coerced to `0` silently; here they abort. Three affected writes found (admin.php:87, quiz.php:43, addNewQuiz.php:26). |
| Inconsistent header casing | Success login sends `Location:admin.php` (no space after colon); most other redirects send lowercase `location:`. |
| Raw unencoded redirect query | `logout.php` concatenates a message containing spaces into the Location URL without `urlencode()` → literal spaces inside the header value. |
| Broken `\ '` artifacts | Several success/duplicate messages embed a literal backslash-space around interpolated names, e.g. `'Quiz, \ '.NAME.' \ has been created!'`. Visible verbatim in redirects (`%5C+` in encoded form). |
| Right-click disable | `document.addEventListener("contextmenu", ...)` present **only** in `login.php:80` and `quiz.php:341`. Not on index/admin/result. |
| Video overlay refs | `scripts/overlay.js`, `<video>` sources `videos/video.mp4` + `videos/video.webmhd.webm`, `open_overlay()`/`close_overlay()`, `#fade.black_overlay` present on index.php and quiz.php. No `videos/` dir exists in webroot → overlay would show broken player. |
| Message rendering | `index.php` echoes `user_msg` (GET or POST) htmlspecialchars'd into `div#enter_rollno`; `login.php` into a `<p>`; `admin.php` renders `msg` GET param into `<p id="msg">`. |

---

## index.php

- `GET /index.php` → `200 OK`.
- Default quiz block shows: `LEVEL2(HARD)` (in `<strong>`), and the line
  `You've got 20 mins for attempting 10 questions.` (from default quiz row id=2).
- Start form: `<form id="myForm" name="onlyForm" action="quiz.php" method="POST">`,
  input `name="rollno" placeholder="Enter Your Roll No." autofocus`.
  Submit is `<a href="javascript:submit();" class="myButton">Click Here to Begin</a>`.
- Client-side validation JS sets `document.getElementById("enter_rollno").innerHTML = "Please Enter Your Roll No.";` when rollno empty (no server round-trip).
- `GET /index.php?user_msg=WELCOME%20TEST%20MSG` → `200`; body renders `WELCOME TEST MSG` inside `div#enter_rollno`.

## login.php

- `GET /login.php` → `200 OK`.
- Form: `<form action="login_check.php" class="login" method="POST">`, fields
  `login` (text, autofocus) and `password` (password).
- `?user_msg=` rendered (htmlspecialchars'd) in a `<p>` under the form.
- Contextmenu (right-click) disabled via inline JS (see global quirks).

## login_check.php (POST target of login form)

| Scenario | Observed |
|---|---|
| Wrong creds (`login=admin&password=WRONGPASS`) | `302` → `location: login.php?user_msg=Wrong+Username+or+Password%21` |
| Correct creds (`login=admin&password=12345`) | `302` → `Location:admin.php`; session cookie set; `admins.last_login` updated to `now()` |
| Empty fields (`login=&password=`) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` — **lands on admin.php**, not back to login.php |
| No matching fields at all | Same as above |

## Authenticated admin flow (cookie jar)

- `GET /admin.php` authenticated → `200`, ~34 KB page.
- Sections: `<h2>True or false</h2>`, `<h2>Multiple Choice</h2>`.
- Forms present:
  - `addQuestion` (TF) → POST `admin.php`
  - `addMcQuestion` (MC) → POST `admin.php`
  - `deleteedit` (id) → POST `deleteSomeQues.php`
  - `regNewAdmin` (`reg_name`) → POST `register.php`
  - `regNewQuiz` (`newQuiz_name`) → POST `addNewQuiz.php`
- Quiz selector (`select#quizIDtf` / `quizIDmc`, name `quizID`) options: `1=LEVEL1(EASY)`, `2=LEVEL2(HARD)`.
- Hidden field renders as `<input type="hidden" value="" name="questionID">` (always empty).
- Menu area: `<p style="color:#06F;" id="msg">` renders `?msg=` value; greeting `Hello, <a href="admin.php"><span id="usr">admin!</span></a>`.
- Unauthenticated `GET /admin.php` → `302` → `location: login.php?user_msg=Please+Login+First%21` (via `session.php` require).

### Create TF question (authed POST admin.php)

Payload: `desc`, `code_desc`, `prog-lang=plain`, `answer1=True`, `answer2=False`,
`answer3=`, `answer4=`, `iscorrect=answer1|answer2`, `type=tf`, `quizID`, `questionID=`.

**Observed: `HTTP/1.0 500 Internal Server Error`, empty body. No question row created.**
Server log:

```
PHP Fatal error:  Uncaught PDOException: SQLSTATE[HY000]: General error: 1364 Field 'question_id' doesn't have a default value in .../admin.php:87
```

Cause note: the `INSERT INTO questions (...)` omits the NOT-NULL `question_id`
column (the app sets it afterwards via `UPDATE questions SET question_id=lastInsertId`).
Under this host's strict sql_mode the insert aborts before the UPDATE can run;
on legacy non-strict MySQL/MariaDB it silently inserted `0` and worked.

Validation strings (verbatim, returned as `200` plain echo):

- Missing `iscorrect`: `Sorry, important data to submit your question is missing. Please press back in your browser and try again and make sure you select a correct answer for the question.`
- Empty `answer1`/`answer2`: `Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.`

Success message *(source, currently unreachable due to fatal)*:
`Thanks, question no.<lastId> has been added` → `302 admin.php?msg=...`
(6 code branches share it).

### Create MC question (authed POST admin.php)

Fields as TF but `answer1..answer4` free text, one `iscorrect=answer1..4`, `type=mc`.

**Observed: identical `HTTP/1.0 500` empty body, same PDOException 1364 at admin.php:87. No row created.**

### AJAX handlers (POST admin.php, authed)

All return `200` with an HTML fragment intended for `div#quesans_table`:

| Handler (POST key=value) | Response shape |
|---|---|
| `usersQuiz=LEVEL2(HARD)` | Ranked results table: headers `Rank / Roll No. / Marks / Percentage / Time Taken / TimeStamp`, rows for that quiz's takers |
| `usersAll=LEVEL1(EASY)` | Same table shape across all quizzes |
| `questionsQuiz=LEVEL2(HARD)` | Table listing questions (one `<tr>` per question) |
| `deleteSomeQuestions=LEVEL2(HARD)` | Table with per-question checkboxes `name="qu<N>"` plus hidden `total_ques` (posts with the `deleteedit` form) |
| `editaquestion=LEVEL2(HARD)` | Table rows each with radio `name="editAQ" value="<questionID>"`, question text in `<pre class="question_style">`, code in `<pre class="brush: cpp;">` |
| `defaultQuiz=LEVEL2(HARD)` | Plain fragment: `Thanks! The quiz, /LEVEL2(HARD)/ has now been set as default.` (note slash-wrapped name) |

Unauthenticated AJAX POST → `302` → `login.php?user_msg=Please+Login+First%21`.

**Deliberately not exercised (destructive, would destroy seed data):**
`clearResult=<quizID>`, `resetTables=yes`, `deleteAdmin=<username>`,
`deleteQuiz=<name>`, `reset=yes`.

## quiz.php

| Scenario | Observed |
|---|---|
| `GET /quiz.php` (no rollno) | `302` → `location: index.php?user_msg=Hey%2C+This+is+the+start+Page%2C+So+enter+your+username+here+first` |
| `GET /quiz.php?rollno=GETTEST` | Identical redirect — rollno is accepted **only** via POST |
| `POST rollno=<fresh>` | `HTTP/1.0 500`, empty body, **no taker row created**. Log: `PDOException: ... 1364 Field 'marks' doesn't have a default value in .../quiz.php:43` (`INSERT INTO quiz_takers` omits `marks`; same strict-mode cause as above) |

Because taker creation always fatals, the question page is currently unreachable
through the UI, and the duplicate-attempt check never gets a chance to fire.
From source, once past the insert the page would render:

- N random questions of the default quiz (radios `name="rads<counter>"` with answer-id values)
- hidden fields `rollno`, `total_ques`, `total_time`, `quizID`
- countdown timer JS: recursive `setTimeout('timer('+secs+')',1000)`
- form: `<form id="quiz_form" name="quiz_form_name" action="result.php" method="POST">`
- contextmenu disabled (quiz.php:341), video overlay markup present

Duplicate-rollno guard *(source)*: checks `quiz_takers` for (username, quiz_id)
and redirects `Sorry, but <roll_no>, has already attempted the quiz, <quzz_name>!`

## result.php

No authentication of any kind; reachable directly.

| Scenario | Observed |
|---|---|
| First submission for taker `0987` (quiz 2, duration=0): `total_ques=9&rollno=0987&quizID=2&rads1=71(correct)&rads2=72&rads3=73&rads4=75(correct)&rads5=76(nonexistent)` | `200 OK`, page title `Result`, body renders `0987, You scored 2/9` (marks = count of submitted ids whose `answers.correct='1'`; unknown ids ignored). DB row updated: marks=2, percentage=22.222222222222, duration=393076961 |
| Resubmission (same rollno, duration now ≠ 0) | `302` → `location: index.php?user_msg=Sorry%2C+but+re-submission+of+the+quiz+isn%27t+allowed%21` |
| No fields | `302` → `Hey, This is the start Page!, So enter your username here first` (note comma after `Page!` — differs from quiz.php's variant) |
| `total_ques=0` | `302` → `Hey, Weird, but it seems the quiz had no questions!` |
| Required fields present but empty | `302` → `Hey, Something went wrong! Tell the Admin!!` |

Quirk: `duration` uses `TIMESTAMPDIFF(SECOND, date_time, now())`; seed takers date
from 2014, so recorded "time taken" values are ~39 million seconds.

Test-mutation disclosure: the `0987` row was updated by this test and then restored
to seed values (marks=0, percentage=0, duration=0).

## register.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` — **not** a login bounce; the page executes its logic unauthenticated |
| `POST login=baseline_newadmin&password=test123` (no session) | `302` → `admin.php?msg=Admin+account%2C+%5C+baseline_newadmin+%5C+has+been+created%21` — decodes to `Admin account, \ baseline_newadmin \ has been created!`. **Admin account actually created in DB** (verified by SELECT, then deleted to restore seed state) |

*(source)* duplicate username: `Sorry, but \ <name> \ is already taken!`

Security baseline: anyone can mint an admin account with one unauthenticated POST.

## changePassword.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` |
| `POST login=nosuchuser_xyz&password=newpass` (no session) | `302` → `admin.php?msg=You+can+only+change+your+own+password%21` (compares input against the *session's* username, which is unset here → mismatch; user existence never checked) |

*(source)* success string uses doubled backslashes: `Password Changed Successfully for \\` + name + `\`.

## addNewQuiz.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` |
| `POST quizName=BaselineQuizX&quizTime=10&numQues=5` (no session) | `HTTP/1.0 500`, empty body, **no quiz row created**. Log: `PDOException: ... 1364 Field 'quiz_id' doesn't have a default value in .../addNewQuiz.php:26` (`INSERT INTO quizes` omits its `quiz_id` column) |
| Duplicate-name path *(source, never reached live)* | `Sorry, but \ <name> \ already exists!` |
| Success path *(source, never reached live)* | `Quiz, \ <name> \ has been created!` |

## updateExistingQuiz.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` |
| `POST quizName=LEVEL1(EASY)&quizTime=30&numQues=20` (no session; same values as seed → no visible data change) | `302` → `admin.php?msg=Quiz%2C+%5C+LEVEL1%28EASY%29+%5C+has+been+updated%21` = `Quiz, \ LEVEL1(EASY) \ has been updated!` |

Security baseline: quiz time/question-count config mutable without any session.
Nonexistent name *(source)*: `Sorry, but \ <name> \ doesn't exist!`.

## deleteSomeQues.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` |
| `POST total_ques=0` (no session) | `302` → `admin.php?msg=Questions%2C+%5C++%5C+have+been+deleted%21` = `Questions, \  \ have been deleted!` (empty name between broken backslashes) |

Reads checkbox names `qu1..quN` keyed off client-supplied `total_ques`; deletion
happens unauthenticated (not exercised with real ids to preserve seed data).

## editaquest.php

| Scenario | Observed |
|---|---|
| `GET` (no fields, no session) | `302` → `admin.php?msg=Sorry%2C+but+Something+went+wrong` |
| `POST desc=…&iscorrect=answer1&type=tf&code_desc=&prog-lang=plain&answer1=True&answer2=False&questionID=999999` (no session) | `200 OK`, body echoes `Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.` — request processed **with no session check whatsoever** |

Per source, a fully-populated payload updates/creates the question by `questionID`
unauthenticated (not exercised further to avoid seed mutation).

## logout.php

- `GET /logout.php` (with valid session cookie) →
  `302` → `location: login.php?user_msg=You have successfully Logged out!`
  — raw spaces in the header value (not urlencoded); captured verbatim.
- Subsequent `GET /admin.php` with same jar → `302` → `login.php?user_msg=Please+Login+First%21`
  (session destroyed correctly).

## session.php

- Direct `GET /session.php` without session → `302` → `login.php?user_msg=Please+Login+First%21`.
- Also re-validates the stored username against the `admins` table on every include;
  stale/deleted usernames are bounced with the same message.

---

## Coverage map

index ✓ · login ✓ · login_check ✓ · admin (auth + AJAX + creates) ✓ · quiz ✓ ·
result ✓ · register ✓ · editaquest ✓ · addNewQuiz ✓ · updateExistingQuiz ✓ ·
deleteSomeQues ✓ · changePassword ✓ · logout ✓ · session ✓

## Test-data mutations & cleanup

- `admins`: unauth register created `baseline_newadmin` (id 4) → deleted after test.
- `quiz_takers`: result.php test updated `0987` (id 15) → restored to marks=0,
  percentage=0, duration=0.
- `quizes.time_allotted/display_questions`, `questions`, `answers`: unchanged
  (updateExistingQuiz POST used seed-identical values).

