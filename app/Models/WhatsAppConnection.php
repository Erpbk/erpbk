<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

/**
 * Platform WhatsApp Business phone numbers managed by app admin.
 * Assigned to a company (many per company allowed), or marked as the platform default.
 */
class WhatsAppConnection extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'whatsapp_connections';

    protected $fillable = [
        'company_id',
        'is_default',
        'label',
        'phone_number_id',
        'display_phone_number',
        'waba_id',
        'access_token',
        'status',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'whatsapp_connection_user', 'whatsapp_connection_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDefaultNumber($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDefaultNumber(): bool
    {
        return (bool) $this->is_default;
    }

    public function setAccessTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getDecryptedAccessToken(): ?string
    {
        $encrypted = $this->attributes['access_token'] ?? null;
        if (! is_string($encrypted) || trim($encrypted) === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function displayLabel(): string
    {
        $label = trim((string) ($this->label ?? ''));
        $phone = trim((string) ($this->display_phone_number ?: $this->phone_number_id));
        $base = $label !== '' ? "{$label} ({$phone})" : $phone;

        if ($this->isDefaultNumber()) {
            return $base.' — Default';
        }

        return $base;
    }

    public function assignmentLabel(): string
    {
        if ($this->isDefaultNumber()) {
            return 'Default number';
        }

        if ($this->company_id && $this->relationLoaded('company') && $this->company) {
            return $this->company->name;
        }

        if ($this->company_id) {
            return 'Company #'.$this->company_id;
        }

        return 'Unassigned';
    }
}
