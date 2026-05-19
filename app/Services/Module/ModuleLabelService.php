<?php

namespace App\Services\Module;

use App\Models\Company;
use App\Models\Settings;
use App\Support\ErpModuleRegistry;
use Illuminate\Support\Facades\Auth;

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

        foreach ($allKeys as $key) {
            if (!array_key_exists($key, config('menu_labels.defaults', []))) {
                continue;
            }
            Settings::updateOrCreate(
                ['name' => 'menu_label_' . $key],
                ['value' => $label]
            );
        }

        Settings::clearMenuLabelsCache();
        $this->syncCompanyLabelOverrides($allKeys, $label);
    }

    public function saveLabel(string $key, string $label): void
    {
        $this->saveLabels([$key], $label);
    }

    /**
     * Keep company-level label_overrides in sync (they override Settings in the menu composer).
     *
     * @param  list<string>  $keys
     */
    protected function syncCompanyLabelOverrides(array $keys, string $label): void
    {
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            return;
        }

        $company = Company::query()->find($user->company_id);
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
