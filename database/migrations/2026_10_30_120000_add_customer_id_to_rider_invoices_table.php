<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoices')) {
            return;
        }

        if (! Schema::hasColumn('rider_invoices', 'customer_id')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('rider_id')->index();
            });
        }

        $this->backfillCustomerIds();
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_invoices') && Schema::hasColumn('rider_invoices', 'customer_id')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }
    }

    /**
     * Resolve invoice project from rider project_change history for the billing month,
     * falling back to the rider's current customer_id.
     */
    private function backfillCustomerIds(): void
    {
        if (! Schema::hasColumn('rider_invoices', 'customer_id')) {
            return;
        }

        $hasHistory = Schema::hasTable('rider_histories')
            && Schema::hasColumn('rider_histories', 'event_type')
            && Schema::hasColumn('rider_histories', 'effective_date');

        $riderCurrent = [];
        if (Schema::hasTable('riders') && Schema::hasColumn('riders', 'customer_id')) {
            $riderCurrent = DB::table('riders')
                ->whereNotNull('customer_id')
                ->pluck('customer_id', 'id')
                ->map(fn ($id) => $id !== null && $id !== '' ? (int) $id : null)
                ->all();
        }

        $historyByRider = [];
        if ($hasHistory) {
            $rows = DB::table('rider_histories')
                ->where('event_type', 'project_change')
                ->orderBy('effective_date')
                ->orderBy('id')
                ->get(['id', 'rider_id', 'customer_id', 'effective_date', 'meta']);

            foreach ($rows as $row) {
                $riderId = (int) $row->rider_id;
                $meta = is_string($row->meta) ? json_decode($row->meta, true) : (array) ($row->meta ?? []);
                if (! is_array($meta)) {
                    $meta = [];
                }

                $newId = $row->customer_id ?? ($meta['new_customer_id'] ?? null);
                $oldId = $meta['old_customer_id'] ?? null;

                $historyByRider[$riderId][] = [
                    'effective_date' => (string) $row->effective_date,
                    'new_customer_id' => $newId !== null && $newId !== '' ? (int) $newId : null,
                    'old_customer_id' => $oldId !== null && $oldId !== '' ? (int) $oldId : null,
                ];
            }
        }

        DB::table('rider_invoices')
            ->whereNull('customer_id')
            ->orderBy('id')
            ->select(['id', 'rider_id', 'billing_month'])
            ->chunkById(500, function ($invoices) use ($historyByRider, $riderCurrent) {
                foreach ($invoices as $invoice) {
                    $riderId = (int) $invoice->rider_id;
                    $customerId = $this->resolveCustomerForBillingMonth(
                        $historyByRider[$riderId] ?? [],
                        (string) $invoice->billing_month,
                        $riderCurrent[$riderId] ?? null
                    );

                    if ($customerId === null) {
                        continue;
                    }

                    DB::table('rider_invoices')
                        ->where('id', $invoice->id)
                        ->whereNull('customer_id')
                        ->update(['customer_id' => $customerId]);
                }
            });
    }

    /**
     * @param  list<array{effective_date: string, new_customer_id: ?int, old_customer_id: ?int}>  $changes
     */
    private function resolveCustomerForBillingMonth(array $changes, string $billingMonth, ?int $fallback): ?int
    {
        try {
            $monthEnd = Carbon::parse($billingMonth)->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            return $fallback;
        }

        $latestOnOrBefore = null;
        foreach ($changes as $change) {
            if ($change['effective_date'] <= $monthEnd) {
                $latestOnOrBefore = $change;
            }
        }

        if ($latestOnOrBefore !== null && $latestOnOrBefore['new_customer_id'] !== null) {
            return $latestOnOrBefore['new_customer_id'];
        }

        foreach ($changes as $change) {
            if ($change['effective_date'] > $monthEnd) {
                if ($change['old_customer_id'] !== null) {
                    return $change['old_customer_id'];
                }
                break;
            }
        }

        return $fallback;
    }
};
