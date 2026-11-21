<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('whatsapp_connection_id')->nullable()->index();
            $table->string('phone_number_id')->nullable();
            $table->string('direction', 8); // in|out
            $table->string('peer_phone')->index();
            $table->string('wa_message_id')->nullable();
            $table->string('message_type', 40)->nullable();
            $table->text('body')->nullable();
            $table->string('template_name')->nullable();
            $table->string('template_language', 16)->nullable();
            $table->json('components')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamp('status_at')->nullable();
            $table->unsignedBigInteger('notification_delivery_id')->nullable()->index();
            $table->string('source', 64)->nullable();
            $table->string('trigger_key')->nullable();
            $table->nullableMorphs('participant');
            $table->unsignedBigInteger('sent_by_user_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();

            $table->unique('wa_message_id');
            $table->index(['company_id', 'peer_phone', 'occurred_at'], 'wa_messages_company_peer_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
