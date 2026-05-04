<?php

namespace App\Repositories;

use App\Models\FuelCompany;

class FuelCompaniesRepository extends BaseRepository
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
        return FuelCompany::class;
    }
}
