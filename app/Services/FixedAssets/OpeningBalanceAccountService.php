<?php

namespace App\Services\FixedAssets;

use App\Models\Accounts;
use Illuminate\Support\Facades\Schema;

class OpeningBalanceAccountService
{
    public const EQUITY_HEAD_NAME = 'Equity';
    public const OPENING_BALANCE_EQUITY_NAME = 'Opening Balance Equity';

    public function ensureOpeningBalanceEquityAccount(): Accounts
    {
        $equityHead = $this->ensureGlobalHeadAccount(self::EQUITY_HEAD_NAME, 'Equity', '3000');

        return $this->ensureGlobalFixedAccount(
            name: self::OPENING_BALANCE_EQUITY_NAME,
            accountType: 'Equity',
            parentId: $equityHead->id,
            codePrefix: 'OBE'
        );
    }

    private function ensureGlobalHeadAccount(string $name, string $accountType, string $accountCode): Accounts
    {
        $existing = Accounts::query()
            ->withoutGlobalScope('company')
            ->where('name', $name)
            ->where('account_type', $accountType)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->orderByRaw('is_fixed DESC')
            ->orderBy('id')
            ->first();

        if ($existing) {
            $this->markAccountGlobalFixed($existing);

            return $existing;
        }

        $account = new Accounts();
        $account->company_id = null;
        $account->branch_id = null;
        $account->account_code = $accountCode;
        $account->name = $name;
        $account->account_type = $accountType;
        $account->parent_id = null;
        $account->status = 1;
        $account->opening_balance = 0;
        $account->is_locked = 1;
        $account->is_fixed = true;
        $account->notes = 'Auto-created for opening balance fixed assets.';
        $account->save();

        return $account;
    }

    private function ensureGlobalFixedAccount(
        string $name,
        string $accountType,
        int $parentId,
        string $codePrefix
    ): Accounts {
        $existing = Accounts::query()
            ->withoutGlobalScope('company')
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->where('account_type', $accountType)
            ->whereNull('deleted_at')
            ->orderByRaw('is_fixed DESC')
            ->orderBy('id')
            ->first();

        if ($existing) {
            $this->markAccountGlobalFixed($existing);

            return $existing;
        }

        $account = new Accounts();
        $account->company_id = null;
        $account->branch_id = null;
        $account->name = $name;
        $account->account_type = $accountType;
        $account->parent_id = $parentId;
        $account->status = 1;
        $account->opening_balance = 0;
        $account->is_locked = 0;
        $account->is_fixed = true;
        $account->notes = 'Auto-created for opening balance fixed assets.';
        $account->save();
        $account->account_code = $codePrefix . str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
        $account->save();

        return $account;
    }

    private function markAccountGlobalFixed(Accounts $account): void
    {
        $updates = [];

        if ($account->company_id !== null) {
            $updates['company_id'] = null;
        }

        if (Schema::hasColumn($account->getTable(), 'is_fixed') && !$account->is_fixed) {
            $updates['is_fixed'] = true;
        }

        if (!empty($updates)) {
            $account->update($updates);
        }
    }
}
