<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Excel import column mappings for any module (SIM invoices first).
 * Scoped optionally (e.g. per SIM company) via scope_type + scope_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('excel_import_mappings')) {
            return;
        }

        Schema::create('excel_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('module_key', 64)->index();
            $table->string('import_type', 64)->default('default');
            $table->string('scope_type', 64)->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->unsignedTinyInteger('header_rows_to_skip')->default(0);
            $table->json('column_mappings')->nullable();
            $table->json('dynamic_mappings')->nullable();
            $table->json('required_fields')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['company_id', 'module_key', 'import_type', 'scope_type', 'scope_id'],
                'excel_import_mappings_unique_scope'
            );

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_import_mappings');
    }
};
