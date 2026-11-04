<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies') && ! Schema::hasColumn('companies', 'notification_channels_settings')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->json('notification_channels_settings')->nullable()->after('modules_settings');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'notification_channels_settings')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('notification_channels_settings');
            });
        }
    }
};
