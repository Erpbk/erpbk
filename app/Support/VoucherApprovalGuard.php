<?php

namespace App\Support;

use App\Models\Vouchers;

/**
 * Approval is supervision-only and must not block normal create/edit/ledger flows.
 */
class VoucherApprovalGuard
{
    /**
     * No-op: approved vouchers remain editable under normal voucher edit permissions.
     */
    public static function assertMayEdit(Vouchers $voucher, $user = null): void
    {
        // Intentionally empty — supervision must not lock voucher editing.
    }
}
