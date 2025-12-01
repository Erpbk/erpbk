<?php

namespace App\Repositories;

use App\Models\Departments;
use App\Repositories\BaseRepository;

class DepartmentsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'detail',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Departments::class;
    }
}
