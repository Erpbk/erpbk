<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        try {
            Schema::table($permissionsTable, function (Blueprint $table) {
                $table->dropUnique(['name', 'guard_name']);
            });
        } catch (\Throwable $e) {
            // Index may already be removed or named differently.
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        Schema::table($permissionsTable, function (Blueprint $table) {
            $table->unique(['name', 'guard_name']);
        });
    }
};
