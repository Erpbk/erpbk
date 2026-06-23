<?php

namespace App\Support;

use App\Helpers\HeadAccount;
use App\Models\ExpenseAccount;
use App\Models\VisaRenewalCategory;
use App\Models\visa_expenses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VisaRenewalCategoryService
{
    public static function activeOrdered(): Collection
    {
        return VisaRenewalCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public static function allOrdered(): Collection
    {
        return VisaRenewalCategory::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public static function ensureDefaultExists(): VisaRenewalCategory
    {
        $default = VisaRenewalCategory::query()->where('is_default', true)->first();
        if ($default) {
            return $default;
        }

        $first = VisaRenewalCategory::query()->orderBy('display_order')->orderBy('id')->first();
        if ($first) {
            if (!$first->is_default) {
                $first->is_default = true;
                $first->save();
            }

            return $first;
        }

        return VisaRenewalCategory::create([
            'name' => 'New Visa',
            'display_order' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public static function defaultCategory(): VisaRenewalCategory
    {
        return self::ensureDefaultExists();
    }

    public static function findActive(int $categoryId): ?VisaRenewalCategory
    {
        return VisaRenewalCategory::query()
            ->where('id', $categoryId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Base query for visa expenses belonging to an expense account (incl. legacy head-account rows).
     */
    public static function expensesForAccountQuery(int $expenseAccountId, int $riderId): Builder
    {
        return visa_expenses::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($expenseAccountId, $riderId) {
                $q->where('expense_account_id', $expenseAccountId)
                    ->orWhere(function ($q2) use ($riderId) {
                        $q2->where('expense_account_id', HeadAccount::VISA_EXPENSE_ACCOUNT)
                            ->where('rider_id', $riderId);
                    });
            });
    }

    public static function unpaidCountForCategory(int $expenseAccountId, int $riderId, int $categoryId): int
    {
        return (int) self::expensesForAccountQuery($expenseAccountId, $riderId)
            ->where('renewal_category_id', $categoryId)
            ->where('payment_status', 'unpaid')
            ->count();
    }

    /**
     * Earliest active category that still has unpaid expenses, or default when all are paid.
     */
    public static function resolveTargetCategoryId(int $expenseAccountId, int $riderId): int
    {
        foreach (self::activeOrdered() as $category) {
            if (self::unpaidCountForCategory($expenseAccountId, $riderId, (int) $category->id) > 0) {
                return (int) $category->id;
            }
        }

        return (int) self::defaultCategory()->id;
    }

    public static function resolveTargetCategory(ExpenseAccount $account): VisaRenewalCategory
    {
        $categoryId = self::resolveTargetCategoryId((int) $account->id, (int) $account->rider_id);

        return self::findActive($categoryId) ?? self::defaultCategory();
    }

    /**
     * Whether new expenses may be added to this renewal category for the account.
     */
    public static function canAddExpenseToCategory(int $expenseAccountId, int $riderId, int $categoryId): bool
    {
        $target = self::findActive($categoryId);
        if (!$target) {
            return false;
        }

        foreach (self::activeOrdered() as $category) {
            if ((int) $category->id === (int) $target->id) {
                return true;
            }
            if (self::unpaidCountForCategory($expenseAccountId, $riderId, (int) $category->id) > 0) {
                return false;
            }
        }

        return false;
    }

    public static function generatentriesUrl(int $expenseAccountId, ?int $riderId = null): string
    {
        $account = ExpenseAccount::find($expenseAccountId);
        if (!$account) {
            return route('VisaExpense.generatentries', $expenseAccountId);
        }

        $categoryId = self::resolveTargetCategoryId(
            (int) $account->id,
            (int) ($riderId ?? $account->rider_id)
        );

        return route('VisaExpense.generatentries', [
            'id' => $expenseAccountId,
            'renewal_category' => $categoryId,
        ]);
    }
}
