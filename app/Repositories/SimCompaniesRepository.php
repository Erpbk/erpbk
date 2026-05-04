<?php

namespace App\Repositories;

use App\Models\SimCompany;

class SimCompaniesRepository extends BaseRepository
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
        return SimCompany::class;
    }
}
