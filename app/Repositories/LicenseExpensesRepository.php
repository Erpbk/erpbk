<?php

namespace App\Repositories;

use App\Models\license_expenses;
use App\Repositories\BaseRepository;

class LicenseExpensesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'license_status',
        'detail',
        'amount',
        'payment_status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return license_expenses::class;
    }
}
