<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_document_types')) {
            return;
        }

        Schema::create('company_document_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->string('key', 32)->nullable(); // tl | mohre | imm | null
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'key'], 'cdt_company_key_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_document_types');
    }
};
