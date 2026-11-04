<?php

namespace App\Services\Notifications;

/**
 * Normalizes notification rule recipient panels into explicit buckets.
 */
class RecipientBuckets
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *   both_user_ids: list<int>,
     *   in_app_only_user_ids: list<int>,
     *   email_only_user_ids: list<int>,
     *   emails: list<string>,
     *   exclude_user_ids: list<int>
     * }
     */
    public static function normalize(array $config): array
    {
        $both = self::intIds($config['both_user_ids'] ?? null);
        $inAppOnly = self::intIds($config['in_app_only_user_ids'] ?? null);
        $emailOnly = self::intIds($config['email_only_user_ids'] ?? null);
        $emails = self::emails($config['emails'] ?? null);
        $exclude = self::intIds($config['exclude_user_ids'] ?? null);

        // Legacy single list → treat as in-app & email.
        if ($both === [] && $inAppOnly === [] && $emailOnly === [] && isset($config['user_ids'])) {
            $both = self::intIds($config['user_ids']);
        }

        // Enforce mutual exclusivity (both wins, then in-app-only).
        $inAppOnly = array_values(array_diff($inAppOnly, $both));
        $emailOnly = array_values(array_diff($emailOnly, $both, $inAppOnly));

        return [
            'both_user_ids' => $both,
            'in_app_only_user_ids' => $inAppOnly,
            'email_only_user_ids' => $emailOnly,
            'emails' => $emails,
            'exclude_user_ids' => $exclude,
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function inAppUserIds(array $normalized): array
    {
        $ids = array_merge($normalized['both_user_ids'], $normalized['in_app_only_user_ids']);
        $ids = array_values(array_diff(array_unique($ids), $normalized['exclude_user_ids']));

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function emailUserIds(array $normalized): array
    {
        $ids = array_merge($normalized['both_user_ids'], $normalized['email_only_user_ids']);
        $ids = array_values(array_diff(array_unique($ids), $normalized['exclude_user_ids']));

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<string>
     */
    public static function channelsForUser(int $userId, array $normalized): array
    {
        if (in_array($userId, $normalized['both_user_ids'], true)) {
            return ['in_app', 'email'];
        }
        if (in_array($userId, $normalized['in_app_only_user_ids'], true)) {
            return ['in_app'];
        }

        return [];
    }

    /**
     * Channels implied by recipient panels (before company entitlement filter).
     *
     * @param  array<string, mixed>  $normalized
     * @return list<string>
     */
    public static function impliedChannels(array $normalized): array
    {
        $channels = [];
        if (self::inAppUserIds($normalized) !== []) {
            $channels[] = 'in_app';
        }
        if (self::emailUserIds($normalized) !== [] || $normalized['emails'] !== []) {
            $channels[] = 'email';
        }

        return $channels;
    }

    public static function hasAnyRecipient(array $normalized): bool
    {
        return self::inAppUserIds($normalized) !== []
            || self::emailUserIds($normalized) !== []
            || $normalized['emails'] !== [];
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    private static function intIds($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value), fn ($id) => $id > 0)));
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private static function emails($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $email) {
            $email = strtolower(trim((string) $email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $email;
            }
        }

        return array_values(array_unique($out));
    }
}
