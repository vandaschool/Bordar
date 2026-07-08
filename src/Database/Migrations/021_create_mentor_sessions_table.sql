CREATE TABLE IF NOT EXISTS `mentor_sessions` (
    `id` VARCHAR(36) PRIMARY KEY,
    `mentor_id` VARCHAR(36) NOT NULL,
    `company_id` VARCHAR(36) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('SCHEDULED', 'COMPLETED', 'CANCELED', 'RESCHEDULED') DEFAULT 'SCHEDULED',
    `agenda` TEXT NULL,
    `summary` TEXT NULL,
    `action_plan` TEXT NULL,
    -- Non-NULL only while the booking actively holds the slot (status is
    -- SCHEDULED or COMPLETED). Becomes NULL on cancel/reschedule, which
    -- frees the slot since MySQL/MariaDB unique indexes allow multiple
    -- NULLs. This is what makes concurrent double-booking of the same
    -- mentor+time impossible at the database level (second INSERT hits the
    -- unique key), without permanently locking a slot a company cancelled.
    `slot_lock_key` VARCHAR(120) GENERATED ALWAYS AS (
        CASE WHEN `status` IN ('SCHEDULED', 'COMPLETED') THEN CONCAT(`mentor_id`, '|', `start_time`) ELSE NULL END
    ) STORED,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_mentor_slot_lock` (`slot_lock_key`),
    KEY `idx_mentor_sessions_company` (`company_id`),
    KEY `idx_mentor_sessions_mentor` (`mentor_id`),
    CONSTRAINT `fk_mentor_sessions_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors`(`id`),
    CONSTRAINT `fk_mentor_sessions_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
