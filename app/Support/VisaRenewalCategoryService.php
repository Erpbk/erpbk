<?php

namespace App\Support;

use App\Support\GlobalAccounts;
use App\Models\ExpenseAccount;
use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
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

        $created = VisaRenewalCategory::create([
            'name' => 'New Visa',
            'display_order' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);
        self::seedStatusesForCategory($created);

        return $created;
    }

    public static function defaultCategory(): VisaRenewalCategory
    {
        return self::ensureDefaultExists();
    }

    /**
     * Standard visa status tickets used when a category has no source statuses to copy.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultStatusTemplates(): array
    {
        return [
            ['name' => 'Job offer Letter', 'code' => 'JOL', 'description' => 'Job offer letter for visa processing', 'default_fee' => 150.00, 'category' => 'Document', 'is_required' => true, 'display_order' => 1],
            ['name' => 'Labor Insurance', 'code' => 'LI', 'description' => 'Labor insurance for workers', 'default_fee' => 300.00, 'category' => 'Insurance', 'is_required' => true, 'display_order' => 2],
            ['name' => 'Work Permit', 'code' => 'WP', 'description' => 'Permit to work legally', 'default_fee' => 500.00, 'category' => 'Permit', 'is_required' => true, 'display_order' => 3],
            ['name' => 'Work Man Insurance', 'code' => 'WMI', 'description' => 'Insurance for workman compensation', 'default_fee' => 250.00, 'category' => 'Insurance', 'is_required' => true, 'display_order' => 4],
            ['name' => 'Entry Permit (Inside)', 'code' => 'EPI', 'description' => 'Entry permit for those already inside the country', 'default_fee' => 350.00, 'category' => 'Permit', 'is_required' => false, 'display_order' => 5],
            ['name' => 'Entry Permit (Outside)', 'code' => 'EPO', 'description' => 'Entry permit for those outside the country', 'default_fee' => 400.00, 'category' => 'Permit', 'is_required' => false, 'display_order' => 6],
            ['name' => 'Status Change', 'code' => 'SC', 'description' => 'Change of visa status', 'default_fee' => 200.00, 'category' => 'Other', 'is_required' => false, 'display_order' => 7],
            ['name' => 'Tawjeeh', 'code' => 'TW', 'description' => 'Tawjeeh service', 'default_fee' => 100.00, 'category' => 'Other', 'is_required' => false, 'display_order' => 8],
            ['name' => 'Medical', 'code' => 'MED', 'description' => 'Medical examination for visa', 'default_fee' => 320.00, 'category' => 'Other', 'is_required' => true, 'display_order' => 9],
            ['name' => 'Emirates ID + Residency', 'code' => 'EIDR', 'description' => 'Emirates ID and residency processing', 'default_fee' => 600.00, 'category' => 'Document', 'is_required' => true, 'display_order' => 10],
            ['name' => 'Emirates ID', 'code' => 'EID', 'description' => 'Emirates ID processing only', 'default_fee' => 300.00, 'category' => 'Document', 'is_required' => true, 'display_order' => 11],
            ['name' => 'Residency', 'code' => 'RES', 'description' => 'Residency processing only', 'default_fee' => 400.00, 'category' => 'Document', 'is_required' => true, 'display_order' => 12],
            ['name' => 'Bike License', 'code' => 'BL', 'description' => 'License for bike operation', 'default_fee' => 250.00, 'category' => 'License', 'is_required' => false, 'display_order' => 13],
            ['name' => 'Violation', 'code' => 'VIO', 'description' => 'Violation fees and penalties', 'default_fee' => 100.00, 'category' => 'Other', 'is_required' => false, 'display_order' => 14],
            ['name' => 'Bed Space', 'code' => 'BS', 'description' => 'Accommodation bed space fee', 'default_fee' => 200.00, 'category' => 'Other', 'is_required' => false, 'display_order' => 15],
        ];
    }

    /**
     * Copy visa statuses onto a category (from the default/previous category, or the standard template).
     *
     * @return array{count: int, source: string|null}
     */
    public static function seedStatusesForCategory(VisaRenewalCategory $category): array
    {
        $existing = VisaStatus::query()
            ->where('visa_renewal_category_id', $category->id)
            ->count();
        if ($existing > 0) {
            return ['count' => 0, 'source' => null];
        }

        $sourceCategory = self::statusCopySource($category);
        $createdBy = auth()->id();

        if ($sourceCategory) {
            $sourceStatuses = VisaStatus::query()
                ->where('visa_renewal_category_id', $sourceCategory->id)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            foreach ($sourceStatuses as $status) {
                VisaStatus::create([
                    'visa_renewal_category_id' => $category->id,
                    'name' => $status->name,
                    'code' => $status->code,
                    'description' => $status->description,
                    'default_fee' => $status->default_fee,
                    'category' => $status->category,
                    'is_active' => $status->is_active,
                    'is_required' => $status->is_required,
                    'display_order' => $status->display_order,
                    'created_by' => $createdBy,
                ]);
            }

            return ['count' => $sourceStatuses->count(), 'source' => $sourceCategory->name];
        }

        $count = 0;
        foreach (self::defaultStatusTemplates() as $template) {
            VisaStatus::create([
                'visa_renewal_category_id' => $category->id,
                'name' => $template['name'],
                'code' => $template['code'],
                'description' => $template['description'],
                'default_fee' => $template['default_fee'],
                'category' => $template['category'],
                'is_active' => true,
                'is_required' => $template['is_required'],
                'display_order' => $template['display_order'],
                'created_by' => $createdBy,
            ]);
            $count++;
        }

        return ['count' => $count, 'source' => null];
    }

    private static function statusCopySource(VisaRenewalCategory $category): ?VisaRenewalCategory
    {
        $default = VisaRenewalCategory::query()->where('is_default', true)->first();
        if ($default && (int) $default->id !== (int) $category->id) {
            $hasStatuses = VisaStatus::query()
                ->where('visa_renewal_category_id', $default->id)
                ->exists();
            if ($hasStatuses) {
                return $default;
            }
        }

        $previous = VisaRenewalCategory::query()
            ->where('id', '!=', $category->id)
            ->orderByDesc('display_order')
            ->orderByDesc('id')
            ->first();
        if ($previous) {
            $hasStatuses = VisaStatus::query()
                ->where('visa_renewal_category_id', $previous->id)
                ->exists();
            if ($hasStatuses) {
                return $previous;
            }
        }

        return null;
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

    public static function activeStatusesForCategory(int $categoryId): Collection
    {
        return VisaStatus::getActiveForCategory($categoryId);
    }

    public static function accountForRiderCategory(int $riderId, int $categoryId): ?ExpenseAccount
    {
        $query = ExpenseAccount::query()->visa()->where('rider_id', $riderId);
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
            ->visa()
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
