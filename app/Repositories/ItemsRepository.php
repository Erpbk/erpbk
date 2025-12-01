<?php

namespace App\Repositories;

use App\Models\Items;
use App\Repositories\BaseRepository;

class ItemsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'detail',
        'price',
        'cost',
        'vat',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Items::class;
    }
}
