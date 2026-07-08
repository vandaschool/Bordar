CREATE TABLE IF NOT EXISTS `courses` (
    `id` VARCHAR(36) PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `cohort_id` VARCHAR(36) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    KEY `idx_courses_cohort` (`cohort_id`),
    CONSTRAINT `fk_courses_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`),
    CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
