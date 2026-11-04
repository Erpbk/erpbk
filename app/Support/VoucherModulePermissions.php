<?php

namespace App\Support;

/**
 * Upload / document attach gates for the flat Vouchers module (JV, TRF, etc.).
 */
class VoucherModulePermissions
{
    /**
     * Whether the user may attach documents on Vouchers-module forms / fileUpload
     * for non-rider voucher types.
     *
     * Explicit Upload, or Create / Edit (same pattern as RiderVoucherPermissions::canUpload).
     */
    public static function canUpload(): bool
    {
        if (user_can('vouchers_upload')) {
            return true;
        }

        foreach ([
            'vouchers_create',
            'vouchers_edit',
            'voucher_create',
            'voucher_edit',
            'expenses_create',
            'expenses_edit',
        ] as $permission) {
            if (user_can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Roles with create/edit inherit the new Upload leaf after sync.
     *
     * @return array<string, list<string>>
     */
    public static function uploadGrantMigrationMap(): array
    {
        return [
            'vouchers_create' => ['vouchers_upload'],
            'vouchers_edit' => ['vouchers_upload'],
            'voucher_create' => ['vouchers_upload'],
            'voucher_edit' => ['vouchers_upload'],
        ];
    }
}
