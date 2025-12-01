<?php

namespace App\Repositories;

use App\Models\Accounts;
use App\Repositories\BaseRepository;

class AccountsRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'opening_balance'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Accounts::class;
    }
}
