CREATE TABLE IF NOT EXISTS `invoices` (
    `id` VARCHAR(36) PRIMARY KEY,
    `company_id` VARCHAR(36) NOT NULL,
    `cohort_id` VARCHAR(36) NOT NULL,
    `amount` DECIMAL(14, 2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'IRR',
    `installment_count` INT DEFAULT 1,
    `issue_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `due_date` DATETIME NOT NULL,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by_id` VARCHAR(36) NULL,
    KEY `idx_invoices_company` (`company_id`),
    CONSTRAINT `fk_invoices_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
    CONSTRAINT `fk_invoices_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts`(`id`),
    CONSTRAINT `fk_invoices_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
