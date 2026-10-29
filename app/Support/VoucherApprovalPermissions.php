<?php

namespace App\Support;

use App\Models\User;
use App\Models\Vouchers;

/**
 * Authorize approve / reject / override for a voucher type (Spatie leaves).
 */
class VoucherApprovalPermissions
{
    public static function canApprove(string $voucherType, ?User $user = null): bool
    {
        return self::canTyped($voucherType, 'approve', $user);
    }

    public static function canReject(string $voucherType, ?User $user = null): bool
    {
        return self::canTyped($voucherType, 'reject', $user);
    }

    public static function canForceApprove(?User $user = null): bool
    {
        return self::canGlobal('force_approve', $user);
    }

    public static function canReopen(?User $user = null): bool
    {
        return self::canGlobal('reopen', $user);
    }

    public static function canRevise(?User $user = null): bool
    {
        return self::canGlobal('revise', $user);
    }

    public static function canViewDashboard(?User $user = null): bool
    {
        return self::canGlobal('dashboard_view', $user)
            || self::canAnyApproveOrReject($user);
    }

    public static function canApproveVoucher(Vouchers $voucher, ?User $user = null): bool
    {
        $user = self::resolveUser($user);
        if ($user === null) {
            return false;
        }

        if (self::isSelfBlocked($voucher, $user) && ! self::canForceApprove($user)) {
            return false;
        }

        return self::canApprove((string) $voucher->voucher_type, $user)
            || self::canForceApprove($user);
    }

    public static function canRejectVoucher(Vouchers $voucher, ?User $user = null): bool
    {
        $user = self::resolveUser($user);
        if ($user === null) {
            return false;
        }

        if (self::isSelfBlocked($voucher, $user) && ! self::canForceApprove($user)) {
            return false;
        }

        return self::canReject((string) $voucher->voucher_type, $user)
            || self::canForceApprove($user);
    }

    public static function authorizeApprove(Vouchers $voucher, ?User $user = null): void
    {
        if (! self::canApproveVoucher($voucher, $user)) {
            abort(403, 'Unauthorized: you cannot approve this voucher.');
        }
    }

    public static function authorizeReject(Vouchers $voucher, ?User $user = null): void
    {
        if (! self::canRejectVoucher($voucher, $user)) {
            abort(403, 'Unauthorized: you cannot reject this voucher.');
        }
    }

    public static function isSelfBlocked(Vouchers $voucher, User $user): bool
    {
        if (! VoucherApprovalRegistry::preventSelfApproval()) {
            return false;
        }

        $creatorId = $voucher->Created_By ?? $voucher->created_by ?? null;
        if ($creatorId === null || $creatorId === '') {
            return false;
        }

        return (int) $creatorId === (int) $user->id;
    }

    protected static function canTyped(string $voucherType, string $action, ?User $user = null): bool
    {
        if (! VoucherApprovalRegistry::enabled()) {
            return false;
        }

        $user = self::resolveUser($user);
        if ($user === null) {
            return false;
        }

        if (VoucherApprovalRegistry::adminBypass() && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $permissions = VoucherApprovalRegistry::permissionCandidates($voucherType, $action);
        if ($permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (user_can($permission, $user)) {
                return true;
            }
        }

        return false;
    }

    protected static function canGlobal(string $key, ?User $user = null): bool
    {
        $user = self::resolveUser($user);
        if ($user === null) {
            return false;
        }

        if (VoucherApprovalRegistry::adminBypass() && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $permission = VoucherApprovalRegistry::globalPermission($key);
        if ($permission === null) {
            return false;
        }

        return user_can($permission, $user);
    }

    protected static function canAnyApproveOrReject(?User $user = null): bool
    {
        $user = self::resolveUser($user);
        if ($user === null) {
            return false;
        }

        if (VoucherApprovalRegistry::adminBypass() && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        foreach (VoucherApprovalRegistry::types() as $code => $_) {
            if (self::canApprove($code, $user) || self::canReject($code, $user)) {
                return true;
            }
        }

        return false;
    }

    protected static function resolveUser(?User $user): ?User
    {
        if ($user !== null) {
            return $user;
        }

        $auth = auth()->user();

        return $auth instanceof User ? $auth : null;
    }
}
