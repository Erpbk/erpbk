<?php

namespace App\Models;

use App\Support\PublicStorageDisk;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementLetterhead extends BaseModel
{
    public const KIND_LETTERHEAD = 'letterhead';
    public const KIND_WATERMARK = 'watermark';

    protected $table = 'agreement_letterheads';

    protected $fillable = [
        'company_id',
        'name',
        'kind',
        'path',
        'original_name',
        'suggested_margins',
    ];

    protected $casts = [
        'suggested_margins' => 'array',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(AgreementCategory::class, 'letterhead_id');
    }

    public function watermarkCategories(): HasMany
    {
        return $this->hasMany(AgreementCategory::class, 'watermark_id');
    }

    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public function isWatermark(): bool
    {
        return $this->kind === self::KIND_WATERMARK;
    }

    public function relativePath(): string
    {
        return ltrim(preg_replace('#^storage/#', '', (string) $this->path) ?? '', '/');
    }

    public function filesystemPath(): ?string
    {
        $relative = $this->relativePath();
        if ($relative === '') {
            return null;
        }

        $fullPath = storage_path('app/public/' . $relative);

        return is_readable($fullPath) ? $fullPath : null;
    }

    public function publicUrl(): ?string
    {
        $relative = $this->relativePath();
        if ($relative === '') {
            return null;
        }

        if (PublicStorageDisk::isCloud()) {
            return PublicStorageDisk::url($relative);
        }

        if (! PublicStorageDisk::exists($relative)) {
            return null;
        }

        // Root-relative so local (127.0.0.1) and live both hit FileController,
        // even when APP_URL points at the production domain.
        return '/storage/' . ltrim($relative, '/');
    }
}
