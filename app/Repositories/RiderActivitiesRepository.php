<?php

namespace App\Repositories;

use App\Models\RiderActivities;
use App\Repositories\BaseRepository;

class RiderActivitiesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'rider_id',
        'd_rider_id',
        'payout_type',
        'delivered_orders',
        'ontime_orders',
        'ontime_orders_percentage',
        'avg_time',
        'rejected_orders',
        'rejected_orders_percentage',
        'login_hr',
        'date'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return RiderActivities::class;
    }
}
