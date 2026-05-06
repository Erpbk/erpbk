<?php

namespace App\Helpers;

use App\Models\Settings;

class Currency
{
    private static ?array $settings = null;

    private static function settings(): array
    {
        if (self::$settings === null) {
            self::$settings = Settings::whereIn('name', ['currency_code', 'currency_symbol'])
                ->pluck('value', 'name')
                ->toArray();
        }

        return self::$settings;
    }

    public static function code(): string
    {
        $settings = self::settings();
        $code = trim((string) ($settings['currency_code'] ?? 'AED'));

        return $code !== '' ? strtoupper($code) : 'AED';
    }

    public static function symbol(): string
    {
        $settings = self::settings();
        $symbol = trim((string) ($settings['currency_symbol'] ?? 'AED'));

        return $symbol !== '' ? $symbol : self::code();
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $numericAmount = is_numeric($amount) ? (float) $amount : 0.0;

        return self::symbol() . ' ' . number_format($numericAmount, $decimals);
    }
}
