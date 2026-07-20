<?php

namespace App\Support;

/**
 * Permissions that may open VouchersController actions from outside the Vouchers module
 * (receipts, payments, ledger, salik, etc. all route to vouchers.show).
 */
class VoucherAccess
{
    /**
     * View a single voucher (and optional list sidebar companion) from any linked module.
     *
     * @return list<string>
     */
    public static function crossModuleViewPermissions(): array
    {
        return [
            'vouchers_view',
            'cash_&_banks_banks_view',
            'cash_&_banks_receipts_view',
            'cash_&_banks_payments_view',
            'cash_&_banks_cheques_view',
            'rta_saliks_salik_view',
            'rta_saliks_payment_view',
            'rta_fines_unpaid_view',
            'rta_fines_paid_view',
            'vat_view',
            'accounts_ledger_view',
            'accounts_coa_view',
            'bikes_registration_view',
            'license_expense_view',
            'visa_expense_view',
            'legal_case_view',
            'riders_inventory_view',
            'riders_report_view',
            'expenses_view',
            'assets_view',
            'loans_view',
        ];
    }

    public static function crossModuleViewMiddleware(): string
    {
        return 'permission:' . implode('|', self::crossModuleViewPermissions());
    }
}
