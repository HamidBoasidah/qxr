-- ============================================================
-- SQL Generated from commit: 91c523b (Return Policy)
-- This includes all database changes from that commit
-- ============================================================

-- ============================================================
-- 1. ALTER products table (new columns added)
-- ============================================================
ALTER TABLE `products`
    ADD COLUMN `production_date` DATE NULL AFTER `main_image`,
    ADD COLUMN `expiry_date` DATE NULL AFTER `production_date`,
    ADD COLUMN `usage_instructions` TEXT NULL AFTER `expiry_date`,
    ADD COLUMN `medical_warnings` TEXT NULL AFTER `usage_instructions`;

-- ============================================================
-- 2. ALTER invoices table (add return_policy_id column)
-- ============================================================
ALTER TABLE `invoices`
    ADD COLUMN `return_policy_id` BIGINT UNSIGNED NULL AFTER `total_snapshot`;

-- ============================================================
-- 3. ALTER invoice_items table (add snapshot columns)
-- ============================================================
ALTER TABLE `invoice_items`
    ADD COLUMN `expiry_date` DATE NULL AFTER `line_total_snapshot`,
    ADD COLUMN `discount_type` ENUM('percent', 'fixed') NULL AFTER `expiry_date`,
    ADD COLUMN `discount_value` DECIMAL(15, 4) NULL AFTER `discount_type`,
    ADD COLUMN `is_bonus` TINYINT(1) NOT NULL DEFAULT 0 AFTER `discount_value`;

-- ============================================================
-- 4. CREATE return_policies table
-- ============================================================
CREATE TABLE `return_policies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `return_window_days` INT UNSIGNED NOT NULL,
    `max_return_ratio` DECIMAL(5, 4) NOT NULL,
    `bonus_return_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `bonus_return_ratio` DECIMAL(5, 4) NULL,
    `discount_deduction_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `min_days_before_expiry` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,

    CONSTRAINT `return_policies_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,

    INDEX `idx_company_default` (`company_id`, `is_default`),
    INDEX `idx_company_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ADD foreign key for return_policy_id in invoices
-- ============================================================
ALTER TABLE `invoices`
    ADD CONSTRAINT `invoices_return_policy_id_foreign`
        FOREIGN KEY (`return_policy_id`) REFERENCES `return_policies` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 6. CREATE return_invoices table
-- ============================================================
CREATE TABLE `return_invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `original_invoice_id` BIGINT UNSIGNED NOT NULL,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `return_policy_id` BIGINT UNSIGNED NOT NULL,
    `total_refund_amount` DECIMAL(15, 4) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,

    CONSTRAINT `uq_original_invoice` UNIQUE (`original_invoice_id`),

    CONSTRAINT `return_invoices_original_invoice_id_foreign`
        FOREIGN KEY (`original_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,

    CONSTRAINT `return_invoices_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,

    CONSTRAINT `return_invoices_return_policy_id_foreign`
        FOREIGN KEY (`return_policy_id`) REFERENCES `return_policies` (`id`) ON DELETE RESTRICT,

    INDEX `idx_company_created` (`company_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. CREATE return_invoice_items table
-- ============================================================
CREATE TABLE `return_invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `return_invoice_id` BIGINT UNSIGNED NOT NULL,
    `original_item_id` BIGINT UNSIGNED NOT NULL,
    `returned_quantity` INT UNSIGNED NOT NULL,
    `unit_price_snapshot` DECIMAL(15, 4) NOT NULL,
    `discount_type_snapshot` ENUM('percent', 'fixed') NULL,
    `discount_value_snapshot` DECIMAL(15, 4) NULL,
    `expiry_date_snapshot` DATE NULL,
    `is_bonus` TINYINT(1) NOT NULL DEFAULT 0,
    `refund_amount` DECIMAL(15, 4) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,

    CONSTRAINT `return_invoice_items_return_invoice_id_foreign`
        FOREIGN KEY (`return_invoice_id`) REFERENCES `return_invoices` (`id`) ON DELETE CASCADE,

    CONSTRAINT `return_invoice_items_original_item_id_foreign`
        FOREIGN KEY (`original_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE RESTRICT,

    INDEX `idx_return_invoice` (`return_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
