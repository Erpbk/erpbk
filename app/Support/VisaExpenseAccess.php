<?php

namespace App\Support;

/**
 * Independent Visa Expense permission categories:
 * - Account Creation: create / manage expense accounts
 * - Entries: create / view / edit / delete expense entries
 * - Installments: create / view / edit / manage installment plans
 *
 * Each category is assigned separately; holding one never implies the others.
 */
class VisaExpenseAccess
{
    public const ACCOUNT = 'visa_expense_account_creation';

    public const ENTRIES = 'visa_expense_entries';

    public const INSTALLMENTS = 'visa_expense_installments';

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['view', 'create', 'edit', 'delete'];
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [self::ACCOUNT, self::ENTRIES, self::INSTALLMENTS];
    }

    public static function permission(string $category, string $action): string
    {
        return $category . '_' . $action;
    }

    public static function allows(string $category, string $action): bool
    {
        return user_can(self::permission($category, $action));
    }

    public static function allowsAny(string $action, ?array $categories = null): bool
    {
        foreach ($categories ?? self::categories() as $category) {
            if (self::allows($category, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function permissionsForAction(string $action, ?array $categories = null): array
    {
        $out = [];
        foreach ($categories ?? self::categories() as $category) {
            $out[] = self::permission($category, $action);
        }

        return $out;
    }

    /**
     * Visa Expense appears in the main sidebar when the user can view accounts or entries.
     */
    public static function visibleInSidebar(): bool
    {
        return self::allows(self::ACCOUNT, 'view')
            || self::allows(self::ENTRIES, 'view');
    }

    /**
     * Visa Expense tab on the rider profile for any of the three categories.
     */
    public static function visibleInRiderTab(): bool
    {
        return self::allowsAny('view');
    }

    public static function canManageAccounts(string $action = 'view'): bool
    {
        return self::allows(self::ACCOUNT, $action);
    }

    public static function canManageEntries(string $action = 'view'): bool
    {
        return self::allows(self::ENTRIES, $action);
    }

    public static function canManageInstallments(string $action = 'view'): bool
    {
        return self::allows(self::INSTALLMENTS, $action);
    }
}
