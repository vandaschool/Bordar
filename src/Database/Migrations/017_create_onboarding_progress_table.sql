ALTER TABLE `companies`
    ADD COLUMN `onboarding_tour_completed_at` DATETIME NULL AFTER `status`;

CREATE TABLE IF NOT EXISTS `company_onboarding_progress` (
    `company_id` VARCHAR(36) NOT NULL,
    `item_key` VARCHAR(100) NOT NULL,
    `completed_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`company_id`, `item_key`),
    CONSTRAINT `fk_onboarding_progress_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
