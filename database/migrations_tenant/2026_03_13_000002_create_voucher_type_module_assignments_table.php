<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_type_module_assignments')) {
            Schema::create('voucher_type_module_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
                $table->string('module_key', 100);
                $table->timestamps();

                $table->unique(['voucher_type_id', 'module_key'], 'voucher_type_module_unique');
                $table->index('module_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_type_module_assignments');
    }
};
