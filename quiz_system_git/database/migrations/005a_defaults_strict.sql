-- 005a_defaults_strict.sql
-- Safe defaults for every NOT NULL column omitted by the legacy INSERTs, so
-- they pass under default sql_mode (STRICT_TRANS_TABLES):
--   * admin.php create-question INSERT omits questions.question_id (~L86)
--   * quiz.php taker INSERT omits quiz_takers.marks (~L43)
--   * addNewQuiz.php INSERT omits quizes.quiz_id, total_questions,
--     set_default (~L26)
-- quiz_takers.duration gets a default too (brief-specified; result.php writes
-- it later). The denormalized *_id copies keep their legacy semantics: insert
-- with 0, then UPDATE to lastInsertId.
ALTER TABLE questions
  MODIFY question_id int(11) NOT NULL DEFAULT 0;

ALTER TABLE quizes
  MODIFY quiz_id int(11) NOT NULL DEFAULT 0,
  MODIFY total_questions int(11) NOT NULL DEFAULT 0,
  MODIFY set_default int(1) NOT NULL DEFAULT 0;

ALTER TABLE quiz_takers
  MODIFY marks int(11) NOT NULL DEFAULT 0,
  MODIFY duration int(11) NOT NULL DEFAULT 0;
