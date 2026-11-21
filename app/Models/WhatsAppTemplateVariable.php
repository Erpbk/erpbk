<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplateVariable extends Model
{
    protected $table = 'whatsapp_template_variables';

    protected $fillable = [
        'whatsapp_template_id',
        'component',
        'position',
        'parameter_name',
        'placeholder_key',
        'label',
        'sample_value',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'position' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'whatsapp_template_id');
    }
}
