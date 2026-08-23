# Performance notes

## Quiz page answer loading — N+1 removal (T7 / phase 7)

Scope: `quiz.php` answer loading for one default-quiz render
(`LEVEL2(HARD)` quiz id 2; seeded scale from `database/debug-v2.sql`:
**31 questions / 116 answers**, default quiz displays its **9** questions).

### What changed

Before: the render loop issued one
`SELECT * FROM answers WHERE question_id=:id ORDER BY rand()` per displayed
question. After: a single batched
`fetch_answers_by_question_ids()` call (`lib/render.php`, chunked `IN()`,
same helper the admin AJAX renderers use); random-per-question order is
preserved by shuffling each question's fetched rows in PHP instead of
`ORDER BY rand()` in SQL. Grading/timer flow untouched.

### Measured query counts (MariaDB general_log, one HTTP render)

Measured by `tests/QuizQueryCountTest.php`: general_log is pointed at
`log_output=TABLE` while a `php -S` worker with `DB_NAME` pointed at a
scratch import of `debug-v2.sql` serves exactly one POST to `quiz.php`;
SELECTs against `answers` are counted from `mysql.general_log`.

| metric (per quiz render)                       | before refactor | after refactor |
| ---------------------------------------------- | --------------- | -------------- |
| SELECTs hitting `answers` (measured)           | 9               | **1**          |
| total statements issued by `quiz.php`          | 14              | 6              |
| `ORDER BY rand()` sorts on the answers path    | 9               | 0              |
| DB round trips on the answers path             | 9               | 1              |

(total-statement counts are from code inspection of the same code path;
the answers-SELECT row is the measured regression guard.)

### EXPLAIN evidence (seeded data, MariaDB 10.11)

Before — one of the nine per-question queries:

```sql
EXPLAIN SELECT * FROM answers WHERE question_id=26 ORDER BY rand();
```

```
type  possible_keys            key                      key_len  ref    rows  Extra
ref   idx_answers_question_id  idx_answers_question_id  4        const  4     Using temporary; Using filesort
```

Each of the nine calls uses `idx_answers_question_id` (added by
`database/migrations/003_indexes.sql`) but pays an extra temporary table +
filesort for the random ordering, plus one client round trip each.

After — the single batched query (9 question ids):

```sql
EXPLAIN SELECT * FROM answers WHERE question_id IN (20,21,23,25,26,27,29,31,32);
```

```
type  possible_keys            key   key_len  ref   rows  Extra
ALL   idx_answers_question_id  NULL  NULL     NULL  116   Using where
```

Honest reading: at 9-of-116 selectivity (~8% of a tiny table) the optimizer
deliberately prefers a single scan over a range walk — the index shows up as
`possible_keys` only. The batched query still drops the temporary/filesort
work and collapses nine round trips into one; per-query cost was never the
bottleneck at this scale. When the table outgrows the cache-friendly size,
the range plan is available without touching app code:

```sql
EXPLAIN SELECT * FROM answers FORCE INDEX (idx_answers_question_id)
WHERE question_id IN (20,21,23,25,26,27,29,31,32);
-- type=range, key=idx_answers_question_id, rows=36, Extra=Using index condition
```

### Production opcache note (native PHP deploy, no fpm/docker layer yet)

The built-in dev server never enables opcache; production PHP should run
with at least:

```ini
opcache.enable=1            ; CLI too if cron/scripts are hot paths: opcache.enable_cli=1
opcache.validate_timestamps=1
opcache.revalidate_freq=30  ; seconds between stat() checks for updated files
```

Keep `validate_timestamps=1` for this project's plain-file deploys so a
`git pull` is picked up within `revalidate_freq`; set it to `0` only with an
explicit reload step in the deploy script. `apc.enable_cli`-style guards are
not needed; there is no userland cache in use.
