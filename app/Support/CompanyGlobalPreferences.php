<?php

namespace App\Support;

use App\Helpers\Currency;
use App\Models\Settings;
use Illuminate\Support\Facades\Auth;

/**
 * Company-scoped global display preferences (font, date, number/currency formatting).
 */
class CompanyGlobalPreferences
{
    /** @var array<string, array<string, string>> */
    private static array $cacheByCompany = [];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(config('global_preferences.defaults', []));
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $defaults = config('global_preferences.defaults', []);

        return array_map(static fn ($v) => (string) $v, $defaults);
    }

    private static function companyId(?int $companyId = null): ?int
    {
        if ($companyId !== null) {
            return $companyId;
        }

        $id = CompanyContext::id();
        if ($id !== null) {
            return $id;
        }

        $authUser = Auth::user();
        if ($authUser && ! empty($authUser->company_id)) {
            return (int) $authUser->company_id;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(?int $companyId = null): array
    {
        $companyId = self::companyId($companyId);
        $cacheKey = $companyId !== null ? (string) $companyId : 'none';

        if (array_key_exists($cacheKey, self::$cacheByCompany)) {
            return self::$cacheByCompany[$cacheKey];
        }

        $defaults = self::defaults();
        $keys = array_keys($defaults);

        if ($companyId === null || $keys === []) {
            return self::$cacheByCompany[$cacheKey] = $defaults;
        }

        $stored = Settings::query()
            ->where('company_id', $companyId)
            ->whereIn('name', $keys)
            ->pluck('value', 'name')
            ->all();

        $merged = $defaults;
        foreach ($keys as $key) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }
            $value = trim((string) $stored[$key]);
            if ($value === '' || ! self::isAllowed($key, $value)) {
                continue;
            }
            $merged[$key] = $value;
        }

        return self::$cacheByCompany[$cacheKey] = $merged;
    }

    public static function get(string $key, ?int $companyId = null): string
    {
        $all = self::all($companyId);

        return $all[$key] ?? (string) (self::defaults()[$key] ?? '');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values, ?int $companyId = null): void
    {
        $companyId = self::companyId($companyId);
        if ($companyId === null) {
            return;
        }

        $defaults = self::defaults();

        foreach ($values as $key => $value) {
            $key = (string) $key;
            if (! array_key_exists($key, $defaults)) {
                continue;
            }

            $normalized = self::normalize($key, $value);
            if ($normalized === null || ! self::isAllowed($key, $normalized)) {
                $normalized = $defaults[$key];
            }

            Settings::updateOrCreate(
                ['name' => $key, 'company_id' => $companyId],
                ['name' => $key, 'value' => $normalized, 'company_id' => $companyId]
            );
        }

        self::clearCache($companyId);
        Currency::clearCache($companyId);
    }

    public static function clearCache(?int $companyId = null): void
    {
        if ($companyId === null) {
            self::$cacheByCompany = [];

            return;
        }

        unset(self::$cacheByCompany[(string) $companyId], self::$cacheByCompany['none']);
    }

    public static function phpDateFormat(?int $companyId = null): string
    {
        return self::get('date_format', $companyId) ?: 'd-m-Y';
    }

    public static function decimalPlaces(?int $companyId = null): int
    {
        return max(0, min(4, (int) self::get('decimal_places', $companyId)));
    }

    public static function thousandSeparator(?int $companyId = null): string
    {
        $sep = self::get('thousand_separator', $companyId);

        return match ($sep) {
            'none' => '',
            'space' => ' ',
            default => $sep,
        };
    }

    public static function decimalSeparator(?int $companyId = null): string
    {
        return self::get('decimal_separator', $companyId) ?: '.';
    }

    public static function currencyPosition(?int $companyId = null): string
    {
        $pos = self::get('currency_position', $companyId);

        return $pos === 'after' ? 'after' : 'before';
    }

    public static function fontFamily(?int $companyId = null): string
    {
        return self::get('font_family', $companyId) ?: 'Public Sans';
    }

    public static function fontCssStack(?int $companyId = null, ?string $family = null): string
    {
        $family = $family ?? self::fontFamily($companyId);
        if ($family === 'system-ui') {
            return 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        }

        return '"' . str_replace('"', '', $family) . '", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    }

    public static function googleFontQuery(?int $companyId = null, ?string $family = null): ?string
    {
        $family = $family ?? self::fontFamily($companyId);
        $fonts = config('global_preferences.font_families', []);
        $query = $fonts[$family]['google'] ?? null;

        return is_string($query) && $query !== '' ? $query : null;
    }

    private static function normalize(string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($key === 'decimal_places') {
            return (string) max(0, min(4, (int) $value));
        }

        if ($key === 'thousand_separator' && $value === 'none') {
            return 'none';
        }

        return trim((string) $value);
    }

    private static function isAllowed(string $key, string $value): bool
    {
        return match ($key) {
            'font_family' => array_key_exists($value, config('global_preferences.font_families', [])),
            'date_format' => array_key_exists($value, config('global_preferences.date_formats', [])),
            'decimal_places' => in_array((int) $value, config('global_preferences.decimal_places_options', [0, 1, 2, 3, 4]), true),
            'thousand_separator' => array_key_exists($value, config('global_preferences.thousand_separators', [])),
            'decimal_separator' => array_key_exists($value, config('global_preferences.decimal_separators', [])),
            'currency_position' => array_key_exists($value, config('global_preferences.currency_positions', [])),
            default => false,
        };
    }
}
