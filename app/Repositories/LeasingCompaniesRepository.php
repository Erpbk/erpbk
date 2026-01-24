<?php

namespace App\Repositories;

use App\Models\LeasingCompanies;
use App\Repositories\BaseRepository;

class LeasingCompaniesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'contact_person',
        'contact_number',
        'rental_amount',
        'detail',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return LeasingCompanies::class;
    }
}
