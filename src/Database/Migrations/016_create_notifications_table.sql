CREATE TABLE IF NOT EXISTS `notifications` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `type` ENUM(
        'APPLICATION_STATUS_UPDATE', 'MENTOR_SESSION_REMINDER', 'TASK_ASSIGNED',
        'PAYMENT_DUE', 'PAYMENT_RECEIVED', 'DOCUMENT_EXPIRED', 'SYSTEM_ALERT',
        'SUPPORT_TICKET_UPDATE', 'GENERAL_ANNOUNCEMENT', 'REVIEW_CLARIFICATION_REQUEST'
    ) NOT NULL,
    `channel` ENUM('IN_APP', 'EMAIL', 'SMS') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `link` VARCHAR(2048) NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    KEY `idx_notifications_user` (`user_id`, `is_read`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    CONSTRAINT `fk_notifications_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
