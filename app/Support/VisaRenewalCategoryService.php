<?php

namespace App\Support;

use App\Support\GlobalAccounts;
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

    public static function visaExpenseHeadAccountId(): ?int
    {
        return GlobalAccounts::idOrNull('VISA_EXPENSE_ACCOUNT');
    }

    public static function findActive(int $categoryId): ?VisaRenewalCategory
    {
        return VisaRenewalCategory::query()
            ->where('id', $categoryId)
            ->where('is_active', true)
            ->first();
    }

    public static function accountForRiderCategory(int $riderId, int $categoryId): ?ExpenseAccount
    {
        $query = ExpenseAccount::query()->where('rider_id', $riderId);
        $defaultId = (int) self::defaultCategory()->id;

        if ((int) $categoryId === $defaultId) {
            return $query->where(function ($q) use ($categoryId) {
                $q->where('renewal_category_id', $categoryId)
                    ->orWhereNull('renewal_category_id');
            })->first();
        }

        return $query->where('renewal_category_id', $categoryId)->first();
    }

    /**
     * Base query for visa expenses belonging to an expense account (incl. legacy head-account rows).
     */
    public static function expensesForAccountQuery(int $expenseAccountId, int $riderId, ?int $renewalCategoryId = null): Builder
    {
        $query = visa_expenses::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($expenseAccountId, $riderId) {
                $q->where('expense_account_id', $expenseAccountId);
                $headId = self::visaExpenseHeadAccountId();
                if ($headId !== null) {
                    $q->orWhere(function ($q2) use ($riderId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->where('rider_id', $riderId);
                    });
                }
            });

        if ($renewalCategoryId !== null) {
            $query->where('renewal_category_id', $renewalCategoryId);
        }

        return $query;
    }

    public static function unpaidCountForAccount(ExpenseAccount $account): int
    {
        $categoryId = $account->renewal_category_id
            ? (int) $account->renewal_category_id
            : (int) self::defaultCategory()->id;

        return (int) self::expensesForAccountQuery((int) $account->id, (int) $account->rider_id, $categoryId)
            ->where('payment_status', 'unpaid')
            ->count();
    }

    public static function unpaidCountForCategory(int $expenseAccountId, int $riderId, int $categoryId): int
    {
        return (int) self::expensesForAccountQuery($expenseAccountId, $riderId, $categoryId)
            ->where('payment_status', 'unpaid')
            ->count();
    }

    /**
     * Next renewal category that may receive a new expense account for this rider, or null if blocked / complete.
     */
    public static function nextCreatableCategoryForRider(int $riderId): ?VisaRenewalCategory
    {
        foreach (self::activeOrdered() as $category) {
            $account = self::accountForRiderCategory($riderId, (int) $category->id);
            if (!$account) {
                return $category;
            }
            if (self::unpaidCountForAccount($account) > 0) {
                return null;
            }
        }

        return null;
    }

    /**
     * Categories the user may select when creating a new expense account for this rider (at most one).
     */
    public static function creatableCategoriesForRider(int $riderId): Collection
    {
        $next = self::nextCreatableCategoryForRider($riderId);

        return $next ? collect([$next]) : collect();
    }

    public static function canCreateAccountForCategory(int $riderId, int $categoryId): bool
    {
        if (!self::findActive($categoryId)) {
            return false;
        }

        if (self::accountForRiderCategory($riderId, $categoryId)) {
            return false;
        }

        $next = self::nextCreatableCategoryForRider($riderId);

        return $next && (int) $next->id === (int) $categoryId;
    }

    public static function resolveCategoryForAccount(ExpenseAccount $account): VisaRenewalCategory
    {
        if ($account->renewal_category_id) {
            return self::findActive((int) $account->renewal_category_id) ?? self::defaultCategory();
        }

        return self::defaultCategory();
    }

    /**
     * Other expense accounts for the same rider (for cross-account navigation).
     */
    public static function siblingAccountsForRider(int $riderId, ?int $excludeAccountId = null): Collection
    {
        $query = ExpenseAccount::query()
            ->with('renewalCategory')
            ->where('rider_id', $riderId)
            ->orderBy('id');

        if ($excludeAccountId) {
            $query->where('id', '!=', $excludeAccountId);
        }

        return $query->get();
    }

    public static function generatentriesUrl(int $expenseAccountId, ?int $riderId = null): string
    {
        return route('VisaExpense.generatentries', ['id' => $expenseAccountId]);
    }
}
