<?php

namespace App\Support\WhatsApp;

use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\CompanyModuleVisibility;

class WhatsAppCompanyAccess
{
    public static function channelEnabled(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        return CompanyNotificationChannels::isEnabled(
            $companyId,
            CompanyNotificationChannels::CHANNEL_WHATSAPP
        );
    }

    public static function hasAssignedNumbers(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        return app(WhatsAppConnectionService::class)
            ->getActiveCompanyConnections($companyId)
            ->isNotEmpty();
    }

    /**
     * Tenant inbox / settings / role module: channel on + at least one assigned company number.
     */
    public static function available(int $companyId): bool
    {
        return self::channelEnabled($companyId) && self::hasAssignedNumbers($companyId);
    }

    public static function menuVisible(int $companyId): bool
    {
        return CompanyModuleVisibility::enabled('whatsapp') && self::available($companyId);
    }
}
