CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NULL,
    `action` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(255) NOT NULL,
    `entity_id` VARCHAR(36) NULL,
    `old_value` JSON NULL,
    `new_value` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(512) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
