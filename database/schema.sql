-- Conference Room Reservation System Database Schema
-- Single Conference Room (DIWA Center Conference Room)

-- CREATE DATABASE IF NOT EXISTS `conference_reservation` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `conference_reservation`;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users (Authenticated Google / UP Mail Users)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `google_sub` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_google_sub` (`google_sub`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reservations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `requester_name` VARCHAR(150) NOT NULL,
  `requester_email` VARCHAR(255) NOT NULL,
  `project_team_office` VARCHAR(255) NOT NULL,
  `purpose` TEXT NOT NULL,
  `reservation_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('CONFIRMED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
  `rejection_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_date_status` (`reservation_date`, `status`),
  INDEX `idx_date` (`reservation_date`),
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table: email_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT UNSIGNED NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `email_type` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `status` ENUM('SENT', 'FAILED') NOT NULL,
  `error_message` TEXT NULL,
  `sent_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_email_logs_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: system_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Default System Settings
-- --------------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('organization_name', 'DIWA Center Conference Services'),
('organization_email', 'no-reply@diwacenter.example'),
('organization_phone', '+1 (555) 019-2831'),
('organization_address', 'DIWA Center, Main Office'),
('organization_website', 'https://www.diwacenter.example'),
('email_signature_html', '<p style="margin-top: 15px; margin-bottom: 10px; color: #475569;">Thank you.</p><p style="margin-top: 5px;"><img src="cid:email_signature" alt="Email Signature" style="max-width: 600px; width: 100%; height: auto; display: block; border: 0;"></p>'),
('email_signature_text', "Thank you.\n\nDIWA Center Conference Services\nDIWA Center, Main Office")
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);

-- --------------------------------------------------------
-- Seed Data: Default Administrator
-- Email: admin@diwaconf.com
-- Password: P@sSW0rd321
-- --------------------------------------------------------
INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `is_active`) VALUES
(1, 'Gail', 'admin@diwaconf.com', '$2y$10$AhTS9dFAI0SCfKv1SNoniOSUCjzlY3dPC.wyQEE4wgKAfHI/X.IwO', 1),
(2, 'System Administrator', 'admin@example.com', '$2y$10$489X5N7zl6XWuPzBgjq/oO8XTohkoFXJxfTo5l7VsGzxiFYxcj55S', 1)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `password_hash` = VALUES(`password_hash`);

