<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // Per-user SMTP credentials (encrypted app password) + CC recipients
    if (!Schema::hasTable('user_email_settings')) {
      Schema::create('user_email_settings', function (Blueprint $table) {
        $table->id();
        // Avoid FK constraints to prevent "Foreign key constraint incorrectly formed" errors
        // caused by legacy `users` table engine/charset differences in this project.
        $table->unsignedBigInteger('user_id')->unique();
        $table->longText('smtp_app_password_encrypted')->nullable();
        $table->index('user_id');
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('user_email_cc_recipients')) {
      Schema::create('user_email_cc_recipients', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('recipient_user_id');
        $table->index('user_id');
        $table->index('recipient_user_id');
        $table->timestamps();

        $table->unique(['user_id', 'recipient_user_id']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('user_email_cc_recipients');
    Schema::dropIfExists('user_email_settings');
  }
};

