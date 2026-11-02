<?php

namespace App\Support;

/**
 * Maps Employees module voucher codes to Spatie permission prefixes.
 */
class EmployeeVoucherPermissions
{
    /** Voucher types that use typed permission groups under Employees. */
    public const TYPED_CODES = ['PN', 'INC', 'AL', 'PAY'];

    /**
     * Permission prefix per voucher code (without trailing action).
     *
     * @var array<string, string>
     */
    public const PREFIX_BY_CODE = [
        'PAY' => 'employees_payments',
        'PN' => 'employees_penalty_voucher',
        'INC' => 'employees_incentive_voucher',
        'AL' => 'employees_advance_loan_voucher',
    ];

    public static function prefixForCode(string $code): ?string
    {
        $code = strtoupper(trim($code));

        return self::PREFIX_BY_CODE[$code] ?? null;
    }

    public static function permissionName(string $code, string $action): ?string
    {
        $prefix = self::prefixForCode($code);
        if ($prefix === null) {
            return null;
        }

        $action = strtolower(trim($action));

        return $prefix . '_' . $action;
    }

    public static function can(string $code, string $action): bool
    {
        $action = strtolower(trim($action));

        if ($action === 'upload') {
            return self::canUpload($code);
        }

        $name = self::permissionName($code, $action);
        if ($name === null) {
            return false;
        }

        return user_can($name);
    }

    public static function canUpload(string $code): bool
    {
        $upload = self::permissionName($code, 'upload');
        if ($upload !== null && user_can($upload)) {
            return true;
        }

        foreach (['create', 'edit'] as $action) {
            $name = self::permissionName($code, $action);
            if ($name !== null && user_can($name)) {
                return true;
            }
        }

        return false;
    }

    public static function authorize(string $code, string $action): void
    {
        if (! self::can($code, $action)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public static function authorizeUpload(string $code): void
    {
        if (! self::canUpload($code)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public static function canAny(string $code, array $actions): bool
    {
        foreach ($actions as $action) {
            if (self::can($code, (string) $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a stored voucher_type / form type into a permission code.
     */
    public static function codeFromVoucherType(?string $voucherType): ?string
    {
        $type = strtoupper(trim((string) $voucherType));
        if ($type === '' || $type === 'PV') {
            // Employee salary payments create PV vouchers but use Payments permissions.
            return $type === 'PV' ? 'PAY' : null;
        }

        if (isset(self::PREFIX_BY_CODE[$type])) {
            return $type;
        }

        return null;
    }

    /**
     * Typed voucher groups that inherit legacy employees_voucher_* grants.
     *
     * @return list<string>
     */
    public static function legacyVoucherMigrationPrefixes(): array
    {
        return [
            'employees_penalty_voucher',
            'employees_incentive_voucher',
            'employees_advance_loan_voucher',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function legacyGrantMigrationMap(): array
    {
        $map = [];
        $prefixes = self::legacyVoucherMigrationPrefixes();
        $crud = ['view', 'create', 'edit', 'delete'];

        foreach ($crud as $action) {
            $targets = [];
            foreach ($prefixes as $prefix) {
                $targets[] = $prefix . '_' . $action;
            }
            if (in_array($action, ['create', 'edit'], true)) {
                foreach ($prefixes as $prefix) {
                    $targets[] = $prefix . '_upload';
                }
            }
            $map['employees_voucher_' . $action] = array_values(array_unique($targets));
        }

        $map['employees_payments_create'] = ['employees_payments_upload'];
        $map['employees_payments_edit'] = ['employees_payments_upload'];

        return $map;
    }
}
