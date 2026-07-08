CREATE TABLE IF NOT EXISTS `mentors` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) UNIQUE NOT NULL,
    `bio` TEXT NULL,
    `expertise` JSON NULL,
    `availability` JSON NULL,
    `session_length_minutes` INT DEFAULT 45,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mentors_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
