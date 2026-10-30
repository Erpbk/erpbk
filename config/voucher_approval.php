<?php

/**
 * Central voucher approval registry.
 *
 * New voucher types: register here only (permission prefix + module metadata).
 * Permission leaves are synced via tenant_module_permissions + TenantModulePermissionsSync.
 */

return [

    'enabled' => env('VOUCHER_APPROVAL_ENABLED', true),

    /**
     * When true, soft-holds ledger lines until approve (legacy).
     * Default false: vouchers post immediately; approval is supervision-only.
     */
    'defer_posting' => env('VOUCHER_APPROVAL_DEFER_POSTING', false),

    /** Creator cannot approve/reject their own voucher (admins/override still can). */
    'prevent_self_approval' => env('VOUCHER_APPROVAL_PREVENT_SELF', true),

    /** Super Admin / Administrator bypass typed approve leaves (override still logged). */
    'admin_bypass' => env('VOUCHER_APPROVAL_ADMIN_BYPASS', true),

    'statuses' => [
        'pending_approval' => 'pending_approval',
        'approved' => 'approved',
        'rejected' => 'rejected',
    ],

    /**
     * Global override / dashboard permission names (under Settings / Voucher Approval).
     */
    'permissions' => [
        'dashboard_view' => 'settings_voucher_approvals_view',
        'dashboard_edit' => 'settings_voucher_approvals_edit',
        'force_approve' => 'voucher_approval_force_approve',
        'reopen' => 'voucher_approval_reopen',
        'revise' => 'voucher_approval_revise',
    ],

    /**
     * @var array<string, array{
     *   label: string,
     *   module: string,
     *   approve: string,
     *   reject: string,
     *   posting: string
     * }>
     */
    'types' => [
        'JV' => [
            'label' => 'Journal',
            'module' => 'vouchers',
            'approve' => 'voucher_approval_jv_approve',
            'reject' => 'voucher_approval_jv_reject',
            'posting' => 'default',
        ],
        'TRF' => [
            'label' => 'Funds Transfer',
            'module' => 'vouchers',
            'approve' => 'voucher_approval_trf_approve',
            'reject' => 'voucher_approval_trf_reject',
            'posting' => 'default',
        ],
        'PV' => [
            'label' => 'Payment',
            'module' => 'cash_banks',
            'approve' => 'voucher_approval_pv_approve',
            'reject' => 'voucher_approval_pv_reject',
            'posting' => 'default',
        ],
        'RV' => [
            'label' => 'Receipt',
            'module' => 'cash_banks',
            'approve' => 'voucher_approval_rv_approve',
            'reject' => 'voucher_approval_rv_reject',
            'posting' => 'default',
        ],
        'AL' => [
            'label' => 'Advance Loan',
            'module' => 'riders',
            'approve' => 'riders_advance_loan_voucher_approve',
            'reject' => 'riders_advance_loan_voucher_reject',
            'posting' => 'default',
        ],
        'COD' => [
            'label' => 'COD',
            'module' => 'riders',
            'approve' => 'riders_cod_voucher_approve',
            'reject' => 'riders_cod_voucher_reject',
            'posting' => 'default',
        ],
        'PN' => [
            'label' => 'Penalty',
            'module' => 'riders',
            'approve' => 'riders_penalty_voucher_approve',
            'reject' => 'riders_penalty_voucher_reject',
            'posting' => 'default',
        ],
        'INC' => [
            'label' => 'Incentive',
            'module' => 'riders',
            'approve' => 'riders_incentive_voucher_approve',
            'reject' => 'riders_incentive_voucher_reject',
            'posting' => 'default',
        ],
        'PAY' => [
            'label' => 'Salary Payment',
            'module' => 'riders',
            'approve' => 'riders_payments_approve',
            'reject' => 'riders_payments_reject',
            'posting' => 'default',
        ],
        'VC' => [
            'label' => 'Vendor Charges',
            'module' => 'riders',
            'approve' => 'riders_vendor_charges_voucher_approve',
            'reject' => 'riders_vendor_charges_voucher_reject',
            'posting' => 'default',
        ],
        'EXP' => [
            'label' => 'Expense',
            'module' => 'expenses',
            'approve' => 'voucher_approval_exp_approve',
            'reject' => 'voucher_approval_exp_reject',
            'posting' => 'default',
        ],
        'LV' => [
            'label' => 'Visa Expense',
            'module' => 'visa_expense',
            'approve' => 'voucher_approval_lv_approve',
            'reject' => 'voucher_approval_lv_reject',
            'posting' => 'default',
        ],
        'VL' => [
            'label' => 'Visa Loan',
            'module' => 'visa_expense',
            'approve' => 'voucher_approval_vl_approve',
            'reject' => 'voucher_approval_vl_reject',
            'posting' => 'default',
        ],
        'LE' => [
            'label' => 'License Expense',
            'module' => 'license_expense',
            'approve' => 'voucher_approval_le_approve',
            'reject' => 'voucher_approval_le_reject',
            'posting' => 'default',
        ],
        'VR' => [
            'label' => 'Vehicle Registration',
            'module' => 'bikes',
            'approve' => 'voucher_approval_vr_approve',
            'reject' => 'voucher_approval_vr_reject',
            'posting' => 'default',
        ],
        'RFV' => [
            'label' => 'RTA Fine',
            'module' => 'rta_fines',
            'approve' => 'voucher_approval_rfv_approve',
            'reject' => 'voucher_approval_rfv_reject',
            'posting' => 'default',
        ],
        'SV' => [
            'label' => 'Salik',
            'module' => 'rta_saliks',
            'approve' => 'voucher_approval_sv_approve',
            'reject' => 'voucher_approval_sv_reject',
            'posting' => 'default',
        ],
        'GV' => [
            'label' => 'Garage',
            'module' => 'garage_items',
            'approve' => 'voucher_approval_gv_approve',
            'reject' => 'voucher_approval_gv_reject',
            'posting' => 'default',
        ],
        'RI' => [
            'label' => 'Rider Invoice',
            'module' => 'riders',
            'approve' => 'voucher_approval_ri_approve',
            'reject' => 'voucher_approval_ri_reject',
            'posting' => 'default',
        ],
        'VP' => [
            'label' => 'VAT Payment',
            'module' => 'vat',
            'approve' => 'voucher_approval_vp_approve',
            'reject' => 'voucher_approval_vp_reject',
            'posting' => 'default',
        ],
        'VV' => [
            'label' => 'VAT Return',
            'module' => 'vat',
            'approve' => 'voucher_approval_vv_approve',
            'reject' => 'voucher_approval_vv_reject',
            'posting' => 'default',
        ],
        'BL' => [
            'label' => 'Bank Loan',
            'module' => 'loans',
            'approve' => 'voucher_approval_bl_approve',
            'reject' => 'voucher_approval_bl_reject',
            'posting' => 'default',
        ],
        'FAV' => [
            'label' => 'Fixed Asset Acquisition',
            'module' => 'assets',
            'approve' => 'voucher_approval_fav_approve',
            'reject' => 'voucher_approval_fav_reject',
            'posting' => 'default',
        ],
        'FDV' => [
            'label' => 'Fixed Asset Depreciation',
            'module' => 'assets',
            'approve' => 'voucher_approval_fdv_approve',
            'reject' => 'voucher_approval_fdv_reject',
            'posting' => 'default',
        ],
        'IL' => [
            'label' => 'Inventory Loss',
            'module' => 'items',
            'approve' => 'voucher_approval_il_approve',
            'reject' => 'voucher_approval_il_reject',
            'posting' => 'default',
        ],
    ],
];
