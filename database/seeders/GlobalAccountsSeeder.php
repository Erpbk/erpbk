<?php

namespace Database\Seeders;

use App\Models\Accounts;
use App\Models\GlobalAccount;
use App\Services\GlobalAccountResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GlobalAccountsSeeder extends Seeder
{
    /**
     * Bootstrap global account code → account_id mappings.
     *
     * @return array<string, array{label: string, account_id: int|null, account_type: string|null, is_active: bool, description?: string}>
     */
    public static function definitions(): array
    {
        return [
            'VAT_ON_SALES' => ['label' => 'VAT on Sales', 'account_id' => 1025, 'account_type' => 'Liability'],
            'RTA_FINE' => ['label' => 'RTA Fine', 'account_id' => 2497, 'account_type' => 'Expense'],
            'SIM_EXPENSE_ACCOUNT' => ['label' => 'SIM Expense', 'account_id' => 2394, 'account_type' => 'Expense'],
            'VAT_PURCHASE_ACCOUNT' => ['label' => 'VAT on Purchase', 'account_id' => 2487, 'account_type' => 'Liability'],
            'FUEL_ADMIN_CHARGES' => ['label' => 'Fuel Admin Charges', 'account_id' => 2501, 'account_type' => 'Expense'],
            'SALIK_ASSET_ACCOUNT' => ['label' => 'Salik Asset', 'account_id' => 2490, 'account_type' => 'Asset'],
            'SALIK_ADMIN_CHARGES' => ['label' => 'Salik Admin Charges', 'account_id' => 2476, 'account_type' => 'Expense'],
            'SALIK_PAYABLE_ACCOUNT' => ['label' => 'Salik Payable', 'account_id' => 2494, 'account_type' => 'Liability'],
            'RTA_ADMIN_CHARGES' => ['label' => 'RTA Admin Charges', 'account_id' => 2473, 'account_type' => 'Expense'],
            'RTA_SERVICE_CHARGES' => ['label' => 'RTA Service Charges', 'account_id' => 2513, 'account_type' => 'Expense'],
            'LEASING_EXPENSE_ACCOUNT' => ['label' => 'Leasing Expense', 'account_id' => 2493, 'account_type' => 'Expense'],
            'VEHICAL_INCOME' => ['label' => 'Vehicle Income', 'account_id' => 2502, 'account_type' => 'Revenue'],
            'BIKE_MAINTENANCE_ACCOUNT' => ['label' => 'Bike Maintenance', 'account_id' => 2468, 'account_type' => 'Expense'],
            'GARAGE_ACCOUNT' => ['label' => 'Garage Items', 'account_id' => 2491, 'account_type' => 'Asset'],
            'SALARY_ACCOUNT' => ['label' => 'Salary Account', 'account_id' => 2492, 'account_type' => 'Expense'],
            'STAFF_ACCOUNT' => ['label' => 'Staff Account', 'account_id' => 2464, 'account_type' => 'Expense'],
            'SALES_ACCOUNT' => ['label' => 'Sales Account', 'account_id' => 2514, 'account_type' => 'Revenue'],
            'OTHER_EXPENSES' => ['label' => 'Other Expenses', 'account_id' => 2388, 'account_type' => 'Expense'],
            'VISA_EXPENSE_ACCOUNT' => ['label' => 'Visa Expense', 'account_id' => 2515, 'account_type' => 'Expense'],
            'BIKE_REGISTRATION_EXPENSE_ACCOUNT' => ['label' => 'Bike Registration Expense', 'account_id' => 2516, 'account_type' => 'Expense'],
            'INVENTORY_LOSS' => ['label' => 'Inventory Loss', 'account_id' => 2519, 'account_type' => 'Expense'],
            'LICENSE_EXPENSE_ACCOUNT' => ['label' => 'Dubai Driving License Expense', 'account_id' => 2520, 'account_type' => 'Expense'],
            'LOANS_PAYABLE_PARENT_NAME' => ['label' => 'Loans Payable (Parent)', 'account_id' => 2539, 'account_type' => 'Liability'],
            'LOAN_INTEREST_EXPENSE' => ['label' => 'Loan Interest Expense', 'account_id' => 2538, 'account_type' => 'Expense'],
            'BANK' => ['label' => 'Bank (Parent)', 'account_id' => 994, 'account_type' => 'Asset'],
            'CASH' => ['label' => 'Cash (Parent)', 'account_id' => 1643, 'account_type' => 'Asset'],
            'RIDERS' => ['label' => 'Riders (Parent)', 'account_id' => 1, 'account_type' => 'Liability'],
            'VENDOR_CHARGES_ACCOUNT' => ['label' => 'Vendor Charges', 'account_id' => 1005, 'account_type' => 'Expense'],
            'INCENTIVE_ACCOUNT' => ['label' => 'Incentive', 'account_id' => 1009, 'account_type' => 'Expense'],
            'PENALTY_ACCOUNT' => ['label' => 'Penalty', 'account_id' => 1017, 'account_type' => 'Expense'],
            'TAX_ACCOUNT' => ['label' => 'Tax Account', 'account_id' => 1023, 'account_type' => 'Liability'],
            'ADVANCE_LOAN' => ['label' => 'Advance Loan', 'account_id' => 1135, 'account_type' => 'Asset'],
            'SALARIES_PAYABLE' => ['label' => 'Salaries Payable', 'account_id' => 1200, 'account_type' => 'Liability'],
            'COD_ACCOUNT' => ['label' => 'COD Account', 'account_id' => 1219, 'account_type' => 'Liability'],
            'GARAGE_PARENT' => ['label' => 'Garage (Parent)', 'account_id' => 1664, 'account_type' => 'Liability'],
            'BANK_CHARGES' => ['label' => 'Bank Charges', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'LEGAL_CASE_ACCOUNT' => ['label' => 'Legal Case Expense', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'GARAGE_INCOME_ACCOUNT' => ['label' => 'Garage Income', 'account_id' => null, 'account_type' => 'Revenue', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'PAYMENT_ACCOUNT' => ['label' => 'Payment Account', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
        ];
    }

    public function run(): void
    {
        if (! Schema::hasTable('global_accounts')) {
            return;
        }

        $now = now();

        foreach (self::definitions() as $code => $definition) {
            $accountId = $definition['account_id'] ?? null;
            $isActive = $definition['is_active'] ?? ($accountId !== null);

            if ($accountId !== null && DB::table('accounts')->where('id', $accountId)->exists()) {
                DB::table('accounts')
                    ->where('id', $accountId)
                    ->update([
                        'is_fixed' => true,
                        'company_id' => null,
                        'updated_at' => $now,
                    ]);
            } elseif ($accountId !== null) {
                $this->command?->warn("Skipping global account [{$code}]: account ID {$accountId} does not exist.");
                $accountId = null;
                $isActive = false;
            }

            GlobalAccount::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'] ?? null,
                    'account_id' => $accountId,
                    'account_type' => $definition['account_type'] ?? null,
                    'is_active' => $isActive,
                ]
            );
        }

        app(GlobalAccountResolver::class)->flushCache();
    }
}
