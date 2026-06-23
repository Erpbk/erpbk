<?php

namespace App\Support;

class VisaExpenseAccess
{
    /**
     * Visa Expense appears in the main sidebar when "Show in Menu" is granted.
     */
    public static function visibleInSidebar(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->can('visaexpense_show_in_menu') && $user->can('visaexpense_view');
    }

    /**
     * Visa Expense tab on the rider profile for any user with view permission.
     */
    public static function visibleInRiderTab(): bool
    {
        $user = auth()->user();

        return $user && $user->can('visaexpense_view');
    }
}
