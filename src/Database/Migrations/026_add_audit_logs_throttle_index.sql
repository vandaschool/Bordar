ALTER TABLE `audit_logs`
    ADD KEY `idx_audit_logs_throttle` (`action`, `ip_address`, `created_at`);
