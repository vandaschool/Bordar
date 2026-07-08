ALTER TABLE `users`
    ADD COLUMN `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Tehran' AFTER `phone_number`;
