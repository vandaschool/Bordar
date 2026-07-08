CREATE TABLE IF NOT EXISTS `companies` (
    `id` VARCHAR(36) PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `registration_number` VARCHAR(255) UNIQUE NULL,
    `owner_user_id` VARCHAR(36) NOT NULL,
    `industry` VARCHAR(255) NULL,
    `country` VARCHAR(255) NULL,
    `city` VARCHAR(255) NULL,
    `website` VARCHAR(2048) NULL,
    `description` TEXT NULL,
    `hs_codes` JSON NULL,
    `status` ENUM('ACTIVE', 'INACTIVE', 'PENDING') DEFAULT 'PENDING',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_companies_owner` (`owner_user_id`),
    KEY `idx_companies_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_companies_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
