<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AgreementLetterhead extends BaseModel
{
    protected $table = 'agreement_letterheads';

    protected $fillable = [
        'company_id',
        'name',
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

        $publicPath = public_path('storage/' . $relative);
        if (! is_readable($publicPath)) {
            return null;
        }

        return asset('storage/' . $relative);
    }
}
