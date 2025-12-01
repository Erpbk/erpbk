<?php

namespace App\Repositories;

use App\Models\Sims;
use App\Repositories\BaseRepository;

class SimsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'number',
        'company',
        'assign_to',
        'created_by',
        'updated_by',
        'fleet_supervisor',
        'status',
        'emi',
        'vendor'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Sims::class;
    }
}
