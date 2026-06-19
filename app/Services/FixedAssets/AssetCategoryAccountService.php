<?php

namespace App\Services\FixedAssets;

use App\Models\Accounts;
use App\Models\AssetCategory;
use App\Support\CompanyContext;

class AssetCategoryAccountService
{
    public const SUB_HEAD_FIXED_ASSETS = 'Fixed Assets';
    public const SUB_HEAD_ACCUMULATED_DEPRECIATION = 'Accumulated Depreciation';
    public const SUB_HEAD_DEPRECIATION_EXPENSE = 'Depreciation Expense';

    public function createAccountsForCategory(AssetCategory $category): void
    {
        $nonCurrentAssets = $this->findHeadAccount('Non-Current Assets', 'Asset');
        $operatingExpenses = $this->findHeadAccount('Operating Expenses', 'Expense');

        if (!$nonCurrentAssets || !$operatingExpenses) {
            throw new \RuntimeException(
                'Required parent accounts "Non-Current Assets" and/or "Operating Expenses" were not found. Please run migrations or create them in Chart of Accounts.'
            );
        }

        $fixedAssetsHead = $this->findOrCreateSubHead(
            $nonCurrentAssets,
            self::SUB_HEAD_FIXED_ASSETS,
            'Asset',
            'FAH'
        );

        $accumulatedDepreciationHead = $this->findOrCreateSubHead(
            $nonCurrentAssets,
            self::SUB_HEAD_ACCUMULATED_DEPRECIATION,
            'Asset',
            'ADH'
        );

        $depreciationExpenseHead = $this->findOrCreateSubHead(
            $operatingExpenses,
            self::SUB_HEAD_DEPRECIATION_EXPENSE,
            'Expense',
            'DEH'
        );

        $assetAccount = $this->createAccount(
            name: $category->name,
            accountType: 'Asset',
            parentId: $fixedAssetsHead->id,
            refName: 'AssetCategory',
            refId: $category->id,
            codePrefix: 'FAC'
        );

        $accumulatedAccount = $this->createAccount(
            name: 'Accumulated Depreciation - ' . $category->name,
            accountType: 'Asset',
            parentId: $accumulatedDepreciationHead->id,
            refName: 'AssetCategoryAccumDep',
            refId: $category->id,
            codePrefix: 'FAD'
        );

        $expenseAccount = $this->createAccount(
            name: 'Depreciation Expense - ' . $category->name,
            accountType: 'Expense',
            parentId: $depreciationExpenseHead->id,
            refName: 'AssetCategoryDepExpense',
            refId: $category->id,
            codePrefix: 'FDE'
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

    public function ensureSubHeadAccounts(): void
    {
        $nonCurrentAssets = $this->findHeadAccount('Non-Current Assets', 'Asset');
        $operatingExpenses = $this->findHeadAccount('Operating Expenses', 'Expense');

        if (!$nonCurrentAssets || !$operatingExpenses) {
            return;
        }

        $this->findOrCreateSubHead($nonCurrentAssets, self::SUB_HEAD_FIXED_ASSETS, 'Asset', 'FAH');
        $this->findOrCreateSubHead($nonCurrentAssets, self::SUB_HEAD_ACCUMULATED_DEPRECIATION, 'Asset', 'ADH');
        $this->findOrCreateSubHead($operatingExpenses, self::SUB_HEAD_DEPRECIATION_EXPENSE, 'Expense', 'DEH');
    }

    private function findHeadAccount(string $name, string $accountType): ?Accounts
    {
        return Accounts::query()
            ->where('name', $name)
            ->where('account_type', $accountType)
            ->whereNull('parent_id')
            ->first()
            ?? Accounts::query()
                ->where('name', $name)
                ->where('account_type', $accountType)
                ->orderBy('id')
                ->first();
    }

    private function findOrCreateSubHead(
        Accounts $parent,
        string $name,
        string $accountType,
        string $codePrefix
    ): Accounts {
        $query = Accounts::query()
            ->where('parent_id', $parent->id)
            ->where('name', $name)
            ->where('account_type', $accountType);

        $companyId = CompanyContext::id();
        if ($companyId) {
            $existing = (clone $query)
                ->where(function ($builder) use ($companyId) {
                    $builder->where('company_id', $companyId)->orWhereNull('company_id');
                })
                ->orderByRaw('company_id IS NULL ASC')
                ->first();
        } else {
            $existing = $query->first();
        }

        if ($existing) {
            return $existing;
        }

        return $this->createAccount(
            name: $name,
            accountType: $accountType,
            parentId: $parent->id,
            refName: null,
            refId: null,
            codePrefix: $codePrefix
        );
    }

    private function createAccount(
        string $name,
        string $accountType,
        int $parentId,
        ?string $refName,
        ?int $refId,
        string $codePrefix
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

        if (CompanyContext::id()) {
            $account->company_id = CompanyContext::id();
        }

        $account->save();
        $account->account_code = $codePrefix . str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
        $account->save();

        return $account;
    }
}
