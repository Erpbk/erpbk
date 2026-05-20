<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (Schema::hasTable($permissionsTable) && !Schema::hasColumn($permissionsTable, 'parent_id')) {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                $table->index('parent_id');
            });
        }

        TenantModulePermissionsSync::sync();
    }

    public function down(): void
    {
        // Intentionally no-op: do not remove permissions on rollback.
    }
};
