<?php

namespace App\Repositories;

use App\Models\legal_cases;
use App\Repositories\BaseRepository;

class LegalCasesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'trans_date',
        'trans_code',
        'date',
        'rider_id',
        'case_status',
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
        return legal_cases::class;
    }
}
