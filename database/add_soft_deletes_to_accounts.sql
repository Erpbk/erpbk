-- ============================================================
-- ADD SOFT DELETES TO ACCOUNTS TABLE
-- Adds deleted_at column for soft deletion support
-- ============================================================

USE erpbk;

-- Check if column already exists
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
    'SELECT "Column deleted_at already exists in accounts table" as Result'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for performance (if column was added or check if index exists)
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'erpbk'
    AND TABLE_NAME = 'accounts'
    AND INDEX_NAME = 'accounts_deleted_at_index'
);

SET @sql_idx = IF(@idx_exists = 0,
    'CREATE INDEX `accounts_deleted_at_index` ON `accounts` (`deleted_at`)',
    'SELECT "Index accounts_deleted_at_index already exists" as Result'
);

PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify changes
SELECT 'Soft deletes added to accounts table successfully!' as Result;
DESCRIBE `accounts`;

