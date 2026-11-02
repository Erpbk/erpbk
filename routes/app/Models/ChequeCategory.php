<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeCategory extends BaseModel
{
    protected $table = 'cheque_categories';

    protected $fillable = [
        'slug',
        'label',
        'display_order',
        'is_system',
        'company_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function customFields()
    {
        return $this->hasMany(ChequeCustomField::class, 'category_id', 'id');
    }

    /**
     * Default slug-to-labels for seeding (used by migration / fixed-field mapping).
     */
    public static function defaultSlugLabels(): array
    {
        return [
            'rider_info' => 'Rider Info',
            'visa_info' => 'Visa Info',
            'job_info' => 'Job Info',
            'labor_info' => 'Labor Info',
            'additional_info' => 'Additional Information',
            'other' => 'Other',
        ];
    }
}

