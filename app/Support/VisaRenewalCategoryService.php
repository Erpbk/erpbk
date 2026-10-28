<?php

namespace App\Support;

use App\Support\GlobalAccounts;
use App\Models\ExpenseAccount;
use App\Models\VisaRenewalCategory;
use App\Models\VisaStatus;
use App\Models\visa_expenses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Standard visa status ticket templates (used by seeder for initial master list).
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
     * Attach existing master statuses to a category (pivot only — no new status rows).
     *
     * @param  list<int>|array<int, mixed>  $statusIds
     */
    public static function attachStatusesToCategory(VisaRenewalCategory $category, array $statusIds): int
    {
        $ids = collect($statusIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $validIds = VisaStatus::query()
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($validIds === []) {
            return 0;
        }

        $maxOrder = (int) $category->visaStatuses()->max('visa_renewal_category_visa_status.display_order');
        $sync = [];
        foreach ($validIds as $index => $statusId) {
            $sync[$statusId] = ['display_order' => $maxOrder + $index + 1];
        }

        $category->visaStatuses()->syncWithoutDetaching($sync);

        return count($sync);
    }

    /**
     * Replace a category's ticket assignments (mass assign/unassign), preserving order for kept tickets.
     *
     * @param  list<int>|array<int, mixed>  $statusIds
     */
    public static function syncStatusesForCategory(VisaRenewalCategory $category, array $statusIds): int
    {
        $ids = collect($statusIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $validIds = $ids->isEmpty()
            ? []
            : VisaStatus::query()
                ->whereIn('id', $ids->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $existing = $category->visaStatuses()
            ->get()
            ->keyBy(fn ($status) => (int) $status->id);

        $maxOrder = (int) $existing->max(fn ($status) => (int) ($status->pivot->display_order ?? 0));
        $sync = [];
        foreach ($validIds as $statusId) {
            if ($existing->has($statusId)) {
                $sync[$statusId] = [
                    'display_order' => (int) ($existing->get($statusId)->pivot->display_order ?? 1),
                ];
                continue;
            }
            $maxOrder++;
            $sync[$statusId] = ['display_order' => $maxOrder];
        }

        $category->visaStatuses()->sync($sync);

        return count($sync);
    }

    /**
     * Sync a status's category assignments, preserving existing pivot display_order where possible.
     *
     * @param  list<int>|array<int, mixed>  $categoryIds
     */
    public static function syncStatusCategories(VisaStatus $status, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $existing = $status->renewalCategories()
            ->get()
            ->keyBy(fn ($c) => (int) $c->id);

        $sync = [];
        foreach ($ids as $categoryId) {
            if ($existing->has($categoryId)) {
                $sync[$categoryId] = [
                    'display_order' => (int) ($existing->get($categoryId)->pivot->display_order ?? 1),
                ];
                continue;
            }

            $maxOrder = (int) DB::table('visa_renewal_category_visa_status')
                ->where('visa_renewal_category_id', $categoryId)
                ->max('display_order');

            $sync[$categoryId] = ['display_order' => $maxOrder + 1];
        }

        $status->renewalCategories()->sync($sync);
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
        return self::accountForPersonCategory('rider', $riderId, $categoryId);
    }

    public static function accountForEmployeeCategory(int $employeeId, int $categoryId): ?ExpenseAccount
    {
        return self::accountForPersonCategory('employee', $employeeId, $categoryId);
    }

    public static function accountForPersonCategory(string $personType, int $personId, int $categoryId): ?ExpenseAccount
    {
        $query = ExpenseAccount::query()->visa();
        if ($personType === 'employee') {
            $query->where('employee_id', $personId)->whereNull('rider_id');
        } else {
            $query->where('rider_id', $personId);
        }

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
     * Base query for visa expenses belonging to an expense account (incl. legacy head-account rows for riders).
     */
    public static function expensesForAccountQuery(int $expenseAccountId, ?int $riderId = null, ?int $renewalCategoryId = null): Builder
    {
        $query = visa_expenses::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($expenseAccountId, $riderId) {
                $q->where('expense_account_id', $expenseAccountId);
                $headId = self::visaExpenseHeadAccountId();
                if ($headId !== null && $riderId) {
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

        return (int) self::expensesForAccountQuery(
            (int) $account->id,
            $account->rider_id ? (int) $account->rider_id : null,
            $categoryId
        )
            ->where('payment_status', 'unpaid')
            ->count();
    }

    public static function unpaidCountForCategory(int $expenseAccountId, ?int $riderId, int $categoryId): int
    {
        return (int) self::expensesForAccountQuery($expenseAccountId, $riderId, $categoryId)
            ->where('payment_status', 'unpaid')
            ->count();
    }

    /**
     * Next renewal category that may receive a new expense account for this person under the strict
     * sequential rules (New Visa → 1st Renewal). Returns null when blocked by unpaid entries or
     * when the first-two-step sequence is complete (after that all remaining are open).
     *
     * Used only for legacy/error-message contexts. Prefer creatableCategoriesForPerson() for UI.
     */
    public static function nextCreatableCategoryForPerson(string $personType, int $personId): ?VisaRenewalCategory
    {
        $categories = self::activeOrdered();

        foreach ($categories as $category) {
            $account = self::accountForPersonCategory($personType, $personId, (int) $category->id);
            if (!$account) {
                return $category;
            }
            if (self::unpaidCountForAccount($account) > 0) {
                return null;
            }
        }

        return null;
    }

    public static function nextCreatableCategoryForRider(int $riderId): ?VisaRenewalCategory
    {
        return self::nextCreatableCategoryForPerson('rider', $riderId);
    }

    public static function nextCreatableCategoryForEmployee(int $employeeId): ?VisaRenewalCategory
    {
        return self::nextCreatableCategoryForPerson('employee', $employeeId);
    }

    /**
     * Whether this person has completed the first-renewal threshold.
     *
     * The threshold is reached when:
     *   1. The default (New Visa) category account exists and has no unpaid entries, AND
     *   2. The second category (1st Renewal) account exists and has no unpaid entries.
     *
     * Once the threshold is passed, all remaining categories without accounts become creatable.
     */
    private static function hasCompletedFirstRenewal(string $personType, int $personId): bool
    {
        $categories = self::activeOrdered();

        // Need at least 2 categories (New Visa + 1st Renewal) to reach the threshold.
        if ($categories->count() < 2) {
            return false;
        }

        // Check the first two categories in display_order.
        foreach ($categories->take(2) as $category) {
            $account = self::accountForPersonCategory($personType, $personId, (int) $category->id);
            if (!$account || self::unpaidCountForAccount($account) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Categories the user may select when creating a new expense account for this person.
     *
     * All active categories that do not yet have an account for this person are returned.
     * There is no sequential dependency — any category may be created independently.
     */
    public static function creatableCategoriesForPerson(string $personType, int $personId): Collection
    {
        return self::activeOrdered()->filter(
            static fn ($category) => self::accountForPersonCategory($personType, $personId, (int) $category->id) === null
        )->values();
    }

    public static function creatableCategoriesForRider(int $riderId): Collection
    {
        return self::creatableCategoriesForPerson('rider', $riderId);
    }

    public static function creatableCategoriesForEmployee(int $employeeId): Collection
    {
        return self::creatableCategoriesForPerson('employee', $employeeId);
    }

    public static function canCreateAccountForCategory(int $riderId, int $categoryId): bool
    {
        return self::canCreateAccountForPersonCategory('rider', $riderId, $categoryId);
    }

    public static function canCreateAccountForPersonCategory(string $personType, int $personId, int $categoryId): bool
    {
        if (!self::findActive($categoryId)) {
            return false;
        }

        if (self::accountForPersonCategory($personType, $personId, $categoryId)) {
            return false;
        }

        $creatableIds = self::creatableCategoriesForPerson($personType, $personId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return in_array((int) $categoryId, $creatableIds, true);
    }

    public static function resolveCategoryForAccount(ExpenseAccount $account): VisaRenewalCategory
    {
        if ($account->renewal_category_id) {
            return self::findActive((int) $account->renewal_category_id) ?? self::defaultCategory();
        }

        return self::defaultCategory();
    }

    /**
     * Other expense accounts for the same person (for cross-account navigation).
     */
    public static function siblingAccountsForPerson(ExpenseAccount $account, ?int $excludeAccountId = null): Collection
    {
        $query = ExpenseAccount::query()
            ->visa()
            ->with('renewalCategory')
            ->orderBy('id');

        if ($account->employee_id) {
            $query->where('employee_id', $account->employee_id);
        } else {
            $query->where('rider_id', $account->rider_id);
        }

        if ($excludeAccountId) {
            $query->where('id', '!=', $excludeAccountId);
        }

        return $query->get();
    }

    /**
     * Current/latest visa expense account for a person: highest category display_order, then newest id.
     */
    public static function latestAccountForPerson(ExpenseAccount $account): ExpenseAccount
    {
        $accounts = self::siblingAccountsForPerson($account);
        if ($accounts->isEmpty()) {
            return $account;
        }

        return $accounts->sort(function (ExpenseAccount $a, ExpenseAccount $b) {
            $orderA = (int) ($a->renewalCategory->display_order ?? 0);
            $orderB = (int) ($b->renewalCategory->display_order ?? 0);
            if ($orderA !== $orderB) {
                return $orderB <=> $orderA;
            }

            return (int) $b->id <=> (int) $a->id;
        })->first() ?? $account;
    }

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
