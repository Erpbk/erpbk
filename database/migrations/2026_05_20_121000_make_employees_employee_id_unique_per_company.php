<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (! Schema::hasColumn('employees', 'company_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id', 'employees_company_id_index');
            });
        }

        foreach (['employees_employee_id_unique', 'employee_id'] as $indexName) {
            if ($this->hasIndex('employees', $indexName)) {
                Schema::table('employees', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
                break;
            }
        }

        if (! $this->hasIndex('employees', 'employees_company_id_employee_id_unique')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique(['company_id', 'employee_id'], 'employees_company_id_employee_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if ($this->hasIndex('employees', 'employees_company_id_employee_id_unique')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique('employees_company_id_employee_id_unique');
            });
        }

        if (! $this->hasIndex('employees', 'employees_employee_id_unique')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique('employee_id', 'employees_employee_id_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return ! empty($rows);
    }
};
