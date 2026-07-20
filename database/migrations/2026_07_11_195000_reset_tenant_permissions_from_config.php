<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Drop all existing Spatie permissions and rebuild the tree from
     * config/tenant_module_permissions.php. Admin roles (Super Admin,
     * Administrator) receive every leaf permission.
     *
     * Non-admin role/user permission assignments are cleared and must be
     * re-assigned after this migration.
     */
    public function up(): void
    {
        TenantModulePermissionsSync::resetFromConfig(true);
    }

    public function down(): void
    {
        // Intentionally no-op: previous permission sets cannot be restored.
    }
};
