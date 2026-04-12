-- ============================================================
-- PHP CRUD Student Registration System - Database Schema
-- ============================================================

-- Step 1: Create the database
CREATE DATABASE IF NOT EXISTS student_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Step 2: Use the database
USE student_db;

-- Step 3: Create the students table
CREATE TABLE IF NOT EXISTS students (
    id              INT(11)         NOT NULL AUTO_INCREMENT,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    roll_no         VARCHAR(50)     NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    contact_number  VARCHAR(15)     NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- End of schema
-- ============================================================
