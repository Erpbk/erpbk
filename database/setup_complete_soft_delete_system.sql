-- ============================================================
-- COMPLETE SOFT DELETE & CASCADE TRACKING SETUP
-- Run this file to set up all tables and permissions
-- ============================================================

USE erpbk;

-- ============================================================
-- STEP 1: ADD SOFT DELETES TO ACCOUNTS TABLE
-- ============================================================

-- Check if deleted_at column exists in accounts
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erpbk' 
    AND TABLE_NAME = 'accounts' 
    AND COLUMN_NAME = 'deleted_at'
);

-- Add deleted_at column if it doesn't exist
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `accounts` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL',
    'SELECT "Column deleted_at already exists in accounts table" as Message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for performance
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'erpbk'
    AND TABLE_NAME = 'accounts'
    AND INDEX_NAME = 'accounts_deleted_at_index'
);

SET @sql_idx = IF(@idx_exists = 0,
    'CREATE INDEX `accounts_deleted_at_index` ON `accounts` (`deleted_at`)',
    'SELECT "Index already exists" as Message'
);

PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ Accounts table updated for soft deletes' as Status;

-- ============================================================
-- STEP 2: CREATE DELETION CASCADES TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `deletion_cascades` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  
  -- Primary deleted record (the main one that user deleted)
  `primary_model` VARCHAR(100) NOT NULL COMMENT 'Main model that was deleted (e.g., Banks)',
  `primary_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID of the main deleted record',
  `primary_name` VARCHAR(255) NULL COMMENT 'Name/identifier of main record',
  
  -- Related deleted record (the one that was deleted because of the primary)
  `related_model` VARCHAR(100) NOT NULL COMMENT 'Related model that was deleted (e.g., Accounts)',
  `related_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID of the related deleted record',
  `related_name` VARCHAR(255) NULL COMMENT 'Name/identifier of related record',
  
  -- Deletion context
  `relationship_type` VARCHAR(50) NOT NULL COMMENT 'Type of relationship (e.g., hasOne, hasMany)',
  `relationship_name` VARCHAR(100) NULL COMMENT 'Relationship method name',
  `deletion_type` ENUM('soft', 'hard') DEFAULT 'soft' COMMENT 'Type of deletion',
  
  -- User who triggered the deletion
  `deleted_by` BIGINT UNSIGNED NULL,
  
  -- Metadata
  `deletion_reason` TEXT NULL COMMENT 'Reason or context for deletion',
  `metadata` JSON NULL COMMENT 'Additional data about the deletion',
  
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  -- Indexes for fast lookups
  INDEX `idx_primary_record` (`primary_model`, `primary_id`),
  INDEX `idx_related_record` (`related_model`, `related_id`),
  INDEX `idx_deleted_by` (`deleted_by`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✓ Deletion cascades table created' as Status;

-- ============================================================
-- STEP 3: ADD TRASH/RECYCLE BIN PERMISSIONS
-- ============================================================

-- Create System parent permission if not exists
INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
VALUES (0, 'System', 'web', NOW(), NOW());

SET @system_parent_id = (SELECT id FROM permissions WHERE name = 'System' AND parent_id = 0 LIMIT 1);

-- Create trash permissions
INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(@system_parent_id, 'trash_view', 'web', NOW(), NOW()),
(@system_parent_id, 'trash_restore', 'web', NOW(), NOW()),
(@system_parent_id, 'trash_force_delete', 'web', NOW(), NOW());

SELECT '✓ Trash permissions created' as Status;

-- Assign to Super Admin
SET @super_admin = (SELECT id FROM roles WHERE name LIKE '%Super%Admin%' OR name = 'Super Admin' LIMIT 1);

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @super_admin 
FROM permissions p 
WHERE p.name IN ('trash_view', 'trash_restore', 'trash_force_delete');

SELECT '✓ Permissions assigned to Super Admin' as Status;

-- ============================================================
-- VERIFICATION
-- ============================================================

SELECT '============================================' as '';
SELECT '✓ SETUP COMPLETE!' as '';
SELECT '============================================' as '';

-- Show accounts table structure
SELECT 'Accounts Table Structure:' as '';
DESCRIBE `accounts`;

-- Show deletion_cascades table structure
SELECT '' as '';
SELECT 'Deletion Cascades Table Structure:' as '';
DESCRIBE `deletion_cascades`;

-- Show trash permissions
SELECT '' as '';
SELECT 'Trash Permissions Created:' as '';
SELECT id, parent_id, name, guard_name 
FROM permissions 
WHERE name LIKE 'trash%' OR name = 'System'
ORDER BY parent_id, name;

-- Summary
SELECT '' as '';
SELECT '============================================' as '';
SELECT 'NEXT STEPS:' as '';
SELECT '1. Clear cache: php artisan cache:clear' as '';
SELECT '2. Test by deleting a bank record' as '';
SELECT '3. Check Recycle Bin to see cascade info' as '';
SELECT '============================================' as '';

