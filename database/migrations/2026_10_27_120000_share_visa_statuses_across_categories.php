<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visa_statuses') || ! Schema::hasTable('visa_renewal_categories')) {
            return;
        }

        if (! Schema::hasTable('visa_renewal_category_visa_status')) {
            Schema::create('visa_renewal_category_visa_status', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('visa_renewal_category_id');
                $table->unsignedBigInteger('visa_status_id');
                $table->unsignedInteger('display_order')->default(1);
                $table->timestamps();

                $table->unique(
                    ['visa_renewal_category_id', 'visa_status_id'],
                    'visa_cat_status_unique'
                );
                $table->index('visa_status_id', 'visa_cat_status_status_id_index');
                $table->index(
                    ['visa_renewal_category_id', 'display_order'],
                    'visa_cat_status_order_index'
                );
            });
        }

        $hasCategoryFk = Schema::hasColumn('visa_statuses', 'visa_renewal_category_id');
        $hasCompanyId = Schema::hasColumn('visa_statuses', 'company_id');
        $hasSoftDeletes = Schema::hasColumn('visa_statuses', 'deleted_at');

        if ($hasCategoryFk) {
            $this->backfillPivotAndDedupe($hasCompanyId, $hasSoftDeletes);
            $this->remapTopStatusSettings();

            Schema::table('visa_statuses', function (Blueprint $table) {
                if ($this->indexExists('visa_statuses', 'visa_statuses_renewal_category_id_index')) {
                    $table->dropIndex('visa_statuses_renewal_category_id_index');
                }
                $table->dropColumn('visa_renewal_category_id');
            });
        }

        $this->restoreUniqueNameConstraint($hasCompanyId);
    }

    public function down(): void
    {
        if (! Schema::hasTable('visa_statuses')) {
            return;
        }

        $this->dropUniqueIndexesOnName();

        if (! Schema::hasColumn('visa_statuses', 'visa_renewal_category_id')) {
            Schema::table('visa_statuses', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_renewal_category_id')->nullable()->after('id');
                $table->index('visa_renewal_category_id', 'visa_statuses_renewal_category_id_index');
            });
        }

        if (Schema::hasTable('visa_renewal_category_visa_status')) {
            $defaultId = DB::table('visa_renewal_categories')->where('is_default', true)->value('id')
                ?? DB::table('visa_renewal_categories')->orderBy('id')->value('id');

            $pivots = DB::table('visa_renewal_category_visa_status')->orderBy('id')->get();
            $assigned = [];
            foreach ($pivots as $pivot) {
                $statusId = (int) $pivot->visa_status_id;
                if (isset($assigned[$statusId])) {
                    continue;
                }
                DB::table('visa_statuses')->where('id', $statusId)->update([
                    'visa_renewal_category_id' => (int) $pivot->visa_renewal_category_id,
                    'display_order' => (int) $pivot->display_order,
                ]);
                $assigned[$statusId] = true;
            }

            if ($defaultId) {
                DB::table('visa_statuses')
                    ->whereNull('visa_renewal_category_id')
                    ->update(['visa_renewal_category_id' => $defaultId]);
            }

            Schema::dropIfExists('visa_renewal_category_visa_status');
        }
    }

    private function backfillPivotAndDedupe(bool $hasCompanyId, bool $hasSoftDeletes): void
    {
        $query = DB::table('visa_statuses');
        if ($hasSoftDeletes) {
            $query->whereNull('deleted_at');
        }

        $rows = $query
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'visa_renewal_category_id',
                'display_order',
                $hasCompanyId ? 'company_id' : DB::raw('NULL as company_id'),
            ]);

        $defaultCategoryIds = DB::table('visa_renewal_categories')
            ->where('is_default', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $groups = [];
        foreach ($rows as $row) {
            $companyKey = $hasCompanyId ? (string) ($row->company_id ?? 'null') : 'all';
            $nameKey = mb_strtolower(trim((string) $row->name));
            $groups[$companyKey . '|' . $nameKey][] = $row;
        }

        $idRemap = [];
        $now = now();

        foreach ($groups as $groupRows) {
            usort($groupRows, function ($a, $b) use ($defaultCategoryIds) {
                $aDefault = in_array((int) $a->visa_renewal_category_id, $defaultCategoryIds, true) ? 0 : 1;
                $bDefault = in_array((int) $b->visa_renewal_category_id, $defaultCategoryIds, true) ? 0 : 1;
                if ($aDefault !== $bDefault) {
                    return $aDefault <=> $bDefault;
                }

                return ((int) $a->id) <=> ((int) $b->id);
            });

            $canonical = $groupRows[0];
            $canonicalId = (int) $canonical->id;

            foreach ($groupRows as $row) {
                $statusId = (int) $row->id;
                $categoryId = (int) ($row->visa_renewal_category_id ?? 0);
                $idRemap[$statusId] = $canonicalId;

                if ($categoryId <= 0) {
                    continue;
                }

                $exists = DB::table('visa_renewal_category_visa_status')
                    ->where('visa_renewal_category_id', $categoryId)
                    ->where('visa_status_id', $canonicalId)
                    ->exists();

                if (! $exists) {
                    DB::table('visa_renewal_category_visa_status')->insert([
                        'visa_renewal_category_id' => $categoryId,
                        'visa_status_id' => $canonicalId,
                        'display_order' => max(1, (int) ($row->display_order ?? 1)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $duplicateIds = collect($groupRows)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id !== $canonicalId)
                ->values()
                ->all();

            if ($duplicateIds === []) {
                continue;
            }

            if ($hasSoftDeletes) {
                DB::table('visa_statuses')
                    ->whereIn('id', $duplicateIds)
                    ->update(['deleted_at' => $now, 'updated_at' => $now]);
            } else {
                DB::table('visa_statuses')->whereIn('id', $duplicateIds)->delete();
            }
        }

        // Stash remap for settings step.
        $this->statusIdRemap = $idRemap;
    }

    /** @var array<int, int> */
    private array $statusIdRemap = [];

    private function remapTopStatusSettings(): void
    {
        if ($this->statusIdRemap === [] || ! Schema::hasTable('settings')) {
            return;
        }

        $rows = DB::table('settings')
            ->where('name', 'visa_expense_top_status_ids')
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $raw = (string) ($row->value ?? '');
            if ($raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                continue;
            }

            $mapped = collect($decoded)
                ->map(function ($id) {
                    $id = (int) $id;
                    if ($id <= 0) {
                        return null;
                    }

                    return $this->statusIdRemap[$id] ?? $id;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            DB::table('settings')->where('id', $row->id)->update([
                'value' => json_encode(array_map('intval', $mapped)),
            ]);
        }
    }

    private function restoreUniqueNameConstraint(bool $hasCompanyId): void
    {
        if ($hasCompanyId) {
            if (! $this->indexExists('visa_statuses', 'visa_statuses_company_id_name_unique')) {
                // Soft-deleted rows may still collide; use a partial approach via unique only on active is not portable.
                // Drop soft-deleted duplicates with same name before adding unique if needed.
                $this->purgeSoftDeletedNameCollisions(true);
                Schema::table('visa_statuses', function (Blueprint $table) {
                    $table->unique(['company_id', 'name'], 'visa_statuses_company_id_name_unique');
                });
            }

            return;
        }

        if (! $this->indexExists('visa_statuses', 'visa_statuses_name_unique')) {
            $this->purgeSoftDeletedNameCollisions(false);
            Schema::table('visa_statuses', function (Blueprint $table) {
                $table->unique('name', 'visa_statuses_name_unique');
            });
        }
    }

    private function purgeSoftDeletedNameCollisions(bool $hasCompanyId): void
    {
        if (! Schema::hasColumn('visa_statuses', 'deleted_at')) {
            return;
        }

        $active = DB::table('visa_statuses')->whereNull('deleted_at')->get(['id', 'name', 'company_id']);
        $activeKeys = [];
        foreach ($active as $row) {
            $key = ($hasCompanyId ? (string) ($row->company_id ?? 'null') : 'all')
                . '|' . mb_strtolower(trim((string) $row->name));
            $activeKeys[$key] = true;
        }

        $deleted = DB::table('visa_statuses')->whereNotNull('deleted_at')->get(['id', 'name', 'company_id']);
        $forceDeleteIds = [];
        foreach ($deleted as $row) {
            $key = ($hasCompanyId ? (string) ($row->company_id ?? 'null') : 'all')
                . '|' . mb_strtolower(trim((string) $row->name));
            if (isset($activeKeys[$key])) {
                $forceDeleteIds[] = (int) $row->id;
            }
        }

        if ($forceDeleteIds !== []) {
            DB::table('visa_statuses')->whereIn('id', $forceDeleteIds)->delete();
        }
    }

    private function dropUniqueIndexesOnName(): void
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT DISTINCT INDEX_NAME AS index_name
                 FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND column_name = ? AND non_unique = 0
                   AND INDEX_NAME <> ?',
                [$database, 'visa_statuses', 'name', 'PRIMARY']
            );
            foreach ($rows as $row) {
                $indexName = (string) $row->index_name;
                if ($indexName === '') {
                    continue;
                }
                Schema::table('visa_statuses', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }

            return;
        }

        foreach (['visa_statuses_name_unique', 'visa_statuses_company_id_name_unique'] as $indexName) {
            if ($this->indexExists('visa_statuses', $indexName)) {
                Schema::table('visa_statuses', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        );

        return $row !== null;
    }
};
