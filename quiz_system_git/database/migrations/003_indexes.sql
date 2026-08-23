-- 003_indexes.sql
-- Secondary indexes for the hot join/filter paths.
ALTER TABLE answers
  ADD KEY idx_answers_question_id (question_id);

ALTER TABLE questions
  ADD KEY idx_questions_quiz_id (quiz_id);

ALTER TABLE quiz_takers
  ADD KEY idx_takers_quiz_id (quiz_id);
