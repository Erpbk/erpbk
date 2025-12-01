<?php

namespace App\Repositories;

use App\Models\Bikes;
use App\Repositories\BaseRepository;

class BikesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'plate',
        'chassis_number',
        'color',
        'model',
        'model_type',
        'engine',
        'company',
        'rider_id',
        'notes',
        'created_by',
        'updated_by',
        'warehouse',
        'traffic_file_number',
        'emirates',
        'bike_code',
        'registration_date',
        'expiry_date',
        'insurance_expiry',
        'insurance_co',
        'policy_no'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Bikes::class;
    }
}
