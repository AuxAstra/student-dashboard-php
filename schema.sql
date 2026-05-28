
-- SQL schema checked by Liatin Locsin
-- Users table (login + profile)
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_no VARCHAR(20)  NOT NULL UNIQUE,
    name       VARCHAR(100) NOT NULL,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    birthdate  DATE,
    age        INT,
    gender     VARCHAR(10),
    address    TEXT,
    email      VARCHAR(100),
    phone      VARCHAR(20),
    guardian         VARCHAR(100),
    guardian_rel     VARCHAR(50),
    guardian_contact VARCHAR(20),
    grade        INT,
    section      VARCHAR(50),
    track        VARCHAR(100),
    strand       VARCHAR(50),
    gpa          DECIMAL(3,2),
    status       VARCHAR(20) DEFAULT 'Active',
    enrolled_at  DATE
);

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    code     VARCHAR(20)  NOT NULL,
    name     VARCHAR(100) NOT NULL,
    teacher  VARCHAR(100),
    units    INT,
    schedule VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Grades table
CREATE TABLE IF NOT EXISTS grades (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    prelim  INT,
    midterm INT,
    final   INT,
    grade   INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample Data

-- Password is: admin123  (plain text for now — no hashing yet)
INSERT INTO users (student_no, name, username, password, birthdate, age, gender, address, email, phone, guardian, guardian_rel, guardian_contact, grade, section, track, strand, gpa, status, enrolled_at)
VALUES (
    'STU-2024-0001', 'Maria Santos', 'admin', 'admin123',
    '2008-03-15', 16, 'Female',
    '123 Rizal St., Bacolod City, Negros Occidental',
    'maria.santos@philtech.edu.ph', '09171234567',
    'Rosa Santos', 'Mother', '09179876543',
    11, 'Sampaguita', 'Academic Track', 'STEM',
    1.25, 'Active', '2024-06-10'
);

-- Subjects for user_id = 1
INSERT INTO subjects (user_id, code, name, teacher, units, schedule) VALUES
(1, 'MATH101', 'General Mathematics',    'Mr. Batumbakal',    4, 'MWF 7:30-8:30'),
(1, 'ENG101',  'Oral Communication',     'Ms. Flores',        2, 'TTH 9:00-10:00'),
(1, 'SCI101',  'Earth and Life Science', 'Ms. Lim',           4, 'MWF 10:00-11:00'),
(1, 'FIL101',  'Komunikasyon',           'Mr. Ramos',         2, 'TTH 1:00-2:00'),
(1, 'PE101',   'Physical Education',     'Coach Delos Reyes', 2, 'WF 2:00-3:00'),
(1, 'HIST101', 'Philippine History',     'Ms. Bautista',      3, 'MWF 1:00-2:00');

-- Grades for user_id = 1
INSERT INTO grades (user_id, subject, prelim, midterm, final, grade) VALUES
(1, 'General Mathematics',    88, 91, 90, 90),
(1, 'Oral Communication',     92, 89, 94, 92),
(1, 'Earth and Life Science', 85, 87, 88, 87),
(1, 'Komunikasyon',           90, 92, 91, 91),
(1, 'Physical Education',     95, 97, 96, 96),
(1, 'Philippine History',     82, 85, 84, 84);
