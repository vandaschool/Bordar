CREATE TABLE IF NOT EXISTS `tasks` (
    `id` VARCHAR(36) PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `company_id` VARCHAR(36) NOT NULL,
    `assigned_to_user_id` VARCHAR(36) NULL,
    `status` ENUM('TODO', 'IN_PROGRESS', 'DONE', 'BLOCKED') DEFAULT 'TODO',
    `due_date` DATETIME NULL,
    `priority` ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_tasks_company` (`company_id`),
    KEY `idx_tasks_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_tasks_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
