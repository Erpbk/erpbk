<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['company_email', 'personal_email', 'passport', 'emirate_id'];

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

        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('employees', $column)) {
                continue;
            }

            $this->dropUniqueIndexesOnColumn('employees', $column);

            $compositeIndex = "employees_company_id_{$column}_unique";
            if (! $this->hasIndex('employees', $compositeIndex)) {
                Schema::table('employees', function (Blueprint $table) use ($column, $compositeIndex) {
                    $table->unique(['company_id', $column], $compositeIndex);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('employees', $column)) {
                continue;
            }

            $compositeIndex = "employees_company_id_{$column}_unique";
            if ($this->hasIndex('employees', $compositeIndex)) {
                Schema::table('employees', function (Blueprint $table) use ($compositeIndex) {
                    $table->dropUnique($compositeIndex);
                });
            }

            $legacyIndex = "employees_{$column}_unique";
            if (! $this->hasIndex('employees', $legacyIndex)) {
                Schema::table('employees', function (Blueprint $table) use ($column, $legacyIndex) {
                    $table->unique($column, $legacyIndex);
                });
            }
        }
    }

    private function dropUniqueIndexesOnColumn(string $table, string $column): void
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Column_name = ? AND Non_unique = 0", [$column]);
        $indexNames = collect($rows)
            ->pluck('Key_name')
            ->unique()
            ->filter(fn ($name) => $name !== 'PRIMARY')
            ->values();

        foreach ($indexNames as $indexName) {
            if ($this->hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                    $blueprint->dropUnique($indexName);
                });
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return ! empty($rows);
    }
};
