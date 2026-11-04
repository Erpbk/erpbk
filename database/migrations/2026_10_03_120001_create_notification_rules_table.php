<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_rules')) {
            return;
        }

        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('type_key', 100);
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->json('recipient_config')->nullable();
            $table->json('channels')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'type_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
