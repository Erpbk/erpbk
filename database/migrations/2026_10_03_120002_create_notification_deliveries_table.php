<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_deliveries')) {
            return;
        }

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_notification_id');
            $table->string('channel', 32);
            $table->string('status', 32)->default('pending')->index();
            $table->json('provider_response')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_notification_id', 'channel'], 'notification_deliveries_notification_channel_unique');
            $table->foreign('user_notification_id')
                ->references('id')
                ->on('user_notifications')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
