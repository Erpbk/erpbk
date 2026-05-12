<?php

namespace App\Repositories;

use App\Models\BikeRegistration;

class BikeRegistrationsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'registration_status',
        'detail',
        'amount',
        'payment_status',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return BikeRegistration::class;
    }
}
