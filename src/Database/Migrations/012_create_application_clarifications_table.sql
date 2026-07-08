CREATE TABLE IF NOT EXISTS `application_clarifications` (
    `id` VARCHAR(36) PRIMARY KEY,
    `application_id` VARCHAR(36) NOT NULL,
    `reviewer_user_id` VARCHAR(36) NOT NULL,
    `question` TEXT NOT NULL,
    `response` TEXT NULL,
    `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `responded_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_clarifications_application` (`application_id`),
    CONSTRAINT `fk_clarifications_application` FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`),
    CONSTRAINT `fk_clarifications_reviewer` FOREIGN KEY (`reviewer_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
