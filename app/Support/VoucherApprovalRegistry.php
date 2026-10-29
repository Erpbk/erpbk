<?php

namespace App\Support;

class VoucherApprovalRegistry
{
    public static function enabled(): bool
    {
        return (bool) config('voucher_approval.enabled', true);
    }

    public static function deferPosting(): bool
    {
        return self::enabled() && (bool) config('voucher_approval.defer_posting', true);
    }

    public static function preventSelfApproval(): bool
    {
        return (bool) config('voucher_approval.prevent_self_approval', true);
    }

    public static function adminBypass(): bool
    {
        return (bool) config('voucher_approval.admin_bypass', true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function types(): array
    {
        return (array) config('voucher_approval.types', []);
    }

    public static function definition(string $voucherType): ?array
    {
        $code = strtoupper(trim($voucherType));

        return self::types()[$code] ?? null;
    }

    public static function label(string $voucherType): string
    {
        return self::definition($voucherType)['label'] ?? strtoupper(trim($voucherType));
    }

    public static function module(string $voucherType): ?string
    {
        return self::definition($voucherType)['module'] ?? null;
    }

    public static function approvePermission(string $voucherType): ?string
    {
        return self::definition($voucherType)['approve'] ?? null;
    }

    public static function rejectPermission(string $voucherType): ?string
    {
        return self::definition($voucherType)['reject'] ?? null;
    }

    /**
     * Permission leaf names that grant approve/reject for this voucher type.
     * Rider salary/payment vouchers are stored as PV but use riders_payments_* leaves
     * (same pattern as RiderVoucherPermissions::codeFromVoucherType).
     *
     * @return list<string>
     */
    public static function permissionCandidates(string $voucherType, string $action): array
    {
        $action = strtolower(trim($action));
        $code = strtoupper(trim($voucherType));
        $primary = $action === 'reject'
            ? self::rejectPermission($code)
            : self::approvePermission($code);

        $names = [];
        if (is_string($primary) && $primary !== '') {
            $names[] = $primary;
        }

        if ($code === 'PV') {
            $rider = $action === 'reject' ? 'riders_payments_reject' : 'riders_payments_approve';
            $names[] = $rider;
        }

        return array_values(array_unique($names));
    }

    public static function globalPermission(string $key): ?string
    {
        return config('voucher_approval.permissions.' . $key);
    }

    /**
     * All typed approve/reject leaf names (for permission-change logging filters).
     *
     * @return list<string>
     */
    public static function allTypedPermissionNames(): array
    {
        $names = [];
        foreach (self::types() as $def) {
            if (! empty($def['approve'])) {
                $names[] = $def['approve'];
            }
            if (! empty($def['reject'])) {
                $names[] = $def['reject'];
            }
        }

        foreach ((array) config('voucher_approval.permissions', []) as $name) {
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Flat list of additional_permissions entries for sync.
     *
     * @return list<string>
     */
    public static function additionalPermissionLeaves(): array
    {
        $leaves = [];

        foreach (self::types() as $code => $def) {
            // Rider typed leaves are created via submodule extra_actions.
            if (str_starts_with((string) ($def['approve'] ?? ''), 'riders_')) {
                continue;
            }
            if (! empty($def['approve'])) {
                $leaves[] = $def['approve'];
            }
            if (! empty($def['reject'])) {
                $leaves[] = $def['reject'];
            }
        }

        foreach (['force_approve', 'reopen', 'revise'] as $key) {
            $name = self::globalPermission($key);
            if ($name) {
                $leaves[] = $name;
            }
        }

        return array_values(array_unique($leaves));
    }
}
