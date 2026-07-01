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
            'VAT_ON_SALES' => ['label' => 'VAT on Sales', 'account_id' => null, 'account_type' => 'Liability'],
            'RTA_FINE' => ['label' => 'RTA Fine', 'account_id' => null, 'account_type' => 'Expense'],
            'SIM_EXPENSE_ACCOUNT' => ['label' => 'SIM Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'VAT_PURCHASE_ACCOUNT' => ['label' => 'VAT on Purchase', 'account_id' => null, 'account_type' => 'Liability'],
            'FUEL_ADMIN_CHARGES' => ['label' => 'Fuel Admin Charges', 'account_id' => 2501, 'account_type' => 'Expense'],
            'SALIK_ASSET_ACCOUNT' => ['label' => 'Salik Asset', 'account_id' => null, 'account_type' => 'Asset'],
            'SALIK_ADMIN_CHARGES' => ['label' => 'Salik Admin Charges', 'account_id' => null, 'account_type' => 'Expense'],
            'SALIK_PAYABLE_ACCOUNT' => ['label' => 'Salik Payable', 'account_id' => null, 'account_type' => 'Liability'],
            'RTA_ADMIN_CHARGES' => ['label' => 'RTA Admin Charges', 'account_id' => null, 'account_type' => 'Expense'],
            'RTA_SERVICE_CHARGES' => ['label' => 'RTA Service Charges', 'account_id' => null, 'account_type' => 'Expense'],
            'LEASING_EXPENSE_ACCOUNT' => ['label' => 'Leasing Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'VEHICAL_INCOME' => ['label' => 'Vehicle Income', 'account_id' => null, 'account_type' => 'Revenue'],
            'BIKE_MAINTENANCE_ACCOUNT' => ['label' => 'Bike Maintenance', 'account_id' => null, 'account_type' => 'Expense'],
            'GARAGE_ACCOUNT' => ['label' => 'Garage Items', 'account_id' => null, 'account_type' => 'Asset'],
            'SALARY_ACCOUNT' => ['label' => 'Salary Account', 'account_id' => null, 'account_type' => 'Expense'],
            'STAFF_ACCOUNT' => ['label' => 'Staff Account', 'account_id' => null, 'account_type' => 'Expense'],
            'SALES_ACCOUNT' => ['label' => 'Sales Account', 'account_id' => null, 'account_type' => 'Revenue'],
            'OTHER_EXPENSES' => ['label' => 'Other Expenses', 'account_id' => null, 'account_type' => 'Expense'],
            'VISA_EXPENSE_ACCOUNT' => ['label' => 'Visa Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'BIKE_REGISTRATION_EXPENSE_ACCOUNT' => ['label' => 'Bike Registration Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'INVENTORY_LOSS' => ['label' => 'Inventory Loss', 'account_id' => null, 'account_type' => 'Expense'],
            'LICENSE_EXPENSE_ACCOUNT' => ['label' => 'Dubai Driving License Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'LOANS_PAYABLE_PARENT_NAME' => ['label' => 'Loans Payable (Parent)', 'account_id' => null, 'account_type' => 'Liability'],
            'LOAN_INTEREST_EXPENSE' => ['label' => 'Loan Interest Expense', 'account_id' => null, 'account_type' => 'Expense'],
            'BANK' => ['label' => 'Bank (Parent)', 'account_id' => null, 'account_type' => 'Asset'],
            'CASH' => ['label' => 'Cash (Parent)', 'account_id' => null, 'account_type' => 'Asset'],
            'RIDERS' => ['label' => 'Riders (Parent)', 'account_id' => null, 'account_type' => 'Liability'],
            'VENDOR_CHARGES_ACCOUNT' => ['label' => 'Vendor Charges', 'account_id' => null, 'account_type' => 'Expense'],
            'INCENTIVE_ACCOUNT' => ['label' => 'Incentive', 'account_id' => null, 'account_type' => 'Expense'],
            'PENALTY_ACCOUNT' => ['label' => 'Penalty', 'account_id' => null, 'account_type' => 'Expense'],
            'TAX_ACCOUNT' => ['label' => 'Tax Account', 'account_id' => null, 'account_type' => 'Liability'],
            'ADVANCE_LOAN' => ['label' => 'Advance Loan', 'account_id' => null, 'account_type' => 'Asset'],
            'SALARIES_PAYABLE' => ['label' => 'Salaries Payable', 'account_id' => null, 'account_type' => 'Liability'],
            'COD_ACCOUNT' => ['label' => 'COD Account', 'account_id' => null, 'account_type' => 'Liability'],
            'GARAGE_PARENT' => ['label' => 'Garage (Parent)', 'account_id' => null, 'account_type' => 'Liability'],
            'BANK_CHARGES' => ['label' => 'Bank Charges', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'LEGAL_CASE_ACCOUNT' => ['label' => 'Legal Case Expense', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'GARAGE_INCOME_ACCOUNT' => ['label' => 'Garage Income', 'account_id' => null, 'account_type' => 'Revenue', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
            'PAYMENT_ACCOUNT' => ['label' => 'Payment Account', 'account_id' => null, 'account_type' => 'Expense', 'is_active' => false, 'description' => 'Configure via Global Accounts admin'],
        ];
    }

    public function run(): void
    {
        // #region agent log
        $debugLog = static function (string $hypothesisId, string $message, array $data = []): void {
            $payload = json_encode([
                'sessionId' => '921d95',
                'hypothesisId' => $hypothesisId,
                'location' => 'GlobalAccountsSeeder::run',
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES);
            @file_put_contents(base_path('debug-921d95.log'), $payload.PHP_EOL, FILE_APPEND);
        };
        // #endregion

        if (! Schema::hasTable('global_accounts')) {
            // #region agent log
            $debugLog('H2', 'Seeder exiting early — global_accounts table missing', [
                'environment' => app()->environment(),
            ]);
            // #endregion

            return;
        }

        // #region agent log
        $debugLog('H1', 'Seeder run started', [
            'environment' => app()->environment(),
            'existing_rows' => GlobalAccount::query()->count(),
            'definition_count' => count(self::definitions()),
        ]);
        // #endregion

        $now = now();
        $created = 0;

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
            $created++;
        }

        // #region agent log
        $debugLog('H3', 'Seeder run finished', [
            'upserted' => $created,
            'total_rows' => GlobalAccount::query()->count(),
            'active_rows' => GlobalAccount::query()->where('is_active', true)->count(),
        ]);
        // #endregion

        app(GlobalAccountResolver::class)->flushCache();
    }
}
