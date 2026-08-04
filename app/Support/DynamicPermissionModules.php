<?php

namespace App\Support;

/**
 * Root permission modules whose children are managed automatically from
 * Module Settings (Top Bars) and Rider Status settings — not via the
 * Permissions CRUD form's syncModuleTree.
 */
class DynamicPermissionModules
{
    public const TOP_BARS = 'Top Bars';

    public const RIDER_STATUSES = 'Rider Statuses';

    public const TOP_BAR_GROUP_PREFIX = 'Top Bar — ';

    public const RIDER_STATUS_GROUP_PREFIX = 'Rider Status — ';

    /**
     * @return list<string>
     */
    public static function reservedRootNames(): array
    {
        return [self::TOP_BARS, self::RIDER_STATUSES];
    }

    public static function isReservedRoot(?string $name): bool
    {
        $name = trim((string) $name);

        return $name !== '' && in_array($name, self::reservedRootNames(), true);
    }

    /**
     * Human label for role-permission UI (strip uniqueness prefixes stored in Spatie names).
     */
    public static function displayGroupLabel(string $rootName, string $groupName): string
    {
        if ($rootName === self::TOP_BARS && str_starts_with($groupName, self::TOP_BAR_GROUP_PREFIX)) {
            return substr($groupName, strlen(self::TOP_BAR_GROUP_PREFIX));
        }

        if ($rootName === self::RIDER_STATUSES && str_starts_with($groupName, self::RIDER_STATUS_GROUP_PREFIX)) {
            return substr($groupName, strlen(self::RIDER_STATUS_GROUP_PREFIX));
        }

        return $groupName;
    }
}
