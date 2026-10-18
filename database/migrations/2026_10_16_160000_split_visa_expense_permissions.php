<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $map = TenantModulePermissionsSync::visaExpenseSubmodulePermissionMap();
        $captured = TenantModulePermissionsSync::capturePermissionAssignments($map);

        TenantModulePermissionsSync::sync(true);
        TenantModulePermissionsSync::applyCapturedPermissionAssignments($captured);
        TenantModulePermissionsSync::migrateLegacyPermissionAssignments();
    }

    public function down(): void
    {
        //
    }
};
