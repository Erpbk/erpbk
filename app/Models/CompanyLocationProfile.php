<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLocationProfile extends BaseModel
{
    use SoftDeletes;

    protected $table = 'company_location_profiles';

    public const TYPE_HEAD_OFFICE = 'ho';
    public const TYPE_BRANCH = 'br';
    public const TYPE_SEPARATE = 'co';

    protected $fillable = [
        'company_id',
        'branch_id',
        'parent_id',
        'entity_type',
        'code',
        'label',
        'place',
        'vals',
        'inh',
        'banks',
        'use_ho_bank',
        'field_orders',
        'docs_meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vals' => 'array',
        'inh' => 'array',
        'banks' => 'array',
        'use_ho_bank' => 'boolean',
        'field_orders' => 'array',
        'docs_meta' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isHeadOffice(): bool
    {
        return $this->entity_type === self::TYPE_HEAD_OFFICE;
    }

    public function isBranch(): bool
    {
        return $this->entity_type === self::TYPE_BRANCH;
    }

    public function isSeparateCompany(): bool
    {
        return $this->entity_type === self::TYPE_SEPARATE;
    }
}
