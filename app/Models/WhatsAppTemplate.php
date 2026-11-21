<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppTemplate extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const PARAM_POSITIONAL = 'positional';

    public const PARAM_NAMED = 'named';

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'label',
        'name',
        'language',
        'category',
        'param_style',
        'status',
        'body_preview',
    ];

    public function variables(): HasMany
    {
        return $this->hasMany(WhatsAppTemplateVariable::class, 'whatsapp_template_id')
            ->orderBy('component')
            ->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function usesNamedParams(): bool
    {
        return $this->param_style === self::PARAM_NAMED;
    }

    public function displayLabel(): string
    {
        $label = trim((string) ($this->label ?? ''));
        $name = trim((string) $this->name);
        $lang = trim((string) ($this->language ?: 'en'));

        if ($label !== '') {
            return "{$label} ({$name} / {$lang})";
        }

        return "{$name} / {$lang}";
    }
}
