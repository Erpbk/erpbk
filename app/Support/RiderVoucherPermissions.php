<?php

namespace App\Support;

/**
 * Maps Rider module voucher codes / import flows to Spatie permission prefixes.
 */
class RiderVoucherPermissions
{
    public const IMPORT_CODE = 'IMPORT';

    /** Voucher types that use typed permission groups under Riders. */
    public const TYPED_CODES = ['COD', 'PN', 'INC', 'VC', 'AL', 'PAY', self::IMPORT_CODE];

    /**
     * Permission prefix per voucher / import code (without trailing action).
     *
     * @var array<string, string>
     */
    public const PREFIX_BY_CODE = [
        'PAY' => 'riders_payments',
        'COD' => 'riders_cod_voucher',
        'PN' => 'riders_penalty_voucher',
        'INC' => 'riders_incentive_voucher',
        'VC' => 'riders_vendor_charges_voucher',
        'AL' => 'riders_advance_loan_voucher',
        self::IMPORT_CODE => 'riders_import_vouchers',
    ];

    /**
     * Typed voucher groups that inherit legacy riders_voucher_* grants (excludes PAY / Import).
     *
     * @return list<string>
     */
    public static function legacyVoucherMigrationPrefixes(): array
    {
        return [
            'riders_cod_voucher',
            'riders_penalty_voucher',
            'riders_incentive_voucher',
            'riders_vendor_charges_voucher',
            'riders_advance_loan_voucher',
        ];
    }

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

        // Document upload: explicit Upload, or Create/Edit (add/update voucher with attachment).
        if ($action === 'upload') {
            return self::canUpload($code);
        }

        $name = self::permissionName($code, $action);
        if ($name === null) {
            return false;
        }

        return user_can($name);
    }

    /**
     * Whether the user may attach documents for this voucher type.
     * True when they have Upload, or Create / Edit (so "add COD" includes form upload).
     */
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

    /**
     * Abort with 403 when the current user lacks the given voucher action.
     */
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
            // Rider invoice payments create PV vouchers but use Payments permissions.
            return $type === 'PV' ? 'PAY' : null;
        }

        if (isset(self::PREFIX_BY_CODE[$type])) {
            return $type;
        }

        return null;
    }

    /**
     * Capture map: old riders_voucher_* → new typed leaves (+ upload when create/edit).
     *
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
            $map['riders_voucher_' . $action] = array_values(array_unique($targets));
        }

        // Import inherits create/edit from the old shared voucher create/edit.
        $map['riders_voucher_create'] = array_values(array_unique(array_merge(
            $map['riders_voucher_create'],
            [
                'riders_import_vouchers_view',
                'riders_import_vouchers_create',
                'riders_import_vouchers_upload',
            ]
        )));
        $map['riders_voucher_edit'] = array_values(array_unique(array_merge(
            $map['riders_voucher_edit'],
            [
                'riders_import_vouchers_edit',
                'riders_import_vouchers_upload',
            ]
        )));
        $map['riders_voucher_view'] = array_values(array_unique(array_merge(
            $map['riders_voucher_view'],
            ['riders_import_vouchers_view']
        )));
        $map['riders_voucher_delete'] = array_values(array_unique(array_merge(
            $map['riders_voucher_delete'],
            ['riders_import_vouchers_delete']
        )));

        // Payments leaves are kept by sync; only grant the new upload leaf.
        $map['riders_payments_create'] = ['riders_payments_upload'];
        $map['riders_payments_edit'] = ['riders_payments_upload'];

        return $map;
    }
}
