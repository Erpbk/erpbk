<?php

namespace App\Services\FixedAssets;

use App\Models\Accounts;
use App\Models\AssetCategory;
use App\Support\CompanyContext;

class AssetCategoryAccountService
{

    public function createAccountsForCategory(AssetCategory $category): void
    {

        $fixedAssetsHead = \App\Models\GlobalAccount::id('FIXED_ASSETS');

        $accumulatedDepreciationHead = \App\Models\GlobalAccount::id('ACCUMULATED_DEPRECIATION');

        $depreciationExpenseHead = \App\Models\GlobalAccount::id('DEPRECIATION_EXPENSE');

        $assetAccount = $this->createAccount(
            name: $category->name,
            accountType: 'Asset',
            parentId: $fixedAssetsHead,
            refName: 'AssetCategory',
            refId: $category->id,
            codePrefix: 'FAC',
            companyId: $category->company_id
        );

        $accumulatedAccount = $this->createAccount(
            name: 'Accumulated Depreciation - ' . $category->name,
            accountType: 'Asset',
            parentId: $accumulatedDepreciationHead,
            refName: 'AssetCategoryAccumDep',
            refId: $category->id,
            codePrefix: 'FAD',
            companyId: $category->company_id
        );

        $expenseAccount = $this->createAccount(
            name: 'Depreciation Expense - ' . $category->name,
            accountType: 'Expense',
            parentId: $depreciationExpenseHead,
            refName: 'AssetCategoryDepExpense',
            refId: $category->id,
            codePrefix: 'FDE',
            companyId: $category->company_id
        );

        $category->asset_account_id = $assetAccount->id;
        $category->accumulated_depreciation_account_id = $accumulatedAccount->id;
        $category->depreciation_expense_account_id = $expenseAccount->id;
        $category->save();
    }

    public function updateAccountNames(AssetCategory $category, string $oldName): void
    {
        if ($category->name === $oldName) {
            return;
        }

        $updates = [
            $category->asset_account_id => $category->name,
            $category->accumulated_depreciation_account_id => 'Accumulated Depreciation - ' . $category->name,
            $category->depreciation_expense_account_id => 'Depreciation Expense - ' . $category->name,
        ];

        foreach ($updates as $accountId => $name) {
            if (!$accountId) {
                continue;
            }

            Accounts::where('id', $accountId)->update(['name' => $name]);
        }
    }

    public function deleteAccountsForCategory(AssetCategory $category): void
    {
        foreach ($this->categoryAccountIds($category) as $accountId) {
            $account = Accounts::find($accountId);

            if (!$account) {
                continue;
            }

            if (Accounts::where('parent_id', $account->id)->exists()) {
                throw new \RuntimeException(
                    'Cannot delete account "' . $account->name . '" because it has sub-accounts.'
                );
            }

            $account->delete();
        }
    }

    /**
     * @return list<int>
     */
    private function categoryAccountIds(AssetCategory $category): array
    {
        return array_values(array_filter([
            $category->asset_account_id,
            $category->accumulated_depreciation_account_id,
            $category->depreciation_expense_account_id,
        ]));
    }

    private function createAccount(
        string $name,
        string $accountType,
        int $parentId,
        ?string $refName,
        ?int $refId,
        string $codePrefix,
        ?int $companyId = null
    ): Accounts {
        $account = new Accounts();
        $account->name = $name;
        $account->account_type = $accountType;
        $account->parent_id = $parentId;
        $account->ref_name = $refName;
        $account->ref_id = $refId;
        $account->status = 1;
        $account->opening_balance = 0;
        $account->is_locked = 0;

        if ($companyId ?? CompanyContext::id()) {
            $account->company_id = $companyId ?? CompanyContext::id();
        }

        $account->save();
        $account->account_code = $codePrefix . str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
        $account->save();

        return $account;
    }
}
