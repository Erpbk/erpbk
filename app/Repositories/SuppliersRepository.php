<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Repositories\BaseRepository;

class SuppliersRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'email',
        'phone',
        'address',
        'company_name',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Supplier::class;
    }
}
