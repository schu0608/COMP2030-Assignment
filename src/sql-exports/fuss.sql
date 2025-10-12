-- Create DB (idempotent) and set sane defaults
CREATE DATABASE IF NOT EXISTS fussdb
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE fussdb;

-- Students
CREATE TABLE IF NOT EXISTS students (
  student_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email            VARCHAR(100) NOT NULL UNIQUE,
  password         VARCHAR(255) NOT NULL,                         -- store bcrypt/argon hashes
  full_name        VARCHAR(100) NOT NULL,
  degree           VARCHAR(100),
  college          VARCHAR(100),
  academic_year    TINYINT UNSIGNED,                               -- 1..8 typically
  bio              TEXT,
  profile_picture  VARCHAR(255),
  fuss_credits     DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,    -- cannot be negative
  active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_students_active (active),
  INDEX idx_students_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Skills (normalize name+category uniqueness)
CREATE TABLE IF NOT EXISTS skills (
  skill_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  category     VARCHAR(50),                    -- e.g. Academic, Technical, Practical, etc.
  description  TEXT,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_skill_name_category (name, category),
  INDEX idx_skills_category (category),
  INDEX idx_skills_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student ↔ Skill mapping (offered/requested)
CREATE TABLE IF NOT EXISTS student_skills (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id  INT UNSIGNED NOT NULL,
  skill_id    INT UNSIGNED NOT NULL,
  role        ENUM('offered','requested') NOT NULL,        -- clearer than free text
  details     TEXT,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_ss_student  FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ss_skill    FOREIGN KEY (skill_id)   REFERENCES skills(skill_id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  UNIQUE KEY uq_student_skill_role (student_id, skill_id, role),
  INDEX idx_ss_role (role),
  INDEX idx_ss_skill (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Credit transactions (service completion/transfer)
CREATE TABLE IF NOT EXISTS transactions (
  transaction_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requester_id       INT UNSIGNED NOT NULL,     -- pays credits
  provider_id        INT UNSIGNED NOT NULL,     -- receives credits
  skill_id           INT UNSIGNED NOT NULL,
  hours              DECIMAL(6,2) UNSIGNED NOT NULL,          -- >= 0
  fuss_credit_amount DECIMAL(8,2) UNSIGNED NOT NULL,          -- usually = hours
  status             ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at       TIMESTAMP NULL DEFAULT NULL,

  CONSTRAINT fk_tx_requester FOREIGN KEY (requester_id) REFERENCES students(student_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tx_provider  FOREIGN KEY (provider_id) REFERENCES students(student_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_tx_skill     FOREIGN KEY (skill_id)     REFERENCES skills(skill_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  -- prevent self-deal
  CONSTRAINT chk_tx_self CHECK (requester_id <> provider_id),

  INDEX idx_tx_status (status),
  INDEX idx_tx_req (requester_id),
  INDEX idx_tx_prov (provider_id),
  INDEX idx_tx_skill (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Flagged content for moderation
CREATE TABLE IF NOT EXISTS flagged_content (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type  ENUM('profile','skill','message') NOT NULL,
  content       TEXT NOT NULL,
  reported_by   INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_flag_reporter FOREIGN KEY (reported_by) REFERENCES students(student_id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX idx_flag_type (content_type),
  INDEX idx_flag_reporter (reported_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional helpful views (read-only dashboards)
CREATE OR REPLACE VIEW v_skill_popularity AS
SELECT s.name, s.category, COUNT(*) AS total_offered
FROM student_skills ss
JOIN skills s ON s.skill_id = ss.skill_id
WHERE ss.role = 'offered'
GROUP BY s.skill_id, s.name, s.category
ORDER BY total_offered DESC;

CREATE OR REPLACE VIEW v_student_balances AS
SELECT student_id, full_name, email, fuss_credits, active
FROM students;
