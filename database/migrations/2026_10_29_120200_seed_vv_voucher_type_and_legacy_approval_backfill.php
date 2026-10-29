<?php

use App\Models\Vouchers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Ensure VV (VAT Return) exists in voucher_types.
 * 2) Legacy backfill: mark ALL existing vouchers approved with a history entry.
 *
 * Do NOT run against production without explicit confirmation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voucher_types')) {
            $now = now();
            if (! DB::table('voucher_types')->where('code', 'VV')->exists()) {
                $row = [
                    'code' => 'VV',
                    'label' => 'VAT Return',
                    'display_order' => 99,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('voucher_types', 'company_id')) {
                    $row['company_id'] = DB::table('companies')->value('id');
                }
                if (! Schema::hasColumn('voucher_types', 'company_id') || $row['company_id'] ?? true) {
                    if (Schema::hasColumn('voucher_types', 'company_id') && empty($row['company_id'])) {
                        // Skip insert when company_id is required but no company exists.
                    } else {
                        DB::table('voucher_types')->insert($row);
                    }
                }
            }
        }

        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'approval_status')) {
            return;
        }

        $batchSize = 500;

        Vouchers::withTrashed()
            ->orderBy('id')
            ->chunkById($batchSize, function ($vouchers) {
                $ids = $vouchers->pluck('id')->all();
                if ($ids === []) {
                    return;
                }

                DB::table('vouchers')->whereIn('id', $ids)->update([
                    'approval_status' => 'approved',
                    'approved_at' => DB::raw('COALESCE(approved_at, created_at, NOW())'),
                    'submitted_at' => DB::raw('COALESCE(submitted_at, created_at, NOW())'),
                    'approval_locked_at' => DB::raw('COALESCE(approval_locked_at, created_at, NOW())'),
                    'updated_at' => now(),
                ]);

                if (! Schema::hasTable('voucher_approval_events')) {
                    return;
                }

                $now = now();
                $rows = [];
                foreach ($vouchers as $voucher) {
                    $rows[] = [
                        'company_id' => $voucher->company_id ?? null,
                        'approvable_type' => Vouchers::class,
                        'approvable_id' => $voucher->id,
                        'action' => 'legacy_backfill',
                        'from_status' => null,
                        'to_status' => 'approved',
                        'user_id' => null,
                        'user_name' => 'System',
                        'remark' => 'legacy backfill',
                        'ip_address' => null,
                        'details' => json_encode(['source' => 'migration_2026_10_29_120200']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table('voucher_approval_events')->insert($chunk);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('voucher_approval_events')) {
            $legacyIds = DB::table('voucher_approval_events')
                ->where('action', 'legacy_backfill')
                ->where('remark', 'legacy backfill')
                ->pluck('approvable_id');

            if ($legacyIds->isNotEmpty() && Schema::hasColumn('vouchers', 'approval_status')) {
                DB::table('vouchers')->whereIn('id', $legacyIds)->update([
                    'approval_status' => 'pending_approval',
                    'approved_by' => null,
                    'approved_at' => null,
                    'approval_locked_at' => null,
                    'updated_at' => now(),
                ]);
            }

            DB::table('voucher_approval_events')
                ->where('action', 'legacy_backfill')
                ->where('remark', 'legacy backfill')
                ->delete();
        }

        if (Schema::hasTable('voucher_types')) {
            DB::table('voucher_types')->where('code', 'VV')->delete();
        }
    }
};
