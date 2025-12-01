<?php
// ... existing code ...
namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\BaseRepository;

class PaymentsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'bank_id',
        'account_type',
        'head_account_id',
        'account_id',
        'amount',
        'date_of_invoice',
        'date_of_payment',
        'billing_month',
        'voucher_no',
        'voucher_type',
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
        return Payment::class;
    }
}
// ... existing code ...
