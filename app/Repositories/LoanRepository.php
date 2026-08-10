<?php

namespace App\Repositories;

use App\Models\Loan;

class LoanRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'loan_number',
        'agreement_ref',
        'status',
        'bank_name',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Loan::class;
    }
}
