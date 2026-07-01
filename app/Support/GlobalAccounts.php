<?php

namespace App\Support;

use App\Models\Accounts;
use App\Services\GlobalAccountResolver;

/**
 * Resolve system-wide chart accounts by stable code from the global_accounts registry.
 * Valid codes are seeded in GlobalAccountsSeeder and managed in Admin → Global Accounts.
 */
final class GlobalAccounts
{
    public static function id(string $code): int
    {
        return app(GlobalAccountResolver::class)->id($code);
    }

    public static function idOrNull(string $code): ?int
    {
        return app(GlobalAccountResolver::class)->idOrNull($code);
    }

    public static function account(string $code): Accounts
    {
        return app(GlobalAccountResolver::class)->account($code);
    }

    public static function requireExists(array $codes): void
    {
        app(GlobalAccountResolver::class)->requireExists($codes);
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function labelsForCodes(array $codes): array
    {
        return app(GlobalAccountResolver::class)->labelsForCodes($codes);
    }

    /**
     * @return array<int, string>
     */
    public static function salikPaymentAccountLabels(): array
    {
        return self::labelsForCodes([
            'SALIK_PAYABLE_ACCOUNT',
            'VAT_PURCHASE_ACCOUNT',
            'SALIK_ASSET_ACCOUNT',
            'SALIK_ADMIN_CHARGES',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function salikVoucherAccountLabels(): array
    {
        return self::labelsForCodes([
            'SALIK_PAYABLE_ACCOUNT',
            'VAT_ON_SALES',
            'SALIK_ADMIN_CHARGES',
        ]);
    }
}
