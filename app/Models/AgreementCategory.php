<?php

namespace App\Models;

use App\Services\Agreements\AgreementLetterheadLayout;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AgreementCategory extends BaseModel
{
    protected $table = 'agreement_categories';

    protected $fillable = [
        'company_id',
        'group_key',
        'slug',
        'agreement_code',
        'name',
        'description',
        'letterhead_path',
        'letterhead_margins',
        'sort_order',
        'status',
        'assigned_modules',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
        'assigned_modules' => 'array',
        'letterhead_margins' => 'array',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(AgreementTemplate::class, 'category_id');
    }

    /**
     * Template selected in Agreement settings for generating this contract type.
     */
    public function defaultTemplate(): HasOne
    {
        return $this->hasOne(AgreementTemplate::class, 'category_id')
            ->where('is_default', true)
            ->where('status', true);
    }

    /**
     * Resolved contract template (settings-assigned default).
     */
    public function contractTemplate(): ?AgreementTemplate
    {
        if ($this->relationLoaded('defaultTemplate')) {
            return $this->defaultTemplate;
        }

        return $this->defaultTemplate()->first()
            ?? $this->templates()->where('status', true)->orderByDesc('is_default')->first();
    }

    public function hasLetterhead(): bool
    {
        return $this->letterhead_path !== null && $this->letterhead_path !== '';
    }

    public function letterheadFilesystemPath(): ?string
    {
        if (! $this->hasLetterhead()) {
            return null;
        }

        $relative = ltrim(preg_replace('#^storage/#', '', (string) $this->letterhead_path) ?? '', '/');
        $fullPath = storage_path('app/public/' . $relative);

        return is_readable($fullPath) ? $fullPath : null;
    }

    /**
     * @return array{top: float, bottom: float, left: float, right: float}
     */
    public function resolvedLetterheadMarginsMm(): array
    {
        return app(AgreementLetterheadLayout::class)->resolvedMarginsMm($this);
    }

    public function activeTemplates(): HasMany
    {
        return $this->templates()->where('status', true)->orderByDesc('is_default')->orderBy('template_name');
    }

    public function scopeAssignedToModule(Builder $query, string $moduleKey): Builder
    {
        if (! Schema::hasColumn($this->getTable(), 'assigned_modules')) {
            // Backward compatibility if the column doesn't exist yet.
            return $query;
        }

        return $query->whereJsonContains('assigned_modules', $moduleKey);
    }

    /**
     * Whether this agreement is assigned to a given ERP module key.
     */
    public function assignedToModule(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->normalizedAssignedModules(), true);
    }

    /**
     * @return list<string>
     */
    public function normalizedAssignedModules(): array
    {
        $modules = $this->assigned_modules;

        if (is_string($modules)) {
            $decoded = json_decode($modules, true);
            $modules = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($modules)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn($key) => is_string($key) ? trim($key) : '',
            $modules
        ))));
    }

    /**
     * Module keys that have at least one active agreement assigned.
     *
     * @return list<string>
     */
    public static function activeModuleKeysWithAgreements(): array
    {
        $keys = [];

        static::query()
            ->where('status', true)
            ->get(['assigned_modules'])
            ->each(function (self $category) use (&$keys): void {
                foreach ($category->normalizedAssignedModules() as $moduleKey) {
                    $keys[$moduleKey] = true;
                }
            });

        return array_keys($keys);
    }

    /**
     * Route model binding respects company scope (BelongsToCompany).
     */
    public function resolveRouteBinding($value, $field = null): self
    {
        $field = $field ?: $this->getRouteKeyName();

        return static::query()->where($field, $value)->firstOrFail();
    }

    /**
     * Ensure default categories exist for the current company from config.
     */
    public static function ensureDefaultsForCompany(?int $companyId = null): void
    {
        $companyId = $companyId ?? \App\Support\CompanyContext::id();
        if (!$companyId) {
            return;
        }

        $groups = config('agreement_categories.groups', []);
        $order = 0;
        foreach ($groups as $groupKey => $group) {
            foreach ($group['categories'] ?? [] as $cat) {
                $slug = $cat['slug'] ?? null;
                if (!$slug) {
                    continue;
                }
                $assignedModules = $cat['assigned_modules'] ?? [];
                $agreementCode = $cat['agreement_code'] ?? strtoupper((string) $slug);

                $categoryQuery = static::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('slug', $slug);

                $category = $categoryQuery->first();

                $categoryPayload = [
                    'group_key' => $groupKey,
                    'agreement_code' => $agreementCode,
                    'name' => $cat['name'] ?? $slug,
                    'description' => $cat['description'] ?? null,
                    'assigned_modules' => $assignedModules,
                ];

                if (! $category) {
                    $category = static::withoutGlobalScopes()->create(array_merge($categoryPayload, [
                        'company_id' => $companyId,
                        'slug' => $slug,
                        'sort_order' => $order++,
                        'status' => true,
                    ]));
                } else {
                    // Existing categories: only ensure group_key; never overwrite admin-edited
                    // fields (name, description, assigned_modules, agreement_code).
                    if ($category->group_key !== $groupKey) {
                        $category->group_key = $groupKey;
                        $category->save();
                    }

                    // Backfill module assignment only when never configured.
                    $existingModules = $category->assigned_modules;
                    if ((! is_array($existingModules) || $existingModules === []) && $assignedModules !== []) {
                        $category->assigned_modules = $assignedModules;
                        $category->save();
                    }
                }

                $defaultContent = '';
                $view = $cat['default_content_file'] ?? null;
                if ($view && view()->exists($view)) {
                    $defaultContent = (string) view($view)->render();
                }

                // Seed BOTH professional styles so each agreement has 2 templates.
                $baseName = (string) ($cat['default_template_name'] ?? 'Default Template');

                $templatesBaseQuery = AgreementTemplate::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('category_id', $category->id);

                $hasAnyDefault = $templatesBaseQuery->clone()->where('is_default', true)->exists();

                $corporateExists = $templatesBaseQuery->clone()
                    ->where('template_type', AgreementTemplate::TYPE_CORPORATE)
                    ->exists();

                if (! $corporateExists) {
                    AgreementTemplate::withoutGlobalScopes()->create([
                        'company_id' => $companyId,
                        'category_id' => $category->id,
                        'template_name' => $baseName . ' (Corporate Professional)',
                        'template_type' => AgreementTemplate::TYPE_CORPORATE,
                        'description' => $defaultContent,
                        'is_default' => ! $hasAnyDefault,
                        'status' => true,
                    ]);

                    if (! $hasAnyDefault) {
                        // Ensure only one default template exists.
                        AgreementTemplate::withoutGlobalScopes()
                            ->where('company_id', $companyId)
                            ->where('category_id', $category->id)
                            ->where('template_type', '!=', AgreementTemplate::TYPE_CORPORATE)
                            ->update(['is_default' => false]);
                    }
                }

                $premiumExists = $templatesBaseQuery->clone()
                    ->where('template_type', AgreementTemplate::TYPE_PREMIUM)
                    ->exists();

                if (! $premiumExists) {
                    AgreementTemplate::withoutGlobalScopes()->create([
                        'company_id' => $companyId,
                        'category_id' => $category->id,
                        'template_name' => $baseName . ' (Modern Premium)',
                        'template_type' => AgreementTemplate::TYPE_PREMIUM,
                        'description' => $defaultContent,
                        'is_default' => false,
                        'status' => true,
                    ]);
                }

                // If the category exists but no default template is set,
                // pick the corporate style as default (or the first template).
                if (! $templatesBaseQuery->clone()->where('is_default', true)->exists()) {
                    $fallback = $templatesBaseQuery
                        ->clone()
                        ->where('template_type', AgreementTemplate::TYPE_CORPORATE)
                        ->first();

                    $fallback = $fallback ?: $templatesBaseQuery->clone()->first();
                    if ($fallback) {
                        $fallback->setAsDefault();
                    }
                }
            }
        }
    }
}
