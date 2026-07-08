CREATE TABLE IF NOT EXISTS `application_reviewers` (
    `application_id` VARCHAR(36) NOT NULL,
    `reviewer_user_id` VARCHAR(36) NOT NULL,
    `status` ENUM('ASSIGNED', 'DRAFT', 'SUBMITTED') DEFAULT 'ASSIGNED',
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `score` DECIMAL(5, 2) NULL,
    `feedback` TEXT NULL,
    `conflict_of_interest` BOOLEAN DEFAULT FALSE,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`application_id`, `reviewer_user_id`),
    KEY `idx_app_reviewers_reviewer` (`reviewer_user_id`),
    CONSTRAINT `fk_app_reviewers_application` FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`),
    CONSTRAINT `fk_app_reviewers_reviewer` FOREIGN KEY (`reviewer_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
