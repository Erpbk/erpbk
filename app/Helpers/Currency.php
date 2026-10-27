<?php

namespace App\Helpers;

use App\Models\Company;
use App\Models\Settings;
use App\Support\CompanyContext;
use App\Support\CompanyGlobalPreferences;
use App\Support\PublicStorageDisk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class Currency
{
    /** @var array<string, array<string, string>> */
    private static array $settingsByCompany = [];

    /** @var array<string, string|null> */
    private static array $iconSrcByCompany = [];

    public static function clearCache(?int $companyId = null): void
    {
        if ($companyId === null) {
            self::$settingsByCompany = [];
            self::$iconSrcByCompany = [];

            return;
        }

        unset(self::$settingsByCompany[(string) $companyId], self::$settingsByCompany['none']);
        unset(self::$iconSrcByCompany['src:' . $companyId], self::$iconSrcByCompany['src:none']);
    }

    private static function companyId(): ?int
    {
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
    private static function settings(?int $forCompanyId = null): array
    {
        $companyId = $forCompanyId ?? self::companyId();
        $cacheKey = $companyId !== null ? (string) $companyId : 'none';

        if (! array_key_exists($cacheKey, self::$settingsByCompany)) {
            $query = Settings::query()->whereIn('name', ['currency_code', 'currency_symbol', 'currency_icon']);

            if ($companyId !== null) {
                $query->where('company_id', $companyId);
            }

            self::$settingsByCompany[$cacheKey] = $query->pluck('value', 'name')->toArray();
        }

        return self::$settingsByCompany[$cacheKey];
    }

    /**
     * @return array<string, array{name: string, symbol: string}>
     */
    public static function catalog(): array
    {
        $raw = config('currencies', []);
        $catalog = [];

        foreach ($raw as $code => $meta) {
            $normalized = strtoupper(trim((string) $code));
            if ($normalized === '' || ! is_array($meta)) {
                continue;
            }

            $catalog[$normalized] = [
                'name' => (string) ($meta['name'] ?? $normalized),
                'symbol' => (string) ($meta['symbol'] ?? $normalized),
            ];
        }

        return $catalog;
    }

    public static function catalogCodes(): array
    {
        return array_keys(self::catalog());
    }

    public static function defaultSymbolForCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $catalog = self::catalog();

        if (isset($catalog[$normalized]['symbol']) && $catalog[$normalized]['symbol'] !== '') {
            return $catalog[$normalized]['symbol'];
        }

        return $normalized !== '' ? $normalized : 'AED';
    }

    public static function code(?int $forCompanyId = null): string
    {
        $settings = self::settings($forCompanyId);
        $code = trim((string) ($settings['currency_code'] ?? 'AED'));
        $code = $code !== '' ? strtoupper($code) : 'AED';

        $catalog = self::catalog();
        if ($catalog !== [] && ! isset($catalog[$code])) {
            return 'AED';
        }

        return $code;
    }

    public static function symbol(): string
    {
        $settings = self::settings();
        $symbol = trim((string) ($settings['currency_symbol'] ?? ''));

        if ($symbol !== '') {
            return $symbol;
        }

        return self::defaultSymbolForCode(self::code());
    }

    public static function iconPath(): ?string
    {
        $path = trim((string) (self::settings()['currency_icon'] ?? ''));

        return $path !== '' ? $path : null;
    }

    public static function usesIcon(): bool
    {
        $path = self::iconPath();

        return $path !== null && PublicStorageDisk::exists($path);
    }

    public static function iconUrl(): ?string
    {
        if (! self::usesIcon()) {
            return null;
        }

        return PublicStorageDisk::url(self::iconPath());
    }

    /**
     * Prefer a data URI so DomPDF/email clients can render without remote fetches.
     * Falls back to the public URL.
     */
    public static function iconSrc(): ?string
    {
        if (! self::usesIcon()) {
            return null;
        }

        $companyId = self::companyId();
        $cacheKey = 'src:' . ($companyId !== null ? (string) $companyId : 'none');
        if (array_key_exists($cacheKey, self::$iconSrcByCompany)) {
            return self::$iconSrcByCompany[$cacheKey];
        }

        $readable = PublicStorageDisk::readablePath(self::iconPath());
        if ($readable !== null) {
            $binary = @file_get_contents($readable);
            if ($binary !== false && $binary !== '') {
                $mime = @mime_content_type($readable) ?: 'image/png';
                self::$iconSrcByCompany[$cacheKey] = 'data:' . $mime . ';base64,' . base64_encode($binary);

                return self::$iconSrcByCompany[$cacheKey];
            }
        }

        self::$iconSrcByCompany[$cacheKey] = self::iconUrl();

        return self::$iconSrcByCompany[$cacheKey];
    }

    public static function mark(): HtmlString
    {
        $iconSrc = self::iconSrc();
        if ($iconSrc) {
            return new HtmlString(
                '<img class="app-currency-icon" src="' . e($iconSrc) . '" alt="' . e(self::code()) . '" />'
            );
        }

        return new HtmlString(e(self::symbol()));
    }

    public static function formatPlain(float|int|string|null $amount, ?int $decimals = null): string
    {
        $formattedAmount = Number::format($amount, $decimals);
        $symbol = self::symbol();

        if (CompanyGlobalPreferences::currencyPosition() === 'after') {
            return $formattedAmount . ' ' . $symbol;
        }

        return $symbol . ' ' . $formattedAmount;
    }

    public static function format(float|int|string|null $amount, ?int $decimals = null): HtmlString|string
    {
        $formattedAmount = Number::format($amount, $decimals);
        $position = CompanyGlobalPreferences::currencyPosition();

        if (self::usesIcon()) {
            $mark = self::mark()->toHtml();
            $html = $position === 'after'
                ? e($formattedAmount) . ' ' . $mark
                : $mark . ' ' . e($formattedAmount);

            return new HtmlString($html);
        }

        $symbol = self::symbol();
        if ($position === 'after') {
            return $formattedAmount . ' ' . $symbol;
        }

        return $symbol . ' ' . $formattedAmount;
    }

    /**
     * Persist company currency code/symbol/icon from a settings form request.
     */
    public static function persistForCompany(Request $request, Company $company): void
    {
        $code = strtoupper(trim((string) $request->input('settings.currency_code', self::code())));
        $catalog = self::catalog();
        if ($code === '' || ! isset($catalog[$code])) {
            $code = 'AED';
        }

        $symbol = self::defaultSymbolForCode($code);
        $existingIcon = (string) (Settings::query()
            ->where('company_id', $company->id)
            ->where('name', 'currency_icon')
            ->value('value') ?? '');

        $mode = (string) $request->input('currency_icon_mode', $existingIcon !== '' ? 'custom' : 'default');
        $removeIcon = $request->boolean('remove_currency_icon') || $mode === 'default';
        $iconPath = $existingIcon;

        if ($removeIcon && $existingIcon !== '') {
            if (PublicStorageDisk::exists($existingIcon)) {
                PublicStorageDisk::disk()->delete($existingIcon);
            }
            $iconPath = '';
        }

        if ($mode === 'custom' && $request->hasFile('currency_icon')) {
            $path = $request->file('currency_icon')->store('currency-icons', 'public');
            if ($existingIcon !== '' && $existingIcon !== $path && PublicStorageDisk::exists($existingIcon)) {
                PublicStorageDisk::disk()->delete($existingIcon);
            }
            $iconPath = $path;
        }

        foreach ([
            'currency_code' => $code,
            'currency_symbol' => $symbol,
            'currency_icon' => $iconPath,
        ] as $name => $value) {
            Settings::updateOrCreate(
                ['name' => $name, 'company_id' => $company->id],
                ['name' => $name, 'value' => $value, 'company_id' => $company->id]
            );
        }

        self::clearCache($company->id);
        cache()->forget('settings');
    }
}
