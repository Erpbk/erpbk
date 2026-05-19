<?php

namespace App\Services\Module;

use App\Models\ModuleCustomField;
use App\Models\ModuleFieldCategoryAssignment;
use App\Models\ModuleSettingCategory;
use App\Models\Settings;
use App\Support\CompanyContext;
use App\Support\ErpModuleRegistry;
use App\Support\ModuleFieldSource;
use Illuminate\Support\Facades\Schema;

final class ModuleDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    /**
     * Module keys that use the shared module settings UI (field assignments + categories).
     *
     * @return list<string>
     */
    public function settingsModuleKeys(): array
    {
        $keys = array_keys(ModuleFieldSource::sourceTableMap());
        $keys = array_merge($keys, array_values(config('module_settings_sync.table_identifier_to_module', [])));
        $keys = array_merge($keys, array_keys(config('menu_labels.defaults', [])));

        return collect($keys)
            ->map(fn (string $key) => ErpModuleRegistry::settingsFieldsModuleKey($key))
            ->unique()
            ->reject(fn (string $key) => in_array($key, [
                'dashboard',
                'recycle_bin',
                'rider_settings',
                'employee_settings',
                'cheques_settings',
                'bike_settings',
                'bike_list',
            ], true))
            ->values()
            ->all();
    }

    /**
     * Ensure the default "General" category exists for a module (per company when scoped).
     */
    public function ensureForModule(string $module, ?int $companyId = null): ModuleSettingCategory
    {
        $module = ErpModuleRegistry::settingsFieldsModuleKey($module);
        $companyId = $companyId ?? $this->resolveCompanyId();

        $query = ModuleSettingCategory::query()
            ->where('module_key', $module)
            ->where('slug', self::DEFAULT_SLUG);

        if ($companyId !== null && $this->categoriesAreCompanyScoped()) {
            $query->where('company_id', $companyId);
        }

        $category = $query->first();
        if ($category) {
            if (trim((string) $category->label) === '') {
                $category->label = $this->labelForModule($module);
                $category->save();
            }

            return $category;
        }

        $payload = [
            'module_key' => $module,
            'slug' => self::DEFAULT_SLUG,
            'label' => $this->labelForModule($module),
            'display_order' => 0,
            'is_system' => true,
        ];

        if ($companyId !== null && $this->categoriesAreCompanyScoped()) {
            $payload['company_id'] = $companyId;
        }

        return ModuleSettingCategory::create($payload);
    }

    /**
     * Assign every field assignment and custom field without a category to the default category.
     */
    public function assignAllFieldsToDefault(string $module, ?ModuleSettingCategory $category = null): void
    {
        $module = ErpModuleRegistry::settingsFieldsModuleKey($module);
        $category = $category ?? $this->ensureForModule($module);
        $categoryId = (int) $category->id;

        $assignmentQuery = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $module)
            ->whereNull('category_id');

        if ($category->company_id !== null) {
            $assignmentQuery->where('company_id', $category->company_id);
        }

        $assignmentQuery->update(['category_id' => $categoryId]);

        if (Schema::hasTable('module_custom_fields')) {
            $customQuery = ModuleCustomField::query()
                ->where('module_key', $module)
                ->whereNull('category_id');

            if ($category->company_id !== null) {
                $customQuery->where('company_id', $category->company_id);
            }

            $customQuery->update(['category_id' => $categoryId]);
        }
    }

    /**
     * Bootstrap default category + assignments for every module settings module.
     */
    public function bootstrapAllModules(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        $companyIds = \Illuminate\Support\Facades\DB::table('companies')->orderBy('id')->pluck('id');

        foreach ($companyIds as $companyId) {
            foreach ($this->settingsModuleKeys() as $module) {
                $category = $this->ensureForModule($module, (int) $companyId);
                $this->assignAllFieldsToDefault($module, $category);
            }
        }
    }

    public function isDefaultCategory(ModuleSettingCategory $category): bool
    {
        return (string) $category->slug === self::DEFAULT_SLUG && (bool) $category->is_system;
    }

    protected function labelForModule(string $module): string
    {
        $menuLabel = Settings::getMenuLabel($module);
        if ($menuLabel !== '' && strcasecmp($menuLabel, $module) !== 0) {
            return $menuLabel;
        }

        return self::DEFAULT_LABEL;
    }

    protected function categoriesAreCompanyScoped(): bool
    {
        return Schema::hasTable('module_setting_categories')
            && Schema::hasColumn('module_setting_categories', 'company_id');
    }

    protected function resolveCompanyId(): ?int
    {
        $id = CompanyContext::id();

        return $id !== null && $id > 0 ? $id : null;
    }
}
