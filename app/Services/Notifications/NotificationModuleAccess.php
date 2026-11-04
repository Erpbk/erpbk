<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Support\RoleFieldAccess;

class NotificationModuleAccess
{
    public function __construct(private readonly NotificationTypeRegistry $registry)
    {
    }

    /**
     * Types grouped by module, limited to modules the user can access.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function typesByModuleFor(User $user): array
    {
        $out = [];
        foreach ($this->registry->groupedByModule() as $moduleKey => $moduleTypes) {
            if (! $this->userCanAccessModuleKey($user, (string) $moduleKey, is_array($moduleTypes) ? $moduleTypes : [])) {
                continue;
            }
            $out[$moduleKey] = $moduleTypes;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function typeKeysFor(User $user): array
    {
        $keys = [];
        foreach ($this->typesByModuleFor($user) as $moduleTypes) {
            foreach (array_keys($moduleTypes) as $typeKey) {
                $keys[] = (string) $typeKey;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, array<string, mixed>>  $moduleTypes
     */
    public function userCanAccessModuleKey(User $user, string $moduleKey, array $moduleTypes = []): bool
    {
        try {
            if ($user->isAdmin()) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        $entityKey = RoleFieldAccess::entityKeyFromModuleKey($moduleKey);
        if ($entityKey !== null) {
            return RoleFieldAccess::userCanAccessModule($entityKey, $user);
        }

        if ($moduleTypes === []) {
            $grouped = $this->registry->groupedByModule();
            $moduleTypes = is_array($grouped[$moduleKey] ?? null) ? $grouped[$moduleKey] : [];
        }

        $roles = [];
        $permissions = [];
        foreach ($moduleTypes as $typeDef) {
            $strategies = $typeDef['default_recipient_config']['strategies'] ?? [];
            if (! is_array($strategies)) {
                continue;
            }
            foreach ($strategies as $strategy) {
                $strategy = (string) $strategy;
                if (str_starts_with($strategy, 'role:')) {
                    $roles[] = substr($strategy, 5);
                } elseif (str_starts_with($strategy, 'permission:')) {
                    $permissions[] = substr($strategy, 11);
                }
            }
        }
        $roles = array_values(array_unique(array_filter($roles)));
        $permissions = array_values(array_unique(array_filter($permissions)));

        // No module tree and no strategies → don't expose the card.
        if ($roles === [] && $permissions === []) {
            return false;
        }

        if ($roles !== []) {
            try {
                if ($user->hasAnyRole($roles)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        foreach ($permissions as $permission) {
            if (RoleFieldAccess::userCan($permission, $user)) {
                return true;
            }
        }

        return false;
    }
}
