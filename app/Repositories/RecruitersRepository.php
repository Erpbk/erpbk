<?php

namespace App\Repositories;

use App\Models\Recruiters;
use App\Repositories\BaseRepository;

class RecruitersRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'email',
        'contact_number',
        'address',
        'tax_number',
        'status',
        'account_id',
        'created_by',
        'updated_by'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Recruiters::class;
    }
}
