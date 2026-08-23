-- 006_drop_denorm.sql -- drop the transitional denormalized id copies.
-- quizes.quiz_id mirrored the auto-increment id (legacy default 0 made
-- UI-created quizzes invisible to the questions.* JOINs) and
-- questions.question_id mirrored questions.id; both are superseded by the
-- real keys: quizes.id / questions.id plus questions.quiz_id FK (004).
ALTER TABLE quizes
  DROP COLUMN quiz_id;

ALTER TABLE questions
  DROP COLUMN question_id;
