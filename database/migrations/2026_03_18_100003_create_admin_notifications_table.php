<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Central database. Notifications for admin (e.g. new company registered).
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_notifications')) {
            return;
        }
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g. 'company_registered'
            $table->json('data'); // payload: company_id, company_name, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
