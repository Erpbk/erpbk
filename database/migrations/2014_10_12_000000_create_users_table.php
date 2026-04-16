<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base ERP users table (tenant DB + central when applicable).
     * Must exist before activity_logs FK and other users.* alterations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('bio')->nullable();
            $table->string('image_name', 100)->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->json('branch_ids')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->rememberToken();
            $table->timestamps();

            $table->unique('email');
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
