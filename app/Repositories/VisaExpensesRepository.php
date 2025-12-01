<?php

namespace App\Repositories;

use App\Models\visa_expenses;
use App\Repositories\BaseRepository;

class VisaExpensesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'visa_status',
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
        return visa_expenses::class;
    }
}
