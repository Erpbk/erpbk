<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global placeholder registry (not company-scoped).
 */
class AgreementPlaceholder extends Model
{
    protected $table = 'agreement_placeholders';

    protected $fillable = [
        'placeholder',
        'description',
        'group_label',
        'sort_order',
    ];

    public static function grouped(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_label')
            ->all();
    }
}
