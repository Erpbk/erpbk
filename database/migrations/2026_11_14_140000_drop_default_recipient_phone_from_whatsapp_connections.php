<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_connections') && Schema::hasColumn('whatsapp_connections', 'default_recipient_phone')) {
            Schema::table('whatsapp_connections', function (Blueprint $table) {
                $table->dropColumn('default_recipient_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('whatsapp_connections') && ! Schema::hasColumn('whatsapp_connections', 'default_recipient_phone')) {
            Schema::table('whatsapp_connections', function (Blueprint $table) {
                $table->string('default_recipient_phone')->nullable()->after('access_token');
            });
        }
    }
};
