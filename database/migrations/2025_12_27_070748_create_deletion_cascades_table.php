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
        Schema::create('deletion_cascades', function (Blueprint $table) {
            $table->id();

            // Primary deleted record
            $table->string('primary_model', 100)->comment('Main model that was deleted (e.g., Banks)');
            $table->unsignedBigInteger('primary_id')->comment('ID of the main deleted record');
            $table->string('primary_name', 255)->nullable()->comment('Name/identifier of main record');

            // Related deleted record
            $table->string('related_model', 100)->comment('Related model that was deleted (e.g., Accounts)');
            $table->unsignedBigInteger('related_id')->comment('ID of the related deleted record');
            $table->string('related_name', 255)->nullable()->comment('Name/identifier of related record');

            // Deletion context
            $table->string('relationship_type', 50)->comment('Type of relationship (e.g., hasOne, hasMany)');
            $table->string('relationship_name', 100)->nullable()->comment('Relationship method name');
            $table->enum('deletion_type', ['soft', 'hard'])->default('soft')->comment('Type of deletion');

            // User who triggered the deletion
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Metadata
            $table->text('deletion_reason')->nullable()->comment('Reason or context for deletion');
            $table->json('metadata')->nullable()->comment('Additional data about the deletion');

            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['primary_model', 'primary_id'], 'idx_primary_record');
            $table->index(['related_model', 'related_id'], 'idx_related_record');
            $table->index('deleted_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deletion_cascades');
    }
};
