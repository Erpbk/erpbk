<?php

namespace App\Repositories;

use App\Models\Banks;
use App\Repositories\BaseRepository;

class BanksRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'title',
        'account_no',
        'iban',
        'swift',
        'branch',
        'account_type',
        'balance',
        'status',
        'notes'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Banks::class;
    }
}
