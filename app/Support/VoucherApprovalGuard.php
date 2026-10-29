<?php

namespace App\Support;

use App\Models\Vouchers;
use Illuminate\Validation\ValidationException;

/**
 * Shared guards for edit/update of vouchers under the approval workflow.
 */
class VoucherApprovalGuard
{
    /**
     * Approved vouchers are locked unless the user may revise.
     * Rejected vouchers may be edited by the creator (for resubmission).
     */
    public static function assertMayEdit(Vouchers $voucher, $user = null): void
    {
        if (! VoucherApprovalRegistry::enabled()) {
            return;
        }

        if ($voucher->isApprovalApproved()) {
            $user = $user ?? auth()->user();
            if ($user && VoucherApprovalPermissions::canRevise($user)) {
                return;
            }

            throw ValidationException::withMessages([
                'approval' => 'Approved vouchers are locked. A revise/reopen permission and remark are required to change them.',
            ]);
        }
    }
}
