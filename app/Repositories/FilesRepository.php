<?php

namespace App\Repositories;

use App\Models\Files;
use App\Repositories\BaseRepository;

class FilesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'type',
        'type_id',
        'file_name',
        'expiry_date',
        'status',
        'notes',
        'file_type'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Files::class;
    }
}
