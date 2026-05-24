<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mirror of central {@see Company} for the admin panel tables.
 * `id` matches `companies.id`. Email is unique: remove stale rows with same email before upserting by id.
 */
class AdminCompany extends BaseModel
{
    use SoftDeletes;

    protected $connection = 'mysql_admin';

    protected $table = 'admin_companies';

    /**
     * IDs mirror central `companies.id` (assigned explicitly), not an auto-increment sequence.
     */
    public $incrementing = false;

    protected $keyType = 'int';

    protected function shouldApplyCompanyScope(): bool
    {
        return false;
    }

    protected $fillable = [
        'id',
        'name',
        'email',
        'country',
        'phone',
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
        'modules_settings',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'is_taxpayer' => 'boolean',
        'approved_at' => 'datetime',
        'tax_registration_date' => 'date',
        'modules_settings' => 'array',
    ];

    /**
     * Upsert admin mirror row for a central company. Drops any other admin row with the same email
     * but a different id (out-of-sync after re-registration or ID shifts).
     */
    public static function syncFromCentralCompany(Company $company): void
    {
        $payload = [
            'name' => $company->name,
            'email' => $company->email,
            'country' => $company->country,
            'phone' => $company->phone,
            'status' => $company->status,
            'database_name' => $company->database_name,
            'city' => $company->city,
            'address' => $company->address,
            'is_taxpayer' => $company->is_taxpayer,
            'ntn_number' => $company->ntn_number,
            'tax_registration_date' => $company->tax_registration_date,
            'logo' => $company->logo,
            'primary_color' => $company->primary_color,
            'secondary_color' => $company->secondary_color,
            'modules_settings' => $company->modules_settings,
            'approved_at' => $company->approved_at,
            'approved_by' => $company->approved_by,
            'rejection_reason' => $company->rejection_reason,
        ];

        if (! empty($company->email)) {
            static::withTrashed()
                ->where('email', $company->email)
                ->where('id', '!=', $company->id)
                ->get()
                ->each
                ->forceDelete();
        }

        // updateOrCreate() is unreliable with $incrementing = false; it can INSERT duplicate PKs.
        $adminCompany = static::withTrashed()->find($company->id);

        if ($adminCompany) {
            if ($adminCompany->trashed()) {
                $adminCompany->restore();
            }
            $adminCompany->fill($payload);
            $adminCompany->save();

            return;
        }

        static::query()->create(array_merge(['id' => $company->id], $payload));
    }
}
