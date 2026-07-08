CREATE TABLE IF NOT EXISTS `company_cohorts` (
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `status` ENUM('APPLIED', 'ACCEPTED', 'REJECTED', 'WITHDRAWN', 'COMPLETED') DEFAULT 'APPLIED',
    `joined_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`company_id`, `cohort_id`),
    CONSTRAINT `fk_company_cohorts_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    CONSTRAINT `fk_company_cohorts_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
