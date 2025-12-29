-- ============================================================
-- CASCADING DELETION TRACKING TABLE
-- Tracks which records were deleted due to other deletions
-- ============================================================

USE erpbk;

-- Create the deletion_cascades table
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

-- Verify table was created
SELECT 'Deletion cascades table created successfully!' as Result;
DESCRIBE `deletion_cascades`;

