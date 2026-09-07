<?php

namespace App\Support;

use App\Models\ExpenseAccount;
use App\Models\LicenseCategory;
use App\Models\LicenseStatus;
use App\Models\license_expenses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LicenseCategoryService
{
    public static function activeOrdered(): Collection
    {
        return LicenseCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public static function allOrdered(): Collection
    {
        return LicenseCategory::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public static function ensureDefaultExists(): LicenseCategory
    {
        $default = LicenseCategory::query()->where('is_default', true)->first();
        if ($default) {
            return $default;
        }

        $first = LicenseCategory::query()->orderBy('display_order')->orderBy('id')->first();
        if ($first) {
            if (! $first->is_default) {
                $first->is_default = true;
                $first->save();
            }

            return $first;
        }

        $created = LicenseCategory::create([
            'name' => 'New License',
            'display_order' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);
        self::seedStatusesForCategory($created);

        return $created;
    }

    public static function defaultCategory(): LicenseCategory
    {
        return self::ensureDefaultExists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultStatusTemplates(): array
    {
        return [
            ['name' => 'RTA File Opening', 'code' => 'RFO', 'description' => 'Open driving license file at RTA / driving institute', 'default_fee' => 200.00, 'category' => 'Document', 'is_required' => true, 'display_order' => 1],
            ['name' => 'Learning Permit', 'code' => 'LP', 'description' => 'Issue learner permit for motorcycle / light vehicle', 'default_fee' => 450.00, 'category' => 'Permit', 'is_required' => true, 'display_order' => 2],
            ['name' => 'Theory Test (Knowledge Test)', 'code' => 'TT', 'description' => 'RTA knowledge / theory test fee', 'default_fee' => 200.00, 'category' => 'License', 'is_required' => true, 'display_order' => 3],
            ['name' => 'Theory Test Retake', 'code' => 'TTR', 'description' => 'Retake fee for failed theory test', 'default_fee' => 200.00, 'category' => 'License', 'is_required' => false, 'display_order' => 4],
            ['name' => 'Eye Test / Medical Fitness', 'code' => 'ETM', 'description' => 'Vision screening and medical fitness for license', 'default_fee' => 150.00, 'category' => 'Other', 'is_required' => true, 'display_order' => 5],
            ['name' => 'Driving Classes', 'code' => 'DC', 'description' => 'Practical training sessions at driving institute', 'default_fee' => 1200.00, 'category' => 'License', 'is_required' => true, 'display_order' => 6],
            ['name' => 'Yard Test (Internal Assessment)', 'code' => 'YT', 'description' => 'Internal yard / parking assessment before RTA road test', 'default_fee' => 250.00, 'category' => 'License', 'is_required' => true, 'display_order' => 7],
            ['name' => 'RTA Road Test', 'code' => 'RRT', 'description' => 'Final RTA road test fee', 'default_fee' => 300.00, 'category' => 'License', 'is_required' => true, 'display_order' => 8],
            ['name' => 'Road Test Retake', 'code' => 'RTR', 'description' => 'Retake fee for failed RTA road test', 'default_fee' => 300.00, 'category' => 'License', 'is_required' => false, 'display_order' => 9],
            ['name' => 'License Issuance Fee', 'code' => 'LIF', 'description' => 'Final driving license issuance at RTA', 'default_fee' => 420.00, 'category' => 'License', 'is_required' => true, 'display_order' => 10],
            ['name' => 'Knowledge & Innovation Fee', 'code' => 'KIF', 'description' => 'Dubai government knowledge and innovation fee', 'default_fee' => 20.00, 'category' => 'Other', 'is_required' => true, 'display_order' => 11],
            ['name' => 'Golden Chance (Direct Test)', 'code' => 'GC', 'description' => 'Golden chance direct road test without full training', 'default_fee' => 600.00, 'category' => 'License', 'is_required' => false, 'display_order' => 12],
            ['name' => 'File Renewal', 'code' => 'FR', 'description' => 'Renew expired driving license file at RTA', 'default_fee' => 300.00, 'category' => 'Document', 'is_required' => false, 'display_order' => 13],
            ['name' => 'License Amendment / Category Change', 'code' => 'LAC', 'description' => 'Change license category or amend file details', 'default_fee' => 350.00, 'category' => 'License', 'is_required' => false, 'display_order' => 14],
            ['name' => 'RTA Violation / Fine', 'code' => 'RVF', 'description' => 'RTA violations or fines during licensing process', 'default_fee' => 100.00, 'category' => 'Other', 'is_required' => false, 'display_order' => 15],
        ];
    }

    /**
     * @return array{count: int, source: string|null}
     */
    public static function seedStatusesForCategory(LicenseCategory $category): array
    {
        $existing = LicenseStatus::query()
            ->where('license_category_id', $category->id)
            ->count();
        if ($existing > 0) {
            return ['count' => 0, 'source' => null];
        }

        $sourceCategory = self::statusCopySource($category);
        $createdBy = auth()->id();

        if ($sourceCategory) {
            $sourceStatuses = LicenseStatus::query()
                ->where('license_category_id', $sourceCategory->id)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            foreach ($sourceStatuses as $status) {
                LicenseStatus::create([
                    'license_category_id' => $category->id,
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
            LicenseStatus::create([
                'license_category_id' => $category->id,
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

    private static function statusCopySource(LicenseCategory $category): ?LicenseCategory
    {
        $default = LicenseCategory::query()->where('is_default', true)->first();
        if ($default && (int) $default->id !== (int) $category->id) {
            $hasStatuses = LicenseStatus::query()
                ->where('license_category_id', $default->id)
                ->exists();
            if ($hasStatuses) {
                return $default;
            }
        }

        $previous = LicenseCategory::query()
            ->where('id', '!=', $category->id)
            ->orderByDesc('display_order')
            ->orderByDesc('id')
            ->first();
        if ($previous) {
            $hasStatuses = LicenseStatus::query()
                ->where('license_category_id', $previous->id)
                ->exists();
            if ($hasStatuses) {
                return $previous;
            }
        }

        return null;
    }

    public static function licenseExpenseHeadAccountId(): ?int
    {
        return GlobalAccounts::idOrNull('LICENSE_EXPENSE_ACCOUNT');
    }

    public static function findActive(int $categoryId): ?LicenseCategory
    {
        return LicenseCategory::query()
            ->where('id', $categoryId)
            ->where('is_active', true)
            ->first();
    }

    public static function activeStatusesForCategory(int $categoryId): Collection
    {
        return LicenseStatus::getActiveForCategory($categoryId);
    }

    public static function accountForRiderCategory(int $riderId, int $categoryId): ?ExpenseAccount
    {
        return self::accountForPersonCategory('rider', $riderId, $categoryId);
    }

    public static function accountForPersonCategory(string $personType, int $personId, int $categoryId): ?ExpenseAccount
    {
        $query = ExpenseAccount::query()->license();
        if ($personType === 'employee') {
            $query->where('employee_id', $personId)->whereNull('rider_id');
        } else {
            $query->where('rider_id', $personId);
        }

        $defaultId = (int) self::defaultCategory()->id;

        if ((int) $categoryId === $defaultId) {
            return $query->where(function ($q) use ($categoryId) {
                $q->where('license_category_id', $categoryId)
                    ->orWhereNull('license_category_id');
            })->first();
        }

        return $query->where('license_category_id', $categoryId)->first();
    }

    public static function expensesForAccountQuery(int $expenseAccountId, ?int $riderId = null, ?int $licenseCategoryId = null): Builder
    {
        $query = license_expenses::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($expenseAccountId, $riderId) {
                $q->where('expense_account_id', $expenseAccountId);
                $headId = self::licenseExpenseHeadAccountId();
                if ($headId !== null && $riderId) {
                    $q->orWhere(function ($q2) use ($riderId, $headId) {
                        $q2->where('expense_account_id', $headId)
                            ->where('rider_id', $riderId);
                    });
                }
            });

        if ($licenseCategoryId !== null) {
            $query->where('license_category_id', $licenseCategoryId);
        }

        return $query;
    }

    public static function unpaidCountForAccount(ExpenseAccount $account): int
    {
        $categoryId = $account->license_category_id
            ? (int) $account->license_category_id
            : (int) self::defaultCategory()->id;

        return (int) self::expensesForAccountQuery(
            (int) $account->id,
            $account->rider_id ? (int) $account->rider_id : null,
            $categoryId
        )
            ->where('payment_status', 'unpaid')
            ->count();
    }

    public static function nextCreatableCategoryForPerson(string $personType, int $personId): ?LicenseCategory
    {
        foreach (self::activeOrdered() as $category) {
            $account = self::accountForPersonCategory($personType, $personId, (int) $category->id);
            if (! $account) {
                return $category;
            }
            if (self::unpaidCountForAccount($account) > 0) {
                return null;
            }
        }

        return null;
    }

    public static function nextCreatableCategoryForRider(int $riderId): ?LicenseCategory
    {
        return self::nextCreatableCategoryForPerson('rider', $riderId);
    }

    public static function creatableCategoriesForPerson(string $personType, int $personId): Collection
    {
        $next = self::nextCreatableCategoryForPerson($personType, $personId);

        return $next ? collect([$next]) : collect();
    }

    public static function creatableCategoriesForRider(int $riderId): Collection
    {
        return self::creatableCategoriesForPerson('rider', $riderId);
    }

    public static function canCreateAccountForCategory(int $riderId, int $categoryId): bool
    {
        return self::canCreateAccountForPersonCategory('rider', $riderId, $categoryId);
    }

    public static function canCreateAccountForPersonCategory(string $personType, int $personId, int $categoryId): bool
    {
        if (! self::findActive($categoryId)) {
            return false;
        }

        if (self::accountForPersonCategory($personType, $personId, $categoryId)) {
            return false;
        }

        $next = self::nextCreatableCategoryForPerson($personType, $personId);

        return $next && (int) $next->id === (int) $categoryId;
    }

    public static function resolveCategoryForAccount(ExpenseAccount $account): LicenseCategory
    {
        if ($account->license_category_id) {
            return self::findActive((int) $account->license_category_id) ?? self::defaultCategory();
        }

        return self::defaultCategory();
    }

    public static function siblingAccountsForPerson(ExpenseAccount $account, ?int $excludeAccountId = null): Collection
    {
        $query = ExpenseAccount::query()
            ->license()
            ->with('licenseCategory')
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

    public static function siblingAccountsForRider(int $riderId, ?int $excludeAccountId = null): Collection
    {
        $query = ExpenseAccount::query()
            ->license()
            ->with('licenseCategory')
            ->where('rider_id', $riderId)
            ->orderBy('id');

        if ($excludeAccountId) {
            $query->where('id', '!=', $excludeAccountId);
        }

        return $query->get();
    }

    public static function generatentriesUrl(int $expenseAccountId): string
    {
        return route('LicenseExpense.generatentries', ['id' => $expenseAccountId]);
    }
}
