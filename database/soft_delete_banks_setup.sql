-- ============================================================
-- BANKS MODULE - SOFT DELETE IMPLEMENTATION
-- Run this SQL file to complete the database setup
-- Date: 2025-12-27
-- ============================================================

USE erpbk;

-- ============================================================
-- STEP 1: Add deleted_at column to banks table
-- ============================================================

ALTER TABLE `banks` 
ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `banks`
ADD INDEX IF NOT EXISTS `banks_deleted_at_index` (`deleted_at`);

-- Verify column added
SELECT 'Step 1 Complete: deleted_at column added to banks table' as Status;


-- ============================================================
-- STEP 2: Create Bank permissions (if parent doesn't exist)
-- ============================================================

-- Check if "Bank" parent permission exists
SET @bank_parent_id = (SELECT id FROM permissions WHERE name = 'Bank' AND parent_id = 0 LIMIT 1);

-- If not exists, create it
INSERT INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (SELECT 0 as parent_id, 'Bank' as name, 'web' as guard_name, NOW() as created_at, NOW() as updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'Bank' AND parent_id = 0);

-- Get the parent ID (either existing or just created)
SET @bank_parent_id = (SELECT id FROM permissions WHERE name = 'Bank' AND parent_id = 0 LIMIT 1);

-- Verify parent exists
SELECT CONCAT('Bank parent permission ID: ', @bank_parent_id) as Status;


-- ============================================================
-- STEP 3: Create soft delete permissions
-- ============================================================

-- Create bank_view_deleted permission
INSERT INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (SELECT @bank_parent_id as parent_id, 'bank_view_deleted' as name, 'web' as guard_name, NOW() as created_at, NOW() as updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'bank_view_deleted');

-- Create bank_restore permission
INSERT INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (SELECT @bank_parent_id as parent_id, 'bank_restore' as name, 'web' as guard_name, NOW() as created_at, NOW() as updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'bank_restore');

-- Create bank_force_delete permission
INSERT INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (SELECT @bank_parent_id as parent_id, 'bank_force_delete' as name, 'web' as guard_name, NOW() as created_at, NOW() as updated_at) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'bank_force_delete');

-- Verify permissions created
SELECT 'Step 2-3 Complete: Permissions created' as Status;
SELECT id, parent_id, name FROM permissions WHERE name IN ('Bank', 'bank_view_deleted', 'bank_restore', 'bank_force_delete');


-- ============================================================
-- STEP 4: Assign permissions to Super Admin role (role_id = 1)
-- Adjust role_id if your Super Admin has a different ID
-- ============================================================

-- Get Super Admin role ID (adjust WHERE clause if needed)
SET @super_admin_role_id = (SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1);

-- If you don't have Super Admin, get the first admin role
SET @super_admin_role_id = IFNULL(@super_admin_role_id, (SELECT id FROM roles ORDER BY id ASC LIMIT 1));

SELECT CONCAT('Super Admin role ID: ', @super_admin_role_id) as Status;

-- Assign all three permissions to Super Admin
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT id, @super_admin_role_id FROM `permissions` WHERE name = 'bank_view_deleted';

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT id, @super_admin_role_id FROM `permissions` WHERE name = 'bank_restore';

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT id, @super_admin_role_id FROM `permissions` WHERE name = 'bank_force_delete';

-- Verify permissions assigned
SELECT 'Step 4 Complete: Permissions assigned to Super Admin' as Status;


-- ============================================================
-- STEP 5: Optionally assign to Admin role (role_id = 2)
-- Comment out this section if you don't want Admins to have these permissions
-- ============================================================

-- Get Admin role ID (adjust WHERE clause if needed)
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Admin' OR name = 'Administrator' LIMIT 1);

-- Assign view_deleted and restore to Admin (but not force_delete)
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT id, @admin_role_id FROM `permissions` WHERE name = 'bank_view_deleted' AND @admin_role_id IS NOT NULL;

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT id, @admin_role_id FROM `permissions` WHERE name = 'bank_restore' AND @admin_role_id IS NOT NULL;

-- Verify
SELECT CONCAT('Step 5 Complete: Permissions assigned to Admin (role ', @admin_role_id, ')') as Status;


-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- Check if deleted_at column exists
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'erpbk'
    AND TABLE_NAME = 'banks'
    AND COLUMN_NAME = 'deleted_at';

-- Check if index exists
SHOW INDEX FROM banks WHERE Key_name = 'banks_deleted_at_index';

-- List all bank-related permissions
SELECT 
    p.id,
    p.parent_id,
    p.name,
    p.guard_name,
    COUNT(rhp.role_id) as assigned_to_roles
FROM permissions p
LEFT JOIN role_has_permissions rhp ON p.id = rhp.permission_id
WHERE p.name LIKE 'bank%' OR p.name = 'Bank'
GROUP BY p.id, p.parent_id, p.name, p.guard_name
ORDER BY p.parent_id, p.id;

-- Show which roles have bank permissions
SELECT 
    r.id as role_id,
    r.name as role_name,
    p.name as permission_name
FROM roles r
INNER JOIN role_has_permissions rhp ON r.id = rhp.role_id
INNER JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name LIKE 'bank%'
ORDER BY r.id, p.name;

-- ============================================================
-- COMPLETION MESSAGE
-- ============================================================

SELECT '========================================' as ' ';
SELECT 'BANKS SOFT DELETE SETUP COMPLETE!' as ' ';
SELECT '========================================' as ' ';
SELECT 'Next steps:' as ' ';
SELECT '1. Test soft delete: Go to /banks and delete a bank' as ' ';
SELECT '2. Test view deleted: Click "View Deleted" button' as ' ';
SELECT '3. Test restore: Click "Restore" on a deleted bank' as ' ';
SELECT '4. Test force delete: Click "Permanent Delete" (Super Admin only)' as ' ';
SELECT '========================================' as ' ';

