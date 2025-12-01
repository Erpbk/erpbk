<?php

namespace App\Repositories;

use App\Models\Garages;
use App\Repositories\BaseRepository;

class GaragesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'contact_person',
        'address',
        'contact_number',
        'detail',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Garages::class;
    }
}
