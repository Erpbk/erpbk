<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Cache;
use App\Traits\LogsActivity;

class Settings extends BaseModel
{
    use HasFactory, LogsActivity;
    protected $table = "settings";
    protected $fillable = [
        'name',
        'value',
        'company_id',
    ];

    /**
     * Key/value rows are unique by `name` only (see migrations). Company scoping would hide
     * existing rows (e.g. legacy NULL company_id) and make updateOrCreate() INSERT again,
     * triggering SQLSTATE[23000] duplicate key on settings_name_unique.
     */
    protected function shouldApplyCompanyScope(): bool
    {
        return false;
    }

    /**
     * Get a single menu label by key (stored value or default).
     */
    public static function getMenuLabel(string $key): string
    {
        $labels = self::getMenuLabels();
        return $labels[$key] ?? config('menu_labels.defaults.' . $key, $key);
    }

    /**
     * Get all menu labels (stored overrides merged with config defaults).
     * Cached per request to avoid repeated DB queries.
     */
    public static function getMenuLabels(): array
    {
        $cacheKey = self::menuLabelsCacheKey();

        return Cache::remember($cacheKey, 300, function () {
            $defaults = config('menu_labels.defaults', []);

            // In the tenant app, menu labels are per-company only (see Company::modules_settings).
            if (CompanyContext::shouldApplyScope() && CompanyContext::id() !== null) {
                return self::mergeCompanyLabelOverrides($defaults);
            }

            $stored = self::where('name', 'like', 'menu_label_%')
                ->pluck('value', 'name');
            $globalOverrides = [];
            foreach ($stored as $name => $value) {
                $globalOverrides[str_replace('menu_label_', '', $name)] = $value;
            }

            return array_merge($defaults, $globalOverrides);
        });
    }

    /**
     * Apply the current tenant's label_overrides on top of global defaults/settings.
     *
     * @param  array<string, string>  $labels
     * @return array<string, string>
     */
    public static function mergeCompanyLabelOverrides(array $labels): array
    {
        if (! CompanyContext::shouldApplyScope()) {
            return $labels;
        }

        $companyId = CompanyContext::id();
        if ($companyId === null) {
            return $labels;
        }

        $company = Company::query()->find($companyId);
        if (! $company || ! is_array($company->modules_settings)) {
            return $labels;
        }

        $overrides = $company->modules_settings['label_overrides'] ?? [];
        if ($overrides === []) {
            return $labels;
        }

        return array_merge($labels, array_filter(
            $overrides,
            static fn ($value): bool => $value !== null && $value !== ''
        ));
    }

    /**
     * Clear menu labels cache (call after saving labels in Settings).
     */
    public static function clearMenuLabelsCache(): void
    {
        Cache::forget(self::menuLabelsCacheKey());

        if (CompanyContext::shouldApplyScope()) {
            Cache::forget('erp_menu_labels');
        }
    }

    protected static function menuLabelsCacheKey(): string
    {
        if (! CompanyContext::shouldApplyScope()) {
            return 'erp_menu_labels';
        }

        $companyId = CompanyContext::id();

        return $companyId === null ? 'erp_menu_labels:none' : 'erp_menu_labels:' . $companyId;
    }
}
