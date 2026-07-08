CREATE TABLE IF NOT EXISTS `images` (
    `id` VARCHAR(36) PRIMARY KEY,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `file_path` VARCHAR(2048) NOT NULL,
    `encryption_iv` VARCHAR(255) NULL,
    `checksum` VARCHAR(128) NULL,
    `uploaded_by_id` VARCHAR(36) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_images_uploader` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
