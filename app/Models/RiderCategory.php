<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderCategory extends Model
{
    protected $table = 'rider_categories';

    protected $fillable = [
        'slug',
        'label',
        'display_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function customFields()
    {
        return $this->hasMany(RiderCustomField::class, 'category_id', 'id');
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
