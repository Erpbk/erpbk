<?php

namespace App\Services\Notifications;

use App\Models\Company;

class CompanyNotificationChannels
{
    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL = 'email';

    /** @return list<string> */
    public static function allChannels(): array
    {
        return array_values(config('notification_types.channels', [self::CHANNEL_IN_APP, self::CHANNEL_EMAIL]));
    }

    /** @return list<string> */
    public static function defaultEnabled(): array
    {
        return array_values(config('notification_types.default_enabled_channels', [self::CHANNEL_IN_APP, self::CHANNEL_EMAIL]));
    }

    /** @return list<string> */
    public static function enabledFromSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        $enabled = $settings['enabled'] ?? null;
        if (! is_array($enabled)) {
            return self::defaultEnabled();
        }

        return array_values(array_intersect(self::allChannels(), array_map('strval', $enabled)));
    }

    /** @return list<string> */
    public static function enabledFor(?int $companyId): array
    {
        if (! $companyId) {
            return self::defaultEnabled();
        }

        $company = Company::query()->find($companyId);
        if (! $company) {
            return self::defaultEnabled();
        }

        return self::enabledFromSettings($company->notification_channels_settings);
    }

    public static function isEnabled(int $companyId, string $channel): bool
    {
        return in_array($channel, self::enabledFor($companyId), true);
    }

    /**
     * @param  list<string>  $enabled
     */
    public static function saveForCompany(Company $company, array $enabled): void
    {
        $enabled = array_values(array_intersect(self::allChannels(), $enabled));
        $company->notification_channels_settings = ['enabled' => $enabled];
        $company->save();
    }
}
