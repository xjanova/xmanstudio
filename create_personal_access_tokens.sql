-- Run this on production MySQL (admin_xman4289new)
-- Creates the Laravel Sanctum personal_access_tokens table

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `tokenable_type` VARCHAR(255) NOT NULL,
    `tokenable_id` BIGINT UNSIGNED NOT NULL,
    `name` TEXT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `abilities` TEXT NULL,
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`),
    UNIQUE `personal_access_tokens_token_unique` (`token`),
    INDEX `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also register in migrations table so artisan knows it's done
INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2026_03_09_135646_create_personal_access_tokens_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations` AS m));
