<?php

namespace App\Repositories;

use App\Models\Permissions;
use App\Repositories\BaseRepository;

class PermissionsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'parent_id',
        'name',
        'guard_name'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Permissions::class;
    }
}
