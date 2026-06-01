<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementCategory extends BaseModel
{
    protected $table = 'agreement_categories';

    protected $fillable = [
        'company_id',
        'group_key',
        'slug',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(AgreementTemplate::class, 'category_id');
    }

    public function activeTemplates(): HasMany
    {
        return $this->templates()->where('status', true)->orderByDesc('is_default')->orderBy('template_name');
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
                $existing = static::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('slug', $slug)
                    ->first();

                if ($existing) {
                    continue;
                }

                $category = static::withoutGlobalScopes()->create([
                    'company_id' => $companyId,
                    'group_key' => $groupKey,
                    'slug' => $slug,
                    'name' => $cat['name'] ?? $slug,
                    'sort_order' => $order++,
                    'status' => true,
                ]);

                $defaultContent = '';
                $view = $cat['default_content_file'] ?? null;
                if ($view && view()->exists($view)) {
                    $defaultContent = view($view)->render();
                }

                AgreementTemplate::withoutGlobalScopes()->create([
                    'company_id' => $companyId,
                    'category_id' => $category->id,
                    'template_name' => $cat['default_template_name'] ?? 'Default Template',
                    'template_type' => AgreementTemplate::TYPE_CORPORATE,
                    'description' => $defaultContent,
                    'is_default' => true,
                    'status' => true,
                ]);
            }
        }
    }
}
