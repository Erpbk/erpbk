<?php

namespace App\Repositories;

use App\Models\RiderEmails;
use App\Repositories\BaseRepository;

class RiderEmailsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'rider_id',
        'mail_to',
        'subject',
        'message',
        'status'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return RiderEmails::class;
    }
}
