CREATE TABLE IF NOT EXISTS `support_ticket_messages` (
    `id` VARCHAR(36) PRIMARY KEY,
    `ticket_id` VARCHAR(36) NOT NULL,
    `sender_id` VARCHAR(36) NOT NULL,
    `message` TEXT NOT NULL,
    `is_internal` BOOLEAN DEFAULT FALSE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ticket_messages_ticket` (`ticket_id`),
    CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`),
    CONSTRAINT `fk_ticket_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
