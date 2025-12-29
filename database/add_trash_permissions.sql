-- ============================================================
-- CENTRALIZED TRASH MODULE - PERMISSIONS
-- Creates a single permission for accessing the recycle bin
-- ============================================================

USE erpbk;

-- Create a System/Settings parent if it doesn't exist
SET @system_parent_id = (SELECT id FROM permissions WHERE name = 'System' AND parent_id = 0 LIMIT 1);

INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`)
VALUES (0, 'System', 'web', NOW(), NOW());

SET @system_parent_id = (SELECT id FROM permissions WHERE name = 'System' AND parent_id = 0 LIMIT 1);

-- Create trash view permission
INSERT IGNORE INTO `permissions` (`parent_id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(@system_parent_id, 'trash_view', 'web', NOW(), NOW()),
(@system_parent_id, 'trash_restore', 'web', NOW(), NOW()),
(@system_parent_id, 'trash_force_delete', 'web', NOW(), NOW());

-- Assign all three to Super Admin
SET @super_admin = (SELECT id FROM roles WHERE name LIKE '%Super%Admin%' OR name = 'Super Admin' LIMIT 1);

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @super_admin 
FROM permissions p 
WHERE p.name IN ('trash_view', 'trash_restore', 'trash_force_delete');

-- Show results
SELECT 'Trash module permissions created!' as Result;
SELECT id, parent_id, name FROM permissions WHERE name LIKE 'trash%' OR name = 'System';

