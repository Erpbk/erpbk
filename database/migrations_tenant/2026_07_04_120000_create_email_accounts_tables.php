<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (!Schema::hasTable('email_accounts')) {
      Schema::create('email_accounts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('company_id')->nullable()->index();
        $table->string('email');
        $table->longText('app_password');
        $table->string('display_name')->nullable();
        $table->string('status', 20)->default('active');
        $table->timestamps();

        $table->unique(['company_id', 'email']);
      });
    }

    if (!Schema::hasTable('email_account_user')) {
      Schema::create('email_account_user', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('email_account_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();

        $table->unique(['email_account_id', 'user_id']);
        $table->index('email_account_id');
        $table->index('user_id');
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('email_account_user');
    Schema::dropIfExists('email_accounts');
  }
};
