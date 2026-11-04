<?php

namespace App\Services\Notifications\Recipients;

use App\Models\User;
use Illuminate\Support\Collection;

class ExplicitUsersStrategy implements RecipientStrategyInterface
{
    /** @param  list<int>  $userIds */
    public function __construct(private readonly array $userIds)
    {
    }

    public function resolve(int $companyId, array $context = []): Collection
    {
        if ($this->userIds === []) {
            return collect();
        }

        return User::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $this->userIds)
            ->get();
    }
}
