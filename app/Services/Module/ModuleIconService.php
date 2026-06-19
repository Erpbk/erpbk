<?php

namespace App\Services\Module;

use App\Models\Company;
use App\Models\Settings;
use App\Support\CompanyContext;
use App\Support\ErpModuleRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleIconService
{
    /**
     * @return array{type: string, class: string, url: string|null, path: string|null}
     */
    public function resolve(string $menuKey): array
    {
        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);
        $defaults = config('menu_icons.defaults', []);
        $defaultClass = $defaults[$menuKey] ?? 'ti-adjustments-alt';
        $stored = $this->getStoredOverrides();

        if (! isset($stored[$menuKey]) || ! is_array($stored[$menuKey])) {
            return $this->classIcon($defaultClass);
        }

        $entry = $stored[$menuKey];
        $type = (string) ($entry['type'] ?? 'class');

        if ($type === 'image') {
            $path = (string) ($entry['path'] ?? '');
            if ($path !== '' && Storage::disk('public')->exists($path)) {
                return [
                    'type' => 'image',
                    'class' => $defaultClass,
                    'url' => Storage::disk('public')->url($path),
                    'path' => $path,
                ];
            }
        }

        $class = (string) ($entry['class'] ?? $defaultClass);

        return $this->classIcon($this->normalizeClass($class) ?: $defaultClass);
    }

    public function saveClassIcon(string $menuKey, string $class): void
    {
        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);
        $class = $this->normalizeClass($class);
        if ($class === '') {
            return;
        }

        $this->deleteImageFile($menuKey);
        $this->persistOverride($menuKey, [
            'type' => 'class',
            'class' => $class,
        ]);
    }

    public function saveImageIcon(string $menuKey, UploadedFile $file): void
    {
        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);
        $companyId = CompanyContext::id();
        if ($companyId === null) {
            return;
        }

        $this->deleteImageFile($menuKey);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = $menuKey . '_' . Str::random(8) . '.' . $ext;
        $directory = 'module-icons/' . $companyId;
        $path = $file->storeAs($directory, $filename, 'public');

        $this->persistOverride($menuKey, [
            'type' => 'image',
            'path' => $path,
        ]);
    }

    public function removeIcon(string $menuKey): void
    {
        $menuKey = ErpModuleRegistry::normalizeKey($menuKey);
        $this->deleteImageFile($menuKey);

        if ($this->shouldSaveCompanyScoped()) {
            $overrides = $this->getStoredOverrides();
            unset($overrides[$menuKey]);
            $this->writeOverrides($overrides);
        } else {
            Settings::query()->where('name', 'menu_icon_' . $menuKey)->delete();
        }

        Settings::clearMenuIconsCache();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function persistOverride(string $menuKey, array $payload): void
    {
        $overrides = $this->getStoredOverrides();
        $overrides[$menuKey] = $payload;
        $this->writeOverrides($overrides);
        Settings::clearMenuIconsCache();
    }

    protected function deleteImageFile(string $menuKey): void
    {
        $overrides = $this->getStoredOverrides();
        $existing = $overrides[$menuKey] ?? null;
        if (! is_array($existing) || ($existing['type'] ?? '') !== 'image') {
            return;
        }
        $path = (string) ($existing['path'] ?? '');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getStoredOverrides(): array
    {
        if ($this->shouldSaveCompanyScoped()) {
            $companyId = CompanyContext::id();
            if ($companyId === null) {
                return [];
            }
            $company = Company::query()->find($companyId);
            if (! $company || ! is_array($company->modules_settings)) {
                return [];
            }

            $overrides = $company->modules_settings['icon_overrides'] ?? [];

            return is_array($overrides) ? $overrides : [];
        }

        $stored = Settings::query()
            ->where('name', 'like', 'menu_icon_%')
            ->pluck('value', 'name');

        $result = [];
        foreach ($stored as $name => $value) {
            $key = str_replace('menu_icon_', '', (string) $name);
            $decoded = json_decode((string) $value, true);
            if (is_array($decoded)) {
                $result[$key] = $decoded;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     */
    protected function writeOverrides(array $overrides): void
    {
        if ($this->shouldSaveCompanyScoped()) {
            $companyId = CompanyContext::id();
            if ($companyId === null) {
                return;
            }
            $company = Company::query()->find($companyId);
            if (! $company) {
                return;
            }
            $settings = is_array($company->modules_settings) ? $company->modules_settings : [];
            $settings['icon_overrides'] = $overrides;
            $company->modules_settings = $settings;
            $company->save();

            return;
        }

        foreach ($overrides as $key => $payload) {
            Settings::updateOrCreate(
                ['name' => 'menu_icon_' . $key],
                ['value' => json_encode($payload)]
            );
        }
    }

    protected function shouldSaveCompanyScoped(): bool
    {
        return CompanyContext::shouldApplyScope() && CompanyContext::id() !== null;
    }

    /**
     * @return array{type: string, class: string, url: string|null, path: string|null}
     */
    protected function classIcon(string $class): array
    {
        return [
            'type' => 'class',
            'class' => $this->normalizeClass($class),
            'url' => null,
            'path' => null,
        ];
    }

    protected function normalizeClass(string $class): string
    {
        $class = trim($class);
        if ($class === '') {
            return '';
        }
        if (! str_starts_with($class, 'ti-')) {
            $class = 'ti-' . ltrim($class, 'ti');
        }

        if ($class === 'ti-passport') {
            $class = 'ti-e-passport';
        }

        return preg_match('/^ti-[a-z0-9-]+$/', $class) ? $class : '';
    }
}
