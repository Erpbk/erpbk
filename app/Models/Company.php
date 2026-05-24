<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company record for shared single-database tenancy.
 * Company owner credentials are stored here until approval.
 */
class Company extends BaseModel
{
    use SoftDeletes;

    protected $table = 'companies';

    /**
     * The companies table is not tenant-scoped (no company_id column).
     */
    protected function shouldApplyCompanyScope(): bool
    {
        return false;
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'country',
        'phone',
        'password',
        'status',
        'database_name',
        'city',
        'address',
        'is_taxpayer',
        'ntn_number',
        'tax_registration_date',
        'logo',
        'primary_color',
        'secondary_color',
        'branding_json',
        'modules_settings',
        'email_verified_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'tax_registration_date' => 'date',
        'password' => 'hashed',
        'is_taxpayer' => 'boolean',
        'modules_settings' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Generate a unique URL slug for company access URLs.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        if ($base === '') {
            $base = 'company';
        }

        $slug = $base;
        $i = 1;

        while (self::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
