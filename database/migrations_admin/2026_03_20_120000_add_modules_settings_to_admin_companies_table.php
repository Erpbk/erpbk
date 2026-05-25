<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        if (Schema::hasColumn('admin_companies', 'modules_settings')) {
            return;
        }

        Schema::table('admin_companies', function (Blueprint $table) {
            $table->json('modules_settings')->nullable()->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('admin_companies', function (Blueprint $table) {
            $table->dropColumn('modules_settings');
        });
    }
};
