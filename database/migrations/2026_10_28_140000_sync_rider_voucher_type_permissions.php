<?php

use App\Services\TenantModulePermissionsSync;
use App\Support\RiderVoucherPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $map = RiderVoucherPermissions::legacyGrantMigrationMap();
        $captured = TenantModulePermissionsSync::capturePermissionAssignments($map);

        TenantModulePermissionsSync::sync(true);
        TenantModulePermissionsSync::applyCapturedPermissionAssignments($captured);
    }

    public function down(): void
    {
        // Intentionally no-op: do not remove permissions on rollback.
    }
};
