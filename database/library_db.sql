-- ============================================================
-- Library Management System - Full Database Schema v3.0
-- NEW: PasswordResetOTP table, login brute-force columns,
--      avatar upload dir note, migration-safe ALTER statements
-- Run in phpMyAdmin → Import, or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `school`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `school`;

-- ------------------------------------------------------------
-- 1. Accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Accounts` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`               VARCHAR(20)  NOT NULL UNIQUE,
  `fullname`              VARCHAR(100) NOT NULL,
  `email`                 VARCHAR(150) NOT NULL UNIQUE,
  `phone`                 VARCHAR(20)  NOT NULL UNIQUE,
  `password`              VARCHAR(255) NOT NULL,
  `is_admin`              TINYINT(1)   NOT NULL DEFAULT 0,
  `status`                ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `profile_image`         VARCHAR(255)  DEFAULT NULL,
  `failed_login_attempts` TINYINT(3)   NOT NULL DEFAULT 0,
  `login_locked_until`    DATETIME      DEFAULT NULL,
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`            DATETIME      DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration: safe ALTER for existing installs
ALTER TABLE `Accounts`
  ADD COLUMN IF NOT EXISTS `profile_image`         VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `failed_login_attempts` TINYINT(3)  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `login_locked_until`    DATETIME    DEFAULT NULL;

-- ------------------------------------------------------------
-- 2. ManageBooksAdmin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ManageBooksAdmin` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `BookName`         VARCHAR(200) NOT NULL,
  `Author`           VARCHAR(150) NOT NULL,
  `ISBN`             VARCHAR(30)  NOT NULL UNIQUE,
  `Category`         VARCHAR(100) NOT NULL DEFAULT 'General',
  `ASCription`      TEXT          DEFAULT NULL,
  `CoverImage`       VARCHAR(255)  DEFAULT NULL,
  `TotalCopies`      INT          NOT NULL DEFAULT 1,
  `AvailableCopies`  INT          NOT NULL DEFAULT 1,
  `is_archived`      TINYINT(1)   NOT NULL DEFAULT 0,
  `added_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. BorrowBook
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `BorrowBook` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `BookName`     VARCHAR(200) NOT NULL,
  `Author`       VARCHAR(150) NOT NULL,
  `ISBN`         VARCHAR(30)  NOT NULL,
  `Name`         VARCHAR(100) NOT NULL,
  `StudentID`    VARCHAR(20)  NOT NULL,
  `Email`        VARCHAR(150) NOT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `BorrowDate`   DATE          DEFAULT NULL,
  `ReturnDate`   DATE         NOT NULL,
  `Status`       ENUM('Pending','Approved','Rejected','Returned') NOT NULL DEFAULT 'Pending',
  `RejectReason` VARCHAR(255)  DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`Email`),
  INDEX `idx_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Request_Book
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Request_Book` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `Name`         VARCHAR(100) NOT NULL,
  `Email`        VARCHAR(150) NOT NULL,
  `BookTitle`    VARCHAR(200) NOT NULL,
  `Author`       VARCHAR(150) NOT NULL,
  `Reason`       TEXT         NOT NULL,
  `Status`       ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `RejectReason` VARCHAR(255)  DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Contacts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `Name`       VARCHAR(100) NOT NULL,
  `Email`      VARCHAR(150) NOT NULL,
  `Message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. ADMIN_SYLLABUS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ADMIN_SYLLABUS` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `Course_Name`  VARCHAR(200) NOT NULL,
  `Semester`     VARCHAR(50)  NOT NULL,
  `Subject_Name` VARCHAR(200) NOT NULL,
  `Course_PDF`   VARCHAR(255) NOT NULL,
  `uploaded_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. ActivityLog
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ActivityLog` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      VARCHAR(20)  DEFAULT NULL,
  `action`       VARCHAR(255) NOT NULL,
  `target_table` VARCHAR(100) DEFAULT NULL,
  `target_id`    INT           DEFAULT NULL,
  `ip_address`   VARCHAR(45)  DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. Notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    VARCHAR(20)  NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. PasswordResetOTP  ← NEW
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `PasswordResetOTP` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `email`       VARCHAR(150) NOT NULL,
  `otp_hash`    VARCHAR(255) NOT NULL,
  `attempts`    TINYINT(3)   NOT NULL DEFAULT 0,
  `expires_at`  DATETIME     NOT NULL,
  `used`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT IGNORE INTO `ManageBooksAdmin`
  (`BookName`, `Author`, `ISBN`, `Category`, `ASCription`, `TotalCopies`, `AvailableCopies`)
VALUES
  ('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', 'Computer Science',
   'A comprehensive textbook on algorithms and data structures.', 5, 5),
  ('Clean Code', 'Robert C. Martin', '978-0132350884', 'Software Engineering',
   'A handbook of agile software craftsmanship.', 3, 3),
  ('The Pragmatic Programmer', 'Andrew Hunt', '978-0201616224', 'Software Engineering',
   'From journeyman to master — timeless advice for programmers.', 4, 4),
  ('Database System Concepts', 'Abraham Silberschatz', '978-0073523323', 'Database',
   'Comprehensive coverage of database systems.', 6, 6),
  ('Computer Networks', 'Andrew S. Tanenbaum', '978-0132126953', 'Computer Networks',
   'A top-down approach to computer networking.', 4, 4),
  ('Operating System Concepts', 'Abraham Silberschatz', '978-1118063330', 'Operating Systems',
   'The dinosaur book — foundation of OS concepts.', 5, 5),
  ('Artificial Intelligence: A Modern Approach', 'Stuart Russell', '978-0134610993', 'Artificial Intelligence',
   'The leading AI textbook used worldwide.', 3, 3),
  ('Design Patterns', 'Gang of Four', '978-0201633610', 'Software Engineering',
   'Elements of reusable object-oriented software.', 4, 4);
