<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        if (!Schema::hasColumn('voucher_types', 'company_id')) {
            Schema::table('voucher_types', function (Blueprint $table): void {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        try {
            Schema::table('voucher_types', function (Blueprint $table): void {
                $table->dropUnique('voucher_types_code_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if legacy DB does not have this unique key.
        }

        if (!$this->hasIndex('voucher_types', 'voucher_types_company_id_code_unique')) {
            Schema::table('voucher_types', function (Blueprint $table): void {
                $table->unique(['company_id', 'code'], 'voucher_types_company_id_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('voucher_types')) {
            return;
        }

        try {
            Schema::table('voucher_types', function (Blueprint $table): void {
                $table->dropUnique('voucher_types_company_id_code_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if key does not exist.
        }

        if (!$this->hasIndex('voucher_types', 'voucher_types_code_unique')) {
            Schema::table('voucher_types', function (Blueprint $table): void {
                $table->unique('code', 'voucher_types_code_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }
};
