<?php

namespace Tests\Unit\VoucherApproval;

use App\Models\Transactions;
use App\Models\Vouchers;
use App\Services\VoucherApproval\VoucherApprovalLedger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoucherApprovalLedgerHoldTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('transactions') || ! Schema::hasTable('vouchers')) {
            $this->markTestSkipped('transactions/vouchers tables missing.');
        }
        if (! Schema::hasColumn('transactions', 'approval_held_at')) {
            $this->markTestSkipped('approval_held_at column not migrated yet.');
        }
        if (! Schema::hasColumn('vouchers', 'approval_status')) {
            $this->markTestSkipped('voucher approval columns missing.');
        }
    }

    public function test_restore_for_voucher_only_clears_approval_holds(): void
    {
        $transCode = (string) random_int(90000000, 99999999);

        $voucher = Vouchers::query()->create([
            'trans_code' => $transCode,
            'voucher_type' => 'JV',
            'amount' => 10,
            'trans_date' => now()->toDateString(),
            'billing_month' => now()->startOfMonth()->toDateString(),
            'approval_status' => 'approved',
            'status' => 1,
        ]);

        $held = Transactions::query()->create([
            'trans_code' => $transCode,
            'trans_date' => now()->toDateString(),
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'account_id' => 1,
            'billing_month' => now()->startOfMonth()->toDateString(),
            'narration' => 'approval hold',
            'debit' => 10,
            'credit' => 0,
            'deleted_at' => now(),
            'approval_held_at' => now(),
        ]);

        $orphanSoftDelete = Transactions::query()->create([
            'trans_code' => $transCode,
            'trans_date' => now()->toDateString(),
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'account_id' => 1,
            'billing_month' => now()->startOfMonth()->toDateString(),
            'narration' => 'rebuild orphan soft-delete',
            'debit' => 5,
            'credit' => 0,
            'deleted_at' => now(),
            'approval_held_at' => null,
            'deleted_by' => null,
        ]);

        VoucherApprovalLedger::restoreForVoucher($voucher->fresh());

        $this->assertNull($held->fresh()->deleted_at);
        $this->assertNull($held->fresh()->approval_held_at);
        $this->assertNotNull($orphanSoftDelete->fresh()->deleted_at);
        $this->assertNull($orphanSoftDelete->fresh()->approval_held_at);
    }
}
