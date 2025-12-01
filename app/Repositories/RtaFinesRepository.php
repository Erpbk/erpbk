<?php

namespace App\Repositories;

use App\Models\RtaFines;
use App\Repositories\BaseRepository;

class RtaFinesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'trans_date',
        'trans_code',
        'trip_date',
        'trip_time',
        'rider_id',
        'billing_month',
        'ticket_no',
        'bike_id',
        'plate_no',
        'detail',
        'amount',
        'service_charges',
        'admin_fee',
        'total_amount',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return RtaFines::class;
    }
}
