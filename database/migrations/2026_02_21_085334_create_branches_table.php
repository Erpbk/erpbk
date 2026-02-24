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
        Schema::create('branches', function (Blueprint $table) {

            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('parent_branch_id')->nullable()->constrained('branches');
            $table->enum('branch_type', ['headquarters', 'branch', 'warehouse', 'grage'])
                  ->default('branch');
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index('is_active');
            $table->index('branch_type');
            $table->index('parent_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};