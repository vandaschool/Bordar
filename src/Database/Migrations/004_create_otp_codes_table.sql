CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `purpose` ENUM('EMAIL_VERIFY', 'PASSWORD_RESET') NOT NULL,
    `attempts` INT DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `consumed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_otp_codes_user_purpose` (`user_id`, `purpose`),
    CONSTRAINT `fk_otp_codes_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
