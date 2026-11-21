<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_connections')) {
            Schema::create('whatsapp_connections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('label')->nullable();
                $table->string('phone_number_id');
                $table->string('display_phone_number')->nullable();
                $table->string('waba_id')->nullable();
                $table->longText('access_token');
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->unique(['company_id', 'phone_number_id']);
            });
        }

        if (! Schema::hasTable('whatsapp_connection_user')) {
            Schema::create('whatsapp_connection_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('whatsapp_connection_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['whatsapp_connection_id', 'user_id'], 'wa_connection_user_unique');
                $table->index('whatsapp_connection_id');
                $table->index('user_id');
            });
        }

        if (Schema::hasTable('notification_deliveries') && ! Schema::hasColumn('notification_deliveries', 'to_phone')) {
            Schema::table('notification_deliveries', function (Blueprint $table) {
                $table->string('to_phone')->nullable()->after('to_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_deliveries') && Schema::hasColumn('notification_deliveries', 'to_phone')) {
            Schema::table('notification_deliveries', function (Blueprint $table) {
                $table->dropColumn('to_phone');
            });
        }

        Schema::dropIfExists('whatsapp_connection_user');
        Schema::dropIfExists('whatsapp_connections');
    }
};
