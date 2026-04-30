<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        Schema::table('voucher_types', function (Blueprint $table) {
            $table->dropUnique('voucher_types_code_unique');
            $table->unique(['company_id', 'code'], 'voucher_types_company_id_code_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        Schema::table('voucher_types', function (Blueprint $table) {
            $table->dropUnique('voucher_types_company_id_code_unique');
            $table->unique('code', 'voucher_types_code_unique');
        });
    }
};
