<?php

namespace App\Repositories;

use App\Models\Riders;
use App\Repositories\BaseRepository;

class RidersRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'name',
        'rider_id',
        'personal_contact',
        'company_contact',
        'personal_email',
        'email',
        'nationality',
        'NFDID',
        'doj',
        'emirate_hub',
        'emirate_id',
        'emirate_exp',
        'mashreq_id',
        'passport',
        'passport_expiry',
        'PID',
        'DEPT',
        'ethnicity',
        'dob',
        'license_no',
        'license_expiry',
        'visa_status',
        'branded_plate_no',
        'vaccine_status',
        'attach_documents',
        'other_details',
        'created_by',
        'updated_by',
        'VID',
        'visa_sponsor',
        'visa_occupation',
        'status',
        'TAID',
        'fleet_supervisor',
        'passport_handover',
        'noon_no',
        'wps',
        'c3_card',
        'contract',
        'designation',
        'image_name',
        'salary_model',
        'rider_reference',
        'job_status',
        'person_code',
        'labor_card_number',
        'labor_card_expiry',
        'insurance',
        'insurance_expiry',
        'policy_no',
        'recuriter'
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
