<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('company_email')->unique();
            $table->string('personal_email')->unique();
            $table->string('personal_contact')->nullable();
            $table->string('company_contact')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->unsignedBigInteger('nationality_id');
            $table->string('department_id')->nullable();
            $table->string('designation')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('emirate_id')->unique()->nullable();
            $table->date('emirate_expiry')->nullable();
            $table->string('passport')->unique()->nullable();
            $table->date('passport_expiry')->nullable();
            $table->date('doj');
            $table->date('dob');
            $table->string('visa_sponsor')->nullable();
            $table->string('visa_occupation')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->text('address')->nullable();
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};