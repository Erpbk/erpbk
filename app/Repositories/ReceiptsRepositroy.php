<?php

namespace App\Repositories;

use App\Models\Receipt;
use App\Repositories\BaseRepository;

class ReceiptsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'transaction_number',
        'sender_id',
        'bank_id',
        'amount',
        'date_of_receipt',
        'billing_month',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Receipt::class;
    }
}
