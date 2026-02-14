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
        'trn_number',
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
