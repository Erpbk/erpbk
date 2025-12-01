<?php

namespace App\Repositories;

use App\Models\Customers;
use App\Repositories\BaseRepository;

class CustomersRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'company_name',
        'company_email',
        'contact_number',
        'address',
        'tax_number',
        'status',
        'tax_percentage'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Customers::class;
    }
}
