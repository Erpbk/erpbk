<?php

namespace App\Services\Module;

use App\Models\Company;
use App\Models\Settings;
use App\Support\CompanyContext;
use App\Support\ErpModuleRegistry;

class ModuleLabelService
{
    /**
     * Persist menu label(s) and clear caches so sidebar updates on next request.
     *
     * @param  list<string>  $keys
     */
    public function saveLabels(array $keys, string $label): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }

        $allKeys = [];
        foreach ($keys as $key) {
            foreach (ErpModuleRegistry::menuLabelAliases($key) as $aliasKey) {
                $allKeys[] = $aliasKey;
            }
        }
        $allKeys = array_values(array_unique($allKeys));
        $allKeys = array_values(array_filter(
            $allKeys,
            fn (string $key): bool => array_key_exists($key, config('menu_labels.defaults', []))
        ));

        if ($allKeys === []) {
            return;
        }

        if ($this->shouldSaveCompanyScoped()) {
            $this->saveCompanyLabelOverrides($allKeys, $label);
            Settings::clearMenuLabelsCache();

            return;
        }

        foreach ($allKeys as $key) {
            Settings::updateOrCreate(
                ['name' => 'menu_label_' . $key],
                ['value' => $label]
            );
        }

        Settings::clearMenuLabelsCache();
    }

    public function saveLabel(string $key, string $label): void
    {
        $this->saveLabels([$key], $label);
    }

    protected function shouldSaveCompanyScoped(): bool
    {
        return CompanyContext::shouldApplyScope() && CompanyContext::id() !== null;
    }

    /**
     * Per-company label overrides (used by the main app menu for that tenant only).
     *
     * @param  list<string>  $keys
     */
    protected function saveCompanyLabelOverrides(array $keys, string $label): void
    {
        $companyId = CompanyContext::id();
        if ($companyId === null) {
            return;
        }

        $company = Company::query()->find($companyId);
        if (!$company) {
            return;
        }

        $settings = is_array($company->modules_settings) ? $company->modules_settings : [];
        $overrides = $settings['label_overrides'] ?? [];

        foreach ($keys as $key) {
            $overrides[$key] = $label;
        }

        $settings['label_overrides'] = $overrides;
        $company->modules_settings = $settings;
        $company->save();
    }
}
