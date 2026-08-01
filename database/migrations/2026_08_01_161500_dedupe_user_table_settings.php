<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_table_settings')) {
            return;
        }

        // Keep the most recently updated row per user + table + company; drop older duplicates.
        $duplicates = DB::table('user_table_settings')
            ->select('user_id', 'table_identifier', 'company_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('user_id', 'table_identifier', 'company_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $group) {
            $base = function () use ($group) {
                $query = DB::table('user_table_settings')
                    ->where('user_id', $group->user_id)
                    ->where('table_identifier', $group->table_identifier);

                if ($group->company_id === null) {
                    $query->whereNull('company_id');
                } else {
                    $query->where('company_id', $group->company_id);
                }

                return $query;
            };

            $keepId = $base()
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            if (! $keepId) {
                continue;
            }

            $base()->where('id', '!=', $keepId)->delete();
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM `user_table_settings` WHERE `Key_name` = ?', [
            'user_table_settings_user_table_company_unique',
        ]))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('user_table_settings', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'table_identifier', 'company_id'],
                    'user_table_settings_user_table_company_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_table_settings')) {
            return;
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM `user_table_settings` WHERE `Key_name` = ?', [
            'user_table_settings_user_table_company_unique',
        ]))->isNotEmpty();

        if ($indexExists) {
            Schema::table('user_table_settings', function (Blueprint $table) {
                $table->dropUnique('user_table_settings_user_table_company_unique');
            });
        }
    }
};
