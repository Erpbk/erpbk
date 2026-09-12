<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sim_invoice_items')) {
            return;
        }

        if (! Schema::hasColumn('sim_invoice_items', 'item_id')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_id')->nullable()->after('sim_id');
                $table->decimal('qty', 10, 2)->default(1)->after('item_id');
                $table->decimal('rate', 12, 2)->default(0)->after('qty');
                $table->decimal('discount', 12, 2)->default(0)->after('rate');
                $table->decimal('tax', 12, 2)->default(0)->after('discount');
                $table->decimal('amount', 12, 2)->default(0)->after('tax');
            });
        }

        $vatPercent = (float) (DB::table('settings')->where('name', 'vat_percentage')->value('value') ?? 5);
        $itemsByCompany = $this->ensureDefaultSimItems($vatPercent);
        $hasCompanyId = Schema::hasColumn('sim_invoice_items', 'company_id');
        $hasBranchId = Schema::hasColumn('sim_invoice_items', 'branch_id');

        if (Schema::hasColumn('sim_invoice_items', 'rental_amount')) {
            $oldRows = DB::table('sim_invoice_items')->get();
            $invoiceCompanies = $this->invoiceCompanyMap($oldRows);
            $newRows = [];
            $now = now();

            foreach ($oldRows as $row) {
                $companyId = $this->resolveRowCompanyId($row, $invoiceCompanies);
                if ($companyId === null || ! isset($itemsByCompany[$companyId])) {
                    continue;
                }

                $itemIds = $itemsByCompany[$companyId];
                $taxRate = (float) ($row->tax_rate ?? $vatPercent);
                $buckets = [
                    $itemIds['monthly'] => (float) ($row->rental_amount ?? 0),
                    $itemIds['additional'] => (float) ($row->additional_charges ?? 0),
                    $itemIds['intl'] => (float) ($row->international_usage_charges ?? 0),
                ];

                $nonZero = array_filter($buckets, fn ($v) => abs($v) > 0.00001);
                if (empty($nonZero)) {
                    continue;
                }

                $exclTotal = array_sum($nonZero);
                $oldTaxAmount = (float) ($row->tax_amount ?? 0);
                if ($oldTaxAmount <= 0 && $taxRate > 0) {
                    $oldTaxAmount = round($exclTotal * ($taxRate / 100), 2);
                }

                $allocatedTax = 0.0;
                $keys = array_keys($nonZero);
                $lastKey = end($keys);

                foreach ($nonZero as $itemId => $rate) {
                    if ($exclTotal > 0) {
                        $lineTax = ($itemId === $lastKey)
                            ? round($oldTaxAmount - $allocatedTax, 2)
                            : round($oldTaxAmount * ($rate / $exclTotal), 2);
                    } else {
                        $lineTax = 0.0;
                    }
                    $allocatedTax += $lineTax;

                    $newRow = [
                        'inv_id' => $row->inv_id,
                        'sim_id' => $row->sim_id,
                        'item_id' => $itemId,
                        'qty' => 1,
                        'rate' => round($rate, 2),
                        'discount' => 0,
                        'tax' => $lineTax,
                        'amount' => round($rate + $lineTax, 2),
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $now,
                    ];

                    if ($hasCompanyId) {
                        $newRow['company_id'] = $companyId;
                    }
                    if ($hasBranchId) {
                        $newRow['branch_id'] = $row->branch_id ?? null;
                    }

                    $newRows[] = $newRow;
                }
            }

            DB::table('sim_invoice_items')->delete();

            foreach (array_chunk($newRows, 500) as $chunk) {
                DB::table('sim_invoice_items')->insert($chunk);
            }
        }

        $dropCols = [];
        foreach ([
            'rental_amount',
            'additional_charges',
            'international_usage_charges',
            'tax_rate',
            'tax_amount',
            'total_amount',
        ] as $col) {
            if (Schema::hasColumn('sim_invoice_items', $col)) {
                $dropCols[] = $col;
            }
        }
        if (! empty($dropCols)) {
            Schema::table('sim_invoice_items', function (Blueprint $table) use ($dropCols) {
                $table->dropColumn($dropCols);
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM sim_invoice_items'))->pluck('Key_name')->unique();
        if (! $indexes->contains('sim_invoice_items_inv_sim_item_unique')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->unique(['inv_id', 'sim_id', 'item_id'], 'sim_invoice_items_inv_sim_item_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sim_invoice_items')) {
            return;
        }

        Schema::table('sim_invoice_items', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM sim_invoice_items'))->pluck('Key_name')->unique();
            if ($indexes->contains('sim_invoice_items_inv_sim_item_unique')) {
                $table->dropUnique('sim_invoice_items_inv_sim_item_unique');
            }
        });

        if (! Schema::hasColumn('sim_invoice_items', 'rental_amount')) {
            Schema::table('sim_invoice_items', function (Blueprint $table) {
                $table->decimal('rental_amount', 10, 2)->default(0)->after('sim_id');
                $table->decimal('additional_charges', 10, 2)->default(0)->after('rental_amount');
                $table->decimal('international_usage_charges', 10, 2)->default(0)->after('additional_charges');
                $table->decimal('tax_rate', 8, 2)->default(0)->after('international_usage_charges');
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
                $table->decimal('total_amount', 12, 2)->default(0)->after('tax_amount');
            });
        }

        $hasCompanyId = Schema::hasColumn('sim_invoice_items', 'company_id');
        $hasBranchId = Schema::hasColumn('sim_invoice_items', 'branch_id');
        $itemsByCompany = $this->findDefaultSimItemIdsByCompany();
        $rows = DB::table('sim_invoice_items')->get();
        $collapsed = [];

        foreach ($rows->groupBy(fn ($r) => $r->inv_id . ':' . $r->sim_id) as $group) {
            $first = $group->first();
            $companyId = $hasCompanyId ? ($first->company_id ?? null) : null;
            $itemIds = $companyId !== null
                ? ($itemsByCompany[(int) $companyId] ?? $this->emptyItemIds())
                : $this->firstAvailableItemIds($itemsByCompany);

            $rental = 0.0;
            $additional = 0.0;
            $intl = 0.0;
            $tax = 0.0;
            $amount = 0.0;

            foreach ($group as $row) {
                $rate = (float) ($row->rate ?? 0);
                $tax += (float) ($row->tax ?? 0);
                $amount += (float) ($row->amount ?? 0);
                if ((int) $row->item_id === (int) ($itemIds['monthly'] ?? 0)) {
                    $rental += $rate;
                } elseif ((int) $row->item_id === (int) ($itemIds['additional'] ?? 0)) {
                    $additional += $rate;
                } elseif ((int) $row->item_id === (int) ($itemIds['intl'] ?? 0)) {
                    $intl += $rate;
                } else {
                    $additional += $rate;
                }
            }

            $excl = $rental + $additional + $intl;
            $collapsedRow = [
                'inv_id' => $first->inv_id,
                'sim_id' => $first->sim_id,
                'rental_amount' => round($rental, 2),
                'additional_charges' => round($additional, 2),
                'international_usage_charges' => round($intl, 2),
                'tax_rate' => $excl > 0 ? round(($tax / $excl) * 100, 2) : 0,
                'tax_amount' => round($tax, 2),
                'total_amount' => round($amount, 2),
                'created_at' => $first->created_at,
                'updated_at' => now(),
            ];

            if ($hasCompanyId) {
                $collapsedRow['company_id'] = $first->company_id ?? null;
            }
            if ($hasBranchId) {
                $collapsedRow['branch_id'] = $first->branch_id ?? null;
            }

            $collapsed[] = $collapsedRow;
        }

        DB::table('sim_invoice_items')->delete();
        foreach (array_chunk($collapsed, 500) as $chunk) {
            DB::table('sim_invoice_items')->insert($chunk);
        }

        Schema::table('sim_invoice_items', function (Blueprint $table) {
            foreach (['item_id', 'qty', 'rate', 'discount', 'tax', 'amount'] as $col) {
                if (Schema::hasColumn('sim_invoice_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Create default SIM catalog items only for company_ids present on sim_invoice_items.
     *
     * @return array<int, array{monthly:int,additional:int,intl:int}>
     */
    private function ensureDefaultSimItems(float $vatPercent): array
    {
        if (! Schema::hasTable('items') || ! Schema::hasColumn('items', 'company_id')) {
            return [];
        }

        $companyIds = $this->companyIdsFromSimInvoiceItems();
        if ($companyIds->isEmpty()) {
            return [];
        }

        // Same default names per company require dropping the global name unique.
        $this->dropItemsNameUniqueIfNeeded();

        $defs = [
            'monthly' => 'Monthly Rental',
            'additional' => 'Additional Charges',
            'intl' => 'International Charges',
        ];

        $byCompany = [];
        foreach ($companyIds as $companyId) {
            $ids = [];
            foreach ($defs as $key => $name) {
                $existing = DB::table('items')
                    ->where('name', $name)
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->first();

                if (! $existing) {
                    // Claim an unscoped legacy row once, then create per remaining companies.
                    $existing = DB::table('items')
                        ->where('name', $name)
                        ->whereNull('company_id')
                        ->whereNull('deleted_at')
                        ->orderBy('id')
                        ->first();

                    if ($existing) {
                        DB::table('items')->where('id', $existing->id)->update([
                            'company_id' => $companyId,
                            'updated_at' => now(),
                        ]);
                        $existing->company_id = $companyId;
                    }
                }

                if ($existing) {
                    $owner = json_decode($existing->owner ?? '[]', true) ?: [];
                    if (! in_array('sim', $owner, true)) {
                        $owner[] = 'sim';
                        DB::table('items')->where('id', $existing->id)->update([
                            'owner' => json_encode(array_values($owner)),
                            'updated_at' => now(),
                        ]);
                    }
                    $ids[$key] = (int) $existing->id;
                    continue;
                }

                $ids[$key] = (int) DB::table('items')->insertGetId([
                    'name' => $name,
                    'detail' => 'Default SIM invoice charge type',
                    'price' => 1,
                    'cost' => 1,
                    'vat' => $vatPercent,
                    'owner' => json_encode(['sim']),
                    'status' => 1,
                    'company_id' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $byCompany[(int) $companyId] = $ids;
        }

        return $byCompany;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function companyIdsFromSimInvoiceItems()
    {
        $ids = collect();

        if (Schema::hasColumn('sim_invoice_items', 'company_id')) {
            $ids = $ids->merge(
                DB::table('sim_invoice_items')
                    ->whereNotNull('company_id')
                    ->distinct()
                    ->pluck('company_id')
            );
        }

        if (
            $ids->isEmpty()
            && Schema::hasTable('sim_invoices')
            && Schema::hasColumn('sim_invoices', 'company_id')
        ) {
            $ids = $ids->merge(
                DB::table('sim_invoice_items as sii')
                    ->join('sim_invoices as si', 'si.id', '=', 'sii.inv_id')
                    ->whereNotNull('si.company_id')
                    ->distinct()
                    ->pluck('si.company_id')
            );
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $oldRows
     * @return array<int, int>
     */
    private function invoiceCompanyMap($oldRows): array
    {
        if (
            ! Schema::hasTable('sim_invoices')
            || ! Schema::hasColumn('sim_invoices', 'company_id')
        ) {
            return [];
        }

        $invIds = $oldRows->pluck('inv_id')->filter()->unique()->values();
        if ($invIds->isEmpty()) {
            return [];
        }

        return DB::table('sim_invoices')
            ->whereIn('id', $invIds)
            ->whereNotNull('company_id')
            ->pluck('company_id', 'id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $invoiceCompanies
     */
    private function resolveRowCompanyId(object $row, array $invoiceCompanies): ?int
    {
        if (isset($row->company_id) && $row->company_id !== null && $row->company_id !== '') {
            return (int) $row->company_id;
        }

        $invId = (int) ($row->inv_id ?? 0);
        if ($invId && isset($invoiceCompanies[$invId])) {
            return $invoiceCompanies[$invId];
        }

        return null;
    }

    private function dropItemsNameUniqueIfNeeded(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM items'));
        $uniqueNameIndexes = $indexes
            ->filter(fn ($idx) => (int) $idx->Non_unique === 0 && $idx->Column_name === 'name')
            ->pluck('Key_name')
            ->unique()
            ->values();

        foreach ($uniqueNameIndexes as $keyName) {
            // Only drop if this unique is name-only (not a composite that includes company_id).
            $cols = $indexes->where('Key_name', $keyName)->pluck('Column_name')->values();
            if ($cols->count() === 1 && $cols->first() === 'name') {
                Schema::table('items', function (Blueprint $table) use ($keyName) {
                    $table->dropUnique($keyName);
                });
            }
        }
    }

    /**
     * @return array<int, array{monthly:?int,additional:?int,intl:?int}>
     */
    private function findDefaultSimItemIdsByCompany(): array
    {
        if (! Schema::hasTable('items') || ! Schema::hasColumn('items', 'company_id')) {
            return [];
        }

        $defs = [
            'monthly' => 'Monthly Rental',
            'additional' => 'Additional Charges',
            'intl' => 'International Charges',
        ];

        $byCompany = [];
        foreach ($defs as $key => $name) {
            $rows = DB::table('items')
                ->where('name', $name)
                ->whereNotNull('company_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'company_id']);

            foreach ($rows as $row) {
                $companyId = (int) $row->company_id;
                $byCompany[$companyId] ??= $this->emptyItemIds();
                $byCompany[$companyId][$key] = (int) $row->id;
            }
        }

        return $byCompany;
    }

    /**
     * @return array{monthly:?int,additional:?int,intl:?int}
     */
    private function emptyItemIds(): array
    {
        return ['monthly' => null, 'additional' => null, 'intl' => null];
    }

    /**
     * @param  array<int, array{monthly:?int,additional:?int,intl:?int}>  $itemsByCompany
     * @return array{monthly:?int,additional:?int,intl:?int}
     */
    private function firstAvailableItemIds(array $itemsByCompany): array
    {
        foreach ($itemsByCompany as $ids) {
            return $ids;
        }

        return $this->emptyItemIds();
    }
};
