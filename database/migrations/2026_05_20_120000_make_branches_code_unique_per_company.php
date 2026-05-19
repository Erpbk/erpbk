<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if (! Schema::hasColumn('branches', 'company_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id', 'branches_company_id_index');
            });
        }

        foreach (['branches_code_unique', 'code'] as $indexName) {
            if ($this->hasIndex('branches', $indexName)) {
                Schema::table('branches', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
                break;
            }
        }

        if (! $this->hasIndex('branches', 'branches_company_id_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'branches_company_id_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if ($this->hasIndex('branches', 'branches_company_id_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropUnique('branches_company_id_code_unique');
            });
        }

        if (! $this->hasIndex('branches', 'branches_code_unique')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unique('code', 'branches_code_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return ! empty($rows);
    }
};
