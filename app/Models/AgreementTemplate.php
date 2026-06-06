<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementTemplate extends BaseModel
{
    public const TYPE_CORPORATE = 'corporate';

    public const TYPE_PREMIUM = 'premium';

    public const TYPES = [
        self::TYPE_CORPORATE => 'Style 1 – Corporate',
        self::TYPE_PREMIUM => 'Style 2 – Premium Modern',
    ];

    protected $table = 'agreement_templates';

    protected $fillable = [
        'company_id',
        'category_id',
        'template_name',
        'template_type',
        'description',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AgreementCategory::class, 'category_id');
    }

    /**
     * System sample styles (Corporate + Premium) used for module customization.
     */
    public function scopeSampleStyles(Builder $query): Builder
    {
        return $query->whereIn('template_type', [self::TYPE_CORPORATE, self::TYPE_PREMIUM]);
    }

    public function setAsDefault(): void
    {
        static::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    public function duplicate(string $newName): self
    {
        $copy = $this->replicate(['is_default']);
        $copy->template_name = $newName;
        $copy->is_default = false;
        $copy->save();

        return $copy;
    }
}
