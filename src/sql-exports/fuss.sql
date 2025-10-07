CREATE DATABASE fussdb;
USE fussdb;

CREATE TABLE students (
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
);

CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    description TEXT
);

CREATE TABLE student_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    role VARCHAR(20), -- 'offered' or 'requested'
    details TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
);

CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    provider_id INT NOT NULL,
    skill_id INT NOT NULL,
    hours DECIMAL(4,2) NOT NULL,
    fuss_credit_amount DECIMAL(6,2) NOT NULL,
    status VARCHAR(20), -- 'pending' or 'confirmed'
    FOREIGN KEY (requester_id) REFERENCES students(student_id),
    FOREIGN KEY (provider_id) REFERENCES students(student_id),
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
);

CREATE TABLE flagged_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50),   -- 'profile', 'skill', 'message'
    content TEXT,
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES students(student_id)
);
