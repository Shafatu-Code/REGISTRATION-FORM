-- Author: Yogyu Mohammed
-- Student Registration - database structure (no student data)
-- Works on MariaDB 10.2+ / MySQL 8.0.16+ (XAMPP)

CREATE DATABASE IF NOT EXISTS student_registration
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_registration;

CREATE TABLE IF NOT EXISTS students (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,            -- stored lower-case
    phone           CHAR(9)       NOT NULL,            -- 9-digit Cameroonian mobile, no +237
    matric_number   VARCHAR(15)   NOT NULL,            -- stored upper-case, e.g. SE2026001
    date_of_birth   DATE          NOT NULL,
    department      VARCHAR(60)   NOT NULL,
    level           SMALLINT UNSIGNED NOT NULL,        -- 100 ... 500
    gender          ENUM('female','male','prefer_not_to_say') NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,            -- password_hash() output (bcrypt)
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT uq_students_email   UNIQUE (email),
    CONSTRAINT uq_students_matric  UNIQUE (matric_number),
    CONSTRAINT chk_students_level  CHECK (level IN (100,200,300,400,500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The "information is accurate" checkbox is NOT stored: the form cannot be
-- submitted without it, so every row would hold the same value.

-- Check a saved record:
-- SELECT id, full_name, email, phone, matric_number, department, level, gender,
--        created_at, LEFT(password_hash, 7) AS hash_prefix FROM students;
