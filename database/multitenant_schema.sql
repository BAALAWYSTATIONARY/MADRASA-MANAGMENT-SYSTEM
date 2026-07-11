-- Multi-tenant schema for Madrasa System
-- Run these statements in your MySQL database for madrasa data isolation.

-- 1) Create the madrasa table
CREATE TABLE IF NOT EXISTS madrasas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 2) Add madrasa_id to users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER role,
  ADD INDEX idx_users_madrasa_id (madrasa_id);

-- 3) Add madrasa_id to students
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER parent_contact,
  ADD INDEX idx_students_madrasa_id (madrasa_id);

-- 4) Add madrasa_id to attendance
ALTER TABLE attendance
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER attendance_date,
  ADD INDEX idx_attendance_madrasa_id (madrasa_id);

-- 5) Add madrasa_id to memorization_records
ALTER TABLE memorization_records
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER memorization_date,
  ADD INDEX idx_memorization_madrasa_id (madrasa_id);

-- 6) Add madrasa_id to exams
ALTER TABLE exams
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER class,
  ADD INDEX idx_exams_madrasa_id (madrasa_id);

-- 7) Add madrasa_id to results
ALTER TABLE results
  ADD COLUMN IF NOT EXISTS madrasa_id INT NOT NULL AFTER student_id,
  ADD INDEX idx_results_madrasa_id (madrasa_id);

-- 8) Add optional foreign keys if you want referential integrity
-- Note: ensure all existing values are valid before enabling FKs
ALTER TABLE users
  ADD CONSTRAINT fk_users_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE students
  ADD CONSTRAINT fk_students_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE attendance
  ADD CONSTRAINT fk_attendance_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE memorization_records
  ADD CONSTRAINT fk_memorization_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE exams
  ADD CONSTRAINT fk_exams_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE results
  ADD CONSTRAINT fk_results_madrasa FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- 9) Ensure username uniqueness per madrasa
ALTER TABLE users
  ADD UNIQUE KEY unique_username_madrasa (username, madrasa_id);
