CREATE DATABASE IF NOT EXISTS fussdb;
USE fussdb;

CREATE TABLE IF NOT EXISTS students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(100) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  degree VARCHAR(100),
  college VARCHAR(100),
  academic_year INT,
  bio TEXT,
  profile_picture VARCHAR(200),
  fuss_credits DECIMAL(6,2) DEFAULT 0,
  active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS skills (
  skill_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50),
  description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  skill_id INT NOT NULL,
  role VARCHAR(20),
  details TEXT,
  FOREIGN KEY (student_id) REFERENCES students(student_id),
  FOREIGN KEY (skill_id)   REFERENCES skills(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  requester_id INT NOT NULL,
  provider_id  INT NOT NULL,
  skill_id     INT NOT NULL,
  hours        DECIMAL(4,2) NOT NULL,
  fuss_credit_amount DECIMAL(6,2) NOT NULL,
  status       VARCHAR(20) NOT NULL DEFAULT 'pending',
  FOREIGN KEY (requester_id) REFERENCES students(student_id),
  FOREIGN KEY (provider_id)  REFERENCES students(student_id),
  FOREIGN KEY (skill_id)     REFERENCES skills(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS flagged_content (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content_type VARCHAR(50),  
  content TEXT,
  reported_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reported_by) REFERENCES students(student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE transactions
  MODIFY COLUMN status ENUM(
    'pending','accepted','rejected','proposed',
    'confirm_requester','confirm_provider','confirmed'
  ) NOT NULL DEFAULT 'pending';

ALTER TABLE transactions
  ADD COLUMN IF NOT EXISTS proposed_hours DECIMAL(4,2) NULL AFTER hours;

CREATE TABLE IF NOT EXISTS service_requests (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  requester_id     INT NOT NULL,
  provider_id      INT NOT NULL,
  skill_id         INT NOT NULL,
  requested_hours  DECIMAL(4,2) NOT NULL,
  proposed_hours   DECIMAL(4,2) DEFAULT NULL,
  status           ENUM(
                     'pending',
                     'accepted',
                     'in_progress',
                     'confirm_provider',
                     'confirm_requester',
                     'complete',
                     'rejected'
                   ) DEFAULT 'pending',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sr_requester (requester_id),
  INDEX idx_sr_provider  (provider_id),
  INDEX idx_sr_skill     (skill_id),
  INDEX idx_sr_status    (status),
  CONSTRAINT fk_sr_requester FOREIGN KEY (requester_id) REFERENCES students(student_id),
  CONSTRAINT fk_sr_provider  FOREIGN KEY (provider_id)  REFERENCES students(student_id),
  CONSTRAINT fk_sr_skill     FOREIGN KEY (skill_id)     REFERENCES skills(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  request_id  INT NOT NULL,
  sender_id   INT NOT NULL,
  body        TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sm_request (request_id, created_at),
  CONSTRAINT fk_sm_request FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_sm_sender  FOREIGN KEY (sender_id)  REFERENCES students(student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  sender_id INT NOT NULL,
  body VARCHAR(1200) NOT NULL,
  type ENUM('text','proposal','system') NOT NULL DEFAULT 'text',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
  FOREIGN KEY (sender_id)     REFERENCES students(student_id),
  INDEX (transaction_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  reviewee_id INT NOT NULL,
  stars TINYINT NOT NULL CHECK (stars BETWEEN 1 AND 5),
  comment VARCHAR(400) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (transaction_id, reviewer_id),
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
  FOREIGN KEY (reviewer_id)    REFERENCES students(student_id),
  FOREIGN KEY (reviewee_id)    REFERENCES students(student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE VIEW student_ratings AS
SELECT
  reviewee_id AS student_id,
  AVG(stars)  AS avg_rating,
  COUNT(*)    AS rating_count
FROM reviews
GROUP BY reviewee_id;

CREATE TABLE IF NOT EXISTS conversations (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  a_id INT NOT NULL,    
  b_id INT NOT NULL,          
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pair (a_id, b_id),
  INDEX idx_updated (updated_at),
  FOREIGN KEY (a_id) REFERENCES students(student_id),
  FOREIGN KEY (b_id) REFERENCES students(student_id)
);

CREATE TABLE IF NOT EXISTS pm_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  body VARCHAR(2000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL DEFAULT NULL,
  INDEX ix_conv_time (conversation_id, created_at),
  INDEX ix_conv_read (conversation_id, read_at),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id),
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES students(student_id)
);

CREATE TABLE IF NOT EXISTS message_reads (
  user_id         INT NOT NULL,
  transaction_id  INT NOT NULL,
  last_seen_at    DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY (user_id, transaction_id),
  FOREIGN KEY (user_id)        REFERENCES students(student_id),
  FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);

CREATE TABLE IF NOT EXISTS conversations (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  a_id INT NOT NULL,
  b_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX ix_a (a_id), INDEX ix_b (b_id),
  CONSTRAINT fk_conv_a FOREIGN KEY (a_id) REFERENCES students(student_id),
  CONSTRAINT fk_conv_b FOREIGN KEY (b_id) REFERENCES students(student_id),
  UNIQUE KEY uniq_pair (a_id, b_id) 
);
CREATE TABLE IF NOT EXISTS zones (
  zone_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

ALTER TABLE students
  ADD COLUMN zone_id INT NULL,
  ADD CONSTRAINT fk_students_zone
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id);

INSERT INTO zones (name) VALUES
  ('Hub'),
  ('Library'),
  ('Engineering'),
  ('Health Sciences'),
  ('Bedford Park Central'),
  ('Sturt'),
  ('Law & Commerce'),
  ('Tonsley')
ON DUPLICATE KEY UPDATE name = VALUES(name);

    
 