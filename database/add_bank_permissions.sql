-- ============================================================
-- BANKS SOFT DELETE - PERMISSIONS ONLY
-- Quick script to add just the permissions
-- ============================================================

USE erpbk;

-- Get or create Bank parent permission
SET @bank_parent_id = (SELECT id FROM permissions WHERE name = 'Bank' AND parent_id = 0 LIMIT 1);

-- If Bank parent doesn't exist, create it
INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
VALUES (0, 'Bank', 'web', NOW(), NOW());

-- Get the parent ID again
SET @bank_parent_id = (SELECT id FROM permissions WHERE name = 'Bank' AND parent_id = 0 LIMIT 1);

-- Create the three soft delete permissions
INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(@bank_parent_id, 'bank_view_deleted', 'web', NOW(), NOW()),
(@bank_parent_id, 'bank_restore', 'web', NOW(), NOW()),
(@bank_parent_id, 'bank_force_delete', 'web', NOW(), NOW());

-- Assign to Super Admin (assuming role_id = 1, adjust if needed)
SET @super_admin = (SELECT id FROM roles WHERE name LIKE '%Super%Admin%' OR name = 'Super Admin' LIMIT 1);

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @super_admin 
FROM permissions p 
WHERE p.name IN ('bank_view_deleted', 'bank_restore', 'bank_force_delete');

-- Show results
SELECT 'Permissions created successfully!' as Result;
SELECT id, parent_id, name FROM permissions WHERE name LIKE 'bank%' OR name = 'Bank';

