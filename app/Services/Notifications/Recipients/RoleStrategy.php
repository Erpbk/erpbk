<?php

namespace App\Services\Notifications\Recipients;

use App\Models\User;
use Illuminate\Support\Collection;

class RoleStrategy implements RecipientStrategyInterface
{
    public function __construct(private readonly string $role)
    {
    }

    public function resolve(int $companyId, array $context = []): Collection
    {
        return User::query()
            ->where('company_id', $companyId)
            ->role([$this->role])
            ->get();
    }
}
