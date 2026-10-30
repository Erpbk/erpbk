<?php

use App\Services\VoucherApproval\VoucherApprovalLedger;
use Illuminate\Database\Migrations\Migration;

/**
 * Un-hide ledger lines that were soft-held for approval so edit forms and
 * voucher ledgers show entries for every user (supervision-only approval).
 */
return new class extends Migration
{
    public function up(): void
    {
        VoucherApprovalLedger::restoreAllApprovalHolds();
    }

    public function down(): void
    {
        // Irreversible restore.
    }
};
