<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin panel database: mirrored company list for the admin UI.
     */
    public function up(): void
    {
        Schema::create('admin_companies', function (Blueprint $table) {
            // Mirror central Company IDs so we can sync updates by ID.
            $table->unsignedBigInteger('id')->primary();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('country');
            $table->string('phone', 50);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('database_name')->unique()->nullable();

            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_taxpayer')->default(false);
            $table->string('ntn_number')->nullable();
            $table->date('tax_registration_date')->nullable();

            $table->string('logo')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_companies');
    }
};

