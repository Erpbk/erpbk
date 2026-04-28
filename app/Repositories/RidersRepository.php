<?php

namespace App\Repositories;

use App\Models\Riders;
use App\Repositories\BaseRepository;

class RidersRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'rider_id',
        'email',
        'nationality',
        'doj',
        'emirate_id',
        'emirate_exp',
        'passport',
        'passport_expiry',
        'ethnicity',
        'dob',
        'license_no',
        'license_expiry',
        'created_by',
        'updated_by',
        'status',
        'fleet_supervisor',
        'wps',
        'image_name',
        'person_code',
        'labor_card_number',
        'labor_card_expiry',
        'recuriter',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Riders::class;
    }

    public function getRiderWithItemsRelations($id)
    {
        return Riders::with('items')->find($id);
    }
}
