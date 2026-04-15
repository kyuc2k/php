-- ============================================================
-- Database setup for PHP File Manager Web Application
-- Run this file once to initialize all required tables.
-- ============================================================

-- Create and select database
CREATE DATABASE IF NOT EXISTS `google_login`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `google_login`;

-- ============================================================
-- Table: users
-- Stores all registered users (email/password + Google OAuth)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                        INT AUTO_INCREMENT PRIMARY KEY,
    `google_id`                 VARCHAR(100)  DEFAULT NULL,
    `name`                      VARCHAR(255)  NOT NULL,
    `email`                     VARCHAR(255)  NOT NULL UNIQUE,
    `password`                  VARCHAR(255)  DEFAULT NULL,          -- NULL for Google-only accounts
    `avatar`                    VARCHAR(500)  DEFAULT NULL,          -- Google avatar URL
    `email_verified`            TINYINT(1)    NOT NULL DEFAULT 0,
    `verification_code`         VARCHAR(10)   DEFAULT NULL,
    `verification_code_expires` DATETIME      DEFAULT NULL,
    `session_token`             VARCHAR(100)  DEFAULT NULL,          -- For single-session enforcement
    `storage_limit`             BIGINT        NOT NULL DEFAULT 5242880, -- Default 5MB in bytes
    `created_at`                DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: uploads
-- Stores uploaded PDF files for each user
-- ============================================================
CREATE TABLE IF NOT EXISTS `uploads` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT           NOT NULL,
    `file_name`   VARCHAR(255)  NOT NULL,
    `file_path`   VARCHAR(500)  NOT NULL,
    `file_size`   BIGINT        NOT NULL DEFAULT 0,
    `uploaded_at` DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: payments
-- Stores VNPay payment transactions and upgrade history
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT           NOT NULL,
    `plan`           VARCHAR(10)   NOT NULL,                          -- e.g. '1gb', '2gb'
    `amount`         INT           NOT NULL,                          -- Amount actually paid (VNĐ)
    `storage_bytes`  BIGINT        NOT NULL,                          -- Storage granted in bytes
    `order_id`       VARCHAR(100)  NOT NULL UNIQUE,                   -- VNPay vnp_TxnRef
    `request_id`     VARCHAR(100)  NOT NULL,
    `transaction_id` VARCHAR(100)  DEFAULT NULL,                      -- VNPay vnp_TransactionNo
    `status`         ENUM('pending','completed','failed') DEFAULT 'pending',
    `created_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    `completed_at`   DATETIME      DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
