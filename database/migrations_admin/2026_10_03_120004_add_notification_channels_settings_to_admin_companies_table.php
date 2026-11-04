<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_admin';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('admin_companies')
            && ! Schema::connection($this->connection)->hasColumn('admin_companies', 'notification_channels_settings')) {
            Schema::connection($this->connection)->table('admin_companies', function (Blueprint $table) {
                $table->json('notification_channels_settings')->nullable()->after('modules_settings');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('admin_companies')
            && Schema::connection($this->connection)->hasColumn('admin_companies', 'notification_channels_settings')) {
            Schema::connection($this->connection)->table('admin_companies', function (Blueprint $table) {
                $table->dropColumn('notification_channels_settings');
            });
        }
    }
};
