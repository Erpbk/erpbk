<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Detects whether branch IDs are referenced elsewhere (branch_id FKs, parent_branch_id, user branch_ids JSON).
 */
class BranchReferencedChecker
{
    /**
     * @param  list<int|string>  $branchIds
     * @return list<int> Distinct branch IDs that cannot be deleted
     */
    public static function inUseIds(array $branchIds): array
    {
        $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));
        if ($branchIds === []) {
            return [];
        }

        $inUse = [];

        foreach (Branch::query()->whereIn('parent_branch_id', $branchIds)->pluck('parent_branch_id') as $pid) {
            if ($pid !== null && $pid !== '') {
                $inUse[(int) $pid] = true;
            }
        }

        $database = Schema::getConnection()->getDatabaseName();
        $rows = DB::select(
            'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?',
            [$database, 'branch_id']
        );

        foreach ($rows as $row) {
            $table = self::tableNameFromInformationSchemaRow($row);
            if ($table === null || $table === 'branches') {
                continue;
            }
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                $hits = company_table($table)
                    ->whereIn('branch_id', $branchIds)
                    ->distinct()
                    ->pluck('branch_id');
            } catch (\Throwable) {
                continue;
            }
            foreach ($hits as $bid) {
                if ($bid !== null && $bid !== '') {
                    $inUse[(int) $bid] = true;
                }
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch_ids')) {
            $branchIdSet = array_fill_keys($branchIds, true);
            try {
                User::query()
                    ->whereNotNull('branch_ids')
                    ->select(['id', 'branch_ids'])
                    ->chunkById(200, function ($users) use (&$inUse, $branchIdSet): void {
                        foreach ($users as $user) {
                            $arr = $user->branch_ids;
                            if (! is_array($arr)) {
                                continue;
                            }
                            foreach ($arr as $bid) {
                                $bid = (int) $bid;
                                if (isset($branchIdSet[$bid])) {
                                    $inUse[$bid] = true;
                                }
                            }
                        }
                    });
            } catch (\Throwable) {
                // ignore
            }
        }

        return array_keys($inUse);
    }

    /**
     * @param  object|array<string, mixed>  $row
     */
    private static function tableNameFromInformationSchemaRow(object|array $row): ?string
    {
        if (is_array($row)) {
            return isset($row['TABLE_NAME']) ? (string) $row['TABLE_NAME'] : null;
        }

        if (isset($row->TABLE_NAME)) {
            return (string) $row->TABLE_NAME;
        }
        if (isset($row->table_name)) {
            return (string) $row->table_name;
        }

        return null;
    }
}
