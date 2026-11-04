<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vouchers;
use App\Support\VoucherApprovalPermissions;

class VoucherApprovalPolicy
{
    public function approve(User $user, Vouchers $voucher): bool
    {
        return VoucherApprovalPermissions::canApproveVoucher($voucher, $user);
    }

    public function reject(User $user, Vouchers $voucher): bool
    {
        return VoucherApprovalPermissions::canRejectVoucher($voucher, $user);
    }

    public function forceApprove(User $user, Vouchers $voucher): bool
    {
        return VoucherApprovalPermissions::canForceApprove($user);
    }

    public function reopen(User $user, Vouchers $voucher): bool
    {
        return VoucherApprovalPermissions::canReopen($user);
    }

    public function revise(User $user, Vouchers $voucher): bool
    {
        return VoucherApprovalPermissions::canRevise($user);
    }

    public function resubmit(User $user, Vouchers $voucher): bool
    {
        if (! $voucher->isApprovalRejected()) {
            return false;
        }

        $creatorId = (int) ($voucher->Created_By ?? 0);
        if ($creatorId === (int) $user->id) {
            return true;
        }

        return (method_exists($user, 'isAdmin') && $user->isAdmin())
            || VoucherApprovalPermissions::canReopen($user);
    }

    public function viewDashboard(User $user): bool
    {
        return VoucherApprovalPermissions::canViewDashboard($user);
    }
}
