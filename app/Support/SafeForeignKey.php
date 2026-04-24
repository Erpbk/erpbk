<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SafeForeignKey
{
    public static function addNullableForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id',
        ?string $constraintName = null,
        string $onDelete = 'set null'
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable)) {
            return;
        }

        if (!Schema::hasColumn($table, $column) || !Schema::hasColumn($referencedTable, $referencedColumn)) {
            return;
        }

        $constraintName ??= "{$table}_{$column}_foreign";
        $indexName = "{$table}_{$column}_index";

        if (!self::hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName): void {
                $blueprint->index($column, $indexName);
            });
        }

        $orphanCount = self::countOrphans($table, $column, $referencedTable, $referencedColumn);
        if ($orphanCount > 0) {
            if (self::isColumnNullable($table, $column)) {
                self::nullifyOrphans($table, $column, $referencedTable, $referencedColumn);
                $orphanCount = self::countOrphans($table, $column, $referencedTable, $referencedColumn);
            }

            if ($orphanCount > 0) {
                logger()->warning(sprintf(
                    'Skipping FK %s because %d orphaned rows remain in %s.%s',
                    $constraintName,
                    $orphanCount,
                    $table,
                    $column
                ));
                return;
            }
        }

        if (self::hasForeignKey($table, $constraintName)) {
            return;
        }

        if (!self::isColumnTypeCompatible($table, $column, $referencedTable, $referencedColumn)) {
            logger()->warning(sprintf(
                'Skipping FK %s due to incompatible column types: %s.%s -> %s.%s',
                $constraintName,
                $table,
                $column,
                $referencedTable,
                $referencedColumn
            ));
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $constraintName, $onDelete): void {
                $blueprint
                    ->foreign($column, $constraintName)
                    ->references($referencedColumn)
                    ->on($referencedTable)
                    ->onDelete($onDelete)
                    ->onUpdate('cascade');
            });
        } catch (Throwable $throwable) {
            logger()->warning("Unable to add foreign key {$constraintName}: {$throwable->getMessage()}");
        }
    }

    public static function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table) || !self::hasForeignKey($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($constraintName): void {
            $blueprint->dropForeign($constraintName);
        });
    }

    public static function nullifyOrphans(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id'
    ): int {
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable) || !Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, function ($query) use ($referencedTable, $referencedColumn): void {
                $query->select($referencedColumn)->from($referencedTable);
            })
            ->update([$column => null]);
    }

    public static function countOrphans(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id'
    ): int {
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable) || !Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, function ($query) use ($referencedTable, $referencedColumn): void {
                $query->select($referencedColumn)->from($referencedTable);
            })
            ->count();
    }

    private static function hasForeignKey(string $table, string $constraintName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            "SELECT COUNT(*) as aggregate
             FROM information_schema.table_constraints
             WHERE constraint_schema = ?
             AND table_name = ?
             AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$database, $table, $constraintName]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }

    private static function hasIndex(string $table, string $indexName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            "SELECT COUNT(*) as aggregate
             FROM information_schema.statistics
             WHERE table_schema = ?
             AND table_name = ?
             AND index_name = ?",
            [$database, $table, $indexName]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }

    private static function isColumnNullable(string $table, string $column): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $columnInfo = DB::selectOne(
            "SELECT IS_NULLABLE
             FROM information_schema.columns
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?",
            [$database, $table, $column]
        );

        return (($columnInfo->IS_NULLABLE ?? 'NO') === 'YES');
    }

    private static function isColumnTypeCompatible(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): bool {
        $database = Schema::getConnection()->getDatabaseName();
        $columns = DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE
             FROM information_schema.columns
             WHERE TABLE_SCHEMA = ?
             AND ((TABLE_NAME = ? AND COLUMN_NAME = ?) OR (TABLE_NAME = ? AND COLUMN_NAME = ?))",
            [$database, $table, $column, $referencedTable, $referencedColumn]
        );

        if (count($columns) !== 2) {
            return false;
        }

        $left = null;
        $right = null;

        foreach ($columns as $columnInfo) {
            if ($columnInfo->TABLE_NAME === $table) {
                $left = $columnInfo;
                continue;
            }
            $right = $columnInfo;
        }

        if ($left === null || $right === null) {
            return false;
        }

        if ($left->DATA_TYPE !== $right->DATA_TYPE) {
            return false;
        }

        $leftUnsigned = str_contains(strtolower((string) $left->COLUMN_TYPE), 'unsigned');
        $rightUnsigned = str_contains(strtolower((string) $right->COLUMN_TYPE), 'unsigned');

        return $leftUnsigned === $rightUnsigned;
    }
}
