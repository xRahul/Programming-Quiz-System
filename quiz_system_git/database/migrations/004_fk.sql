-- 004_fk.sql
-- Purge orphaned rows first so the constraints can attach, then wire up the
-- referential graph. Legacy seed holds 4 answers pointing at deleted question
-- id 28; they are removed here by design.
DELETE a FROM answers a
LEFT JOIN questions q ON q.id = a.question_id
WHERE q.id IS NULL;

DELETE a FROM answers a
LEFT JOIN quizes z ON z.id = a.quiz_id
WHERE z.id IS NULL;

DELETE q FROM questions q
LEFT JOIN quizes z ON z.id = q.quiz_id
WHERE z.id IS NULL;

DELETE t FROM quiz_takers t
LEFT JOIN quizes z ON z.id = t.quiz_id
WHERE z.id IS NULL;

ALTER TABLE answers
  ADD CONSTRAINT fk_answers_quiz FOREIGN KEY (quiz_id) REFERENCES quizes (id) ON DELETE CASCADE;

ALTER TABLE answers
  ADD CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE;

ALTER TABLE questions
  ADD CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizes (id) ON DELETE CASCADE;

ALTER TABLE quiz_takers
  ADD CONSTRAINT fk_takers_quiz FOREIGN KEY (quiz_id) REFERENCES quizes (id) ON DELETE CASCADE;
