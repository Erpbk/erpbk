<?php

namespace App\Repositories;

use App\Models\BikeRentCompany;

class BikeRentCompaniesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'company_contact',
        'email',
        'address',
        'status',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return BikeRentCompany::class;
    }
}
