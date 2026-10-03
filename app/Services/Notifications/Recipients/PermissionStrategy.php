<?php

namespace App\Services\Notifications\Recipients;

use App\Models\User;
use App\Support\RoleFieldAccess;
use Illuminate\Support\Collection;

class PermissionStrategy implements RecipientStrategyInterface
{
    public function __construct(private readonly string $permission)
    {
    }

    public function resolve(int $companyId, array $context = []): Collection
    {
        $users = User::query()->where('company_id', $companyId)->get();

        return $users->filter(function (User $user) {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return $user->can($this->permission)
                || RoleFieldAccess::userCan($this->permission, $user);
        })->values();
    }
}
