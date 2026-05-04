<?php

namespace App\Repositories;

use App\Models\BikeHistory;
use App\Repositories\BaseRepository;

class BikeHistoryRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'bike_id',
        'rider_id',
        'notes',
        'note_date',
        'warehouse',
        'contract'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return BikeHistory::class;
    }
}
