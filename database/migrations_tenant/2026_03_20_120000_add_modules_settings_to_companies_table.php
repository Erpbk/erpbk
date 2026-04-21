<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration must be idempotent
        // (tenant migrations should not fail if the column already exists).
        if (Schema::hasColumn('companies', 'modules_settings')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->json('modules_settings')->nullable()->after('branding_json');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'modules_settings')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('modules_settings');
        });
    }
};
