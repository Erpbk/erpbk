<?php

namespace App\Support;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

/**
 * Company-level default Customer Notes / Terms & Conditions for customer invoices.
 */
class CustomerInvoiceDefaults
{
    public const TERMS_KEY = 'customer_invoice_terms_and_conditions';

    public const NOTES_KEY = 'customer_invoice_customer_notes';

    public static function terms(?int $companyId = null): string
    {
        return (string) (self::get(self::TERMS_KEY, $companyId) ?? '');
    }

    public static function notes(?int $companyId = null): string
    {
        return (string) (self::get(self::NOTES_KEY, $companyId) ?? '');
    }

    /**
     * @return array{terms_and_conditions: string, customer_notes: string}
     */
    public static function all(?int $companyId = null): array
    {
        return [
            'terms_and_conditions' => self::terms($companyId),
            'customer_notes' => self::notes($companyId),
        ];
    }

    public static function save(string $terms, string $notes, ?int $companyId = null): void
    {
        $companyId = $companyId ?? CompanyContext::id() ?? auth()->user()?->company_id;

        self::put(self::TERMS_KEY, $terms, $companyId);
        self::put(self::NOTES_KEY, $notes, $companyId);

        Cache::forget('settings');
    }

    protected static function get(string $name, ?int $companyId = null): ?string
    {
        $companyId = $companyId ?? CompanyContext::id() ?? auth()->user()?->company_id;

        $query = Settings::query()->where('name', $name);
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END');
        }

        $value = $query->value('value');

        return $value !== null ? (string) $value : null;
    }

    protected static function put(string $name, string $value, ?int $companyId): void
    {
        $attrs = ['name' => $name];
        if ($companyId) {
            $attrs['company_id'] = $companyId;
        }

        Settings::updateOrCreate($attrs, [
            'name' => $name,
            'value' => $value,
            'company_id' => $companyId,
        ]);
    }
}
