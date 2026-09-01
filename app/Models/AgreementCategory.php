<?php

namespace App\Models;

use App\Services\Agreements\AgreementLetterheadLayout;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'letterhead_id',
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

    public function letterhead(): BelongsTo
    {
        return $this->belongsTo(AgreementLetterhead::class, 'letterhead_id');
    }

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
        return $this->letterheadRelativePath() !== null;
    }

    public function letterheadRelativePath(): ?string
    {
        if ($this->letterhead_id) {
            $path = $this->letterhead?->relativePath();
            if ($path) {
                return $path;
            }
        }

        $legacy = ltrim(preg_replace('#^storage/#', '', (string) $this->letterhead_path) ?? '', '/');

        return $legacy !== '' ? $legacy : null;
    }

    public function letterheadFilesystemPath(): ?string
    {
        if ($this->letterhead_id && $this->letterhead) {
            return $this->letterhead->filesystemPath();
        }

        $relative = $this->letterheadRelativePath();
        if ($relative === null) {
            return null;
        }

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

                $baseName = (string) ($cat['default_template_name'] ?? 'Default Template');

                $templatesBaseQuery = AgreementTemplate::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('category_id', $category->id);

                if (! $templatesBaseQuery->clone()->exists()) {
                    AgreementTemplate::withoutGlobalScopes()->create([
                        'company_id' => $companyId,
                        'category_id' => $category->id,
                        'template_name' => $baseName,
                        'template_type' => AgreementTemplate::TYPE_STANDARD,
                        'description' => $defaultContent,
                        'is_default' => true,
                        'status' => true,
                    ]);
                } elseif (! $templatesBaseQuery->clone()->where('is_default', true)->exists()) {
                    $fallback = $templatesBaseQuery->clone()->orderBy('id')->first();
                    if ($fallback) {
                        $fallback->setAsDefault();
                    }
                }
            }
        }
    }
}
