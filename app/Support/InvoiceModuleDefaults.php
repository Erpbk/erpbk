<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use App\Models\Settings;

/**
 * Per-module invoice defaults (title, note body, terms) stored in settings.
 */
class InvoiceModuleDefaults
{
    /**
     * @return array{title: string, notes: string, terms_and_conditions: string, default_title: string}
     */
    public static function config(string $module): array
    {
        $module = self::normalize($module);

        return match ($module) {
            'customer_invoices' => [
                'title_key' => CustomerInvoiceDefaults::TITLE_KEY,
                'notes_key' => CustomerInvoiceDefaults::NOTES_KEY,
                'terms_key' => CustomerInvoiceDefaults::TERMS_KEY,
                'default_title' => CustomerInvoiceDefaults::DEFAULT_TITLE,
                'notes_alias' => 'customer_notes',
            ],
            'invoices', 'rider_invoices' => [
                'title_key' => 'rider_invoice_title',
                'notes_key' => 'rider_invoice_notes',
                'terms_key' => 'rider_invoice_terms_and_conditions',
                'default_title' => 'RIDER INVOICE',
                'notes_alias' => 'notes',
            ],
            'employee_invoices' => [
                'title_key' => 'employee_invoice_title',
                'notes_key' => 'employee_invoice_notes',
                'terms_key' => 'employee_invoice_terms_and_conditions',
                'default_title' => 'EMPLOYEE INVOICE',
                'notes_alias' => 'notes',
            ],
            'supplier_invoices' => [
                'title_key' => 'supplier_invoice_title',
                'notes_key' => 'supplier_invoice_notes',
                'terms_key' => 'supplier_invoice_terms_and_conditions',
                'default_title' => 'SUPPLIER INVOICE',
                'notes_alias' => 'notes',
            ],
            'sim_invoices' => [
                'title_key' => 'sim_invoice_title',
                'notes_key' => 'sim_invoice_notes',
                'terms_key' => 'sim_invoice_terms_and_conditions',
                'default_title' => 'SIM INVOICE',
                'notes_alias' => 'notes',
            ],
            'leasing_company_invoices', 'leasing_invoices' => [
                'title_key' => 'leasing_company_invoice_title',
                'notes_key' => 'leasing_company_invoice_notes',
                'terms_key' => 'leasing_company_invoice_terms_and_conditions',
                'default_title' => 'LEASING COMPANY INVOICE',
                'notes_alias' => 'notes',
            ],
            'leasing_company_billing_invoices', 'leasing_billing_invoice' => [
                'title_key' => 'leasing_billing_invoice_title',
                'notes_key' => 'leasing_billing_invoice_notes',
                'terms_key' => 'leasing_billing_invoice_terms_and_conditions',
                'default_title' => 'RENTAL BILL',
                'notes_alias' => 'notes',
            ],
            default => [
                'title_key' => $module . '_invoice_title',
                'notes_key' => $module . '_invoice_notes',
                'terms_key' => $module . '_invoice_terms_and_conditions',
                'default_title' => strtoupper(str_replace('_', ' ', $module)),
                'notes_alias' => 'notes',
            ],
        };
    }

    public static function normalize(string $module): string
    {
        $module = trim($module);

        return match ($module) {
            'invoices' => 'rider_invoices',
            'leasing_invoices' => 'leasing_company_invoices',
            'leasing_billing_invoice' => 'leasing_company_billing_invoices',
            default => $module,
        };
    }

    /**
     * Modules that use invoice defaults on the settings General tab.
     *
     * @return list<string>
     */
    public static function settingsModules(): array
    {
        return [
            'customer_invoices',
            'rider_invoices',
            'invoices',
            'employee_invoices',
            'supplier_invoices',
            'sim_invoices',
            'leasing_company_invoices',
            'leasing_invoices',
            'leasing_company_billing_invoices',
            'leasing_billing_invoice',
        ];
    }

    public static function title(string $module, ?int $companyId = null): string
    {
        $cfg = self::config($module);
        $value = trim((string) (self::get($cfg['title_key'], $companyId) ?? ''));

        return $value !== '' ? $value : $cfg['default_title'];
    }

    public static function notes(string $module, ?int $companyId = null): string
    {
        $cfg = self::config($module);

        return (string) (self::get($cfg['notes_key'], $companyId) ?? '');
    }

    public static function terms(string $module, ?int $companyId = null): string
    {
        $cfg = self::config($module);

        return (string) (self::get($cfg['terms_key'], $companyId) ?? '');
    }

    /**
     * @return array{title: string, terms_and_conditions: string, notes: string, customer_notes?: string}
     */
    public static function all(string $module, ?int $companyId = null): array
    {
        $cfg = self::config($module);
        $notes = self::notes($module, $companyId);
        $result = [
            'title' => self::title($module, $companyId),
            'terms_and_conditions' => self::terms($module, $companyId),
            'notes' => $notes,
        ];
        if (($cfg['notes_alias'] ?? 'notes') === 'customer_notes') {
            $result['customer_notes'] = $notes;
        }

        return $result;
    }

    public static function save(string $module, string $terms, string $notes, ?int $companyId = null, ?string $title = null): void
    {
        $cfg = self::config($module);
        $companyId = $companyId ?? CompanyContext::id() ?? auth()->user()?->company_id;

        if ($title !== null) {
            self::put($cfg['title_key'], trim($title), $companyId);
        }
        self::put($cfg['terms_key'], $terms, $companyId);
        self::put($cfg['notes_key'], $notes, $companyId);

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
