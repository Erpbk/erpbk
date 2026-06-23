<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TenantModulePermissionsSync::sync();
        TenantModulePermissionsSync::migrateLegacyPermissionAssignments();
    }

    public function down(): void
    {
        //
    }
};
