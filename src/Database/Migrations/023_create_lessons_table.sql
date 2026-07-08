CREATE TABLE IF NOT EXISTS `lessons` (
    `id` VARCHAR(36) PRIMARY KEY,
    `course_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `type` ENUM('VIDEO', 'TEXT', 'QUIZ', 'EXTERNAL_LINK') NOT NULL,
    `content` JSON NOT NULL,
    `order` INT NOT NULL DEFAULT 0,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    KEY `idx_lessons_course` (`course_id`),
    CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`),
    CONSTRAINT `fk_lessons_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
