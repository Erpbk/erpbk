<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    protected array $categoryTables = [
        'rider_top_categories',
        'bike_top_categories',
        'employee_top_categories',
        'cheque_top_categories',
    ];

    public function up(): void
    {
        foreach ($this->categoryTables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'filter_type')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->string('filter_type', 40)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->categoryTables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'filter_type')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('filter_type');
            });
        }
    }
};
