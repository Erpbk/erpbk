<?php

use App\Services\Permissions\RiderStatusPermissionSync;
use App\Services\Permissions\TopBarPermissionSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'parent_id')) {
            return;
        }

        TopBarPermissionSync::syncAll(true);
        RiderStatusPermissionSync::syncAll(true);
    }

    public function down(): void
    {
        // Permissions remain; dynamic trees are re-synced on next TenantModulePermissionsSync::sync().
    }
};
