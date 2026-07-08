CREATE TABLE IF NOT EXISTS `applications` (
    `id` VARCHAR(36) PRIMARY KEY,
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `status` ENUM('DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED', 'PENDING_INFO', 'WITHDRAWN') DEFAULT 'DRAFT',
    `data` JSON NOT NULL,
    `version` INT DEFAULT 1,
    `submitted_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_applications_company` (`company_id`),
    KEY `idx_applications_cohort` (`cohort_id`),
    KEY `idx_applications_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_applications_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    CONSTRAINT `fk_applications_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
