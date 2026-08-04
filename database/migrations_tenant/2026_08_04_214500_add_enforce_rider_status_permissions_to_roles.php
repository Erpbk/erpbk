<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        if (! Schema::hasColumn('roles', 'enforce_rider_status_permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('enforce_rider_status_permissions')->default(true)->after('guard_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'enforce_rider_status_permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('enforce_rider_status_permissions');
            });
        }
    }
};
