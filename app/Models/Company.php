<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company (tenant) record. Lives in central database only.
 * Each company has its own database; connection is switched at runtime.
 * Company owner credentials are stored here until approval; then first User is created in tenant DB.
 */
class Company extends BaseModel
{
    use SoftDeletes;

    protected $connection = 'mysql_central';

    protected $table = 'companies';

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
     * Generate unique database name for this company.
     */
    public static function generateDatabaseName(int $companyId): string
    {
        $prefix = env('DB_DATABASE_PREFIX', 'tenant');
        return $prefix . '_company_' . $companyId;
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
