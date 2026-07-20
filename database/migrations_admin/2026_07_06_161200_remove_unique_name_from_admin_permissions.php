<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_permissions')) {
            return;
        }

        try {
            Schema::connection('mysql_admin')->table('admin_permissions', function (Blueprint $table) {
                $table->dropUnique('admin_permissions_name_unique');
            });
        } catch (\Throwable $e) {
            // Index may already be removed or named differently.
        }
    }

    public function down(): void
    {
        if (! Schema::connection('mysql_admin')->hasTable('admin_permissions')) {
            return;
        }

        Schema::connection('mysql_admin')->table('admin_permissions', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
