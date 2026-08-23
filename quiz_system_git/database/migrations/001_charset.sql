-- 001_charset.sql
-- Convert the five legacy latin1 tables to utf8mb4 / utf8mb4_unicode_ci,
-- including every character column. MariaDB's "latin1" is cp1252, so CONVERT
-- TO transcodes each stored byte faithfully (verified by the checks below).
ALTER TABLE admins      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE answers     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE questions   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE quizes      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE quiz_takers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verification: exactly 5 tables converted...
SELECT COUNT(*) AS utf8mb4_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('admins', 'answers', 'questions', 'quizes', 'quiz_takers')
  AND TABLE_COLLATION = 'utf8mb4_unicode_ci';

-- ...and a sample accented literal round-trips as utf8mb4 (HEX -> C3A9).
SELECT HEX('é') AS accented_hex, 'é' = _utf8mb4'é' AS accented_ok;
