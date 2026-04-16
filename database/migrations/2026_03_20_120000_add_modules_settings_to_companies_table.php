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
        Schema::connection('mysql_central')->table('companies', function (Blueprint $table) {
            $table->json('modules_settings')->nullable()->after('branding_json');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_central')->table('companies', function (Blueprint $table) {
            $table->dropColumn('modules_settings');
        });
    }
};
