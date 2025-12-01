<?php

namespace App\Repositories;

use App\Models\RiderAttendance;
use App\Repositories\BaseRepository;

class RiderAttendanceRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'rider_id',
        'd_rider_id',
        'date',
        'shift',
        'attendance',
        'cdm_id',
        'day'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return RiderAttendance::class;
    }
}
