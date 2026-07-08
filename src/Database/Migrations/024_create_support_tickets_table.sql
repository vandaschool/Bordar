CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'ESCALATED', 'PENDING_USER_RESPONSE') DEFAULT 'OPEN',
    `priority` ENUM('LOW', 'MEDIUM', 'HIGH', 'URGENT') DEFAULT 'MEDIUM',
    `category` ENUM('REGISTRATION', 'PAYMENT', 'APPLICATION_FORM', 'DOCUMENTS', 'MENTORING_SESSION', 'ACCESS_ISSUE', 'TECHNICAL_ISSUE', 'REVIEW_APPEAL', 'OTHER') DEFAULT 'OTHER',
    `assigned_to_id` VARCHAR(36) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    KEY `idx_support_tickets_user` (`user_id`),
    KEY `idx_support_tickets_assigned` (`assigned_to_id`),
    CONSTRAINT `fk_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    CONSTRAINT `fk_support_tickets_assignee` FOREIGN KEY (`assigned_to_id`) REFERENCES `users`(`id`),
    CONSTRAINT `fk_support_tickets_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
