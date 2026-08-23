-- 002_constraints.sql
-- Dedupe first (keep lowest id), then enforce uniqueness.
-- Legacy seed contains one duplicate taker pair: (1139113, quiz 1) ids 7 and 13.
DELETE t FROM quiz_takers t
JOIN quiz_takers k
  ON k.username = t.username AND k.quiz_id = t.quiz_id AND k.id < t.id;

DELETE a FROM admins a
JOIN admins b ON b.username = a.username AND b.id < a.id;

ALTER TABLE admins
  ADD UNIQUE KEY uq_admins_username (username);

ALTER TABLE quiz_takers
  ADD UNIQUE KEY uq_takers_user_quiz (username, quiz_id);
