<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central database: per-tenant module visibility and menu label overrides (admin-assigned).
     */
    public function up(): void
    {
        // This migration targets the central DB on purpose, so it must be idempotent
        // (tenant migrations should not fail if the column already exists).
        if (Schema::connection('mysql_central')->hasColumn('companies', 'modules_settings')) {
            return;
        }
        Schema::connection('mysql_central')->table('companies', function (Blueprint $table) {
            $table->json('modules_settings')->nullable()->after('branding_json');
        });
    }

    public function down(): void
    {
        if (!Schema::connection('mysql_central')->hasColumn('companies', 'modules_settings')) {
            return;
        }
        Schema::connection('mysql_central')->table('companies', function (Blueprint $table) {
            $table->dropColumn('modules_settings');
        });
    }
};
