<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id', 'users_company_id_index');
            });
        }

        if (Schema::hasTable('companies')) {
            $fallbackCompanyId = DB::table('companies')->orderBy('id')->value('id');
            if ($fallbackCompanyId !== null) {
                DB::table('users')->whereNull('company_id')->update(['company_id' => (int) $fallbackCompanyId]);
            }
        }

        if (Schema::hasTable('companies') && ! $this->hasCompanyForeignKey()) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('company_id', 'users_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'company_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign('users_company_id_foreign');
            } catch (\Throwable $e) {
                // no-op
            }
            try {
                $table->dropIndex('users_company_id_index');
            } catch (\Throwable $e) {
                // no-op
            }
            $table->dropColumn('company_id');
        });
    }

    private function hasCompanyForeignKey(): bool
    {
        $database = DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.key_column_usage
             WHERE table_schema = ?
               AND table_name = 'users'
               AND column_name = 'company_id'
               AND referenced_table_name = 'companies'
               AND referenced_column_name = 'id'",
            [$database]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }
};
