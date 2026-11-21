<?php

namespace App\Services\Notifications;

/**
 * Normalizes notification rule recipient panels into explicit channel buckets.
 */
class RecipientBuckets
{
    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *   in_app_user_ids: list<int>,
     *   email_user_ids: list<int>,
     *   whatsapp_user_ids: list<int>,
     *   emails: list<string>,
     *   exclude_user_ids: list<int>
     * }
     */
    public static function normalize(array $config): array
    {
        $inApp = self::intIds($config['in_app_user_ids'] ?? null);
        $email = self::intIds($config['email_user_ids'] ?? null);
        $whatsapp = self::intIds($config['whatsapp_user_ids'] ?? null);
        $emails = self::emails($config['emails'] ?? null);
        $exclude = self::intIds($config['exclude_user_ids'] ?? null);

        // Legacy three-panel shape → channel lists.
        $both = self::intIds($config['both_user_ids'] ?? null);
        $inAppOnly = self::intIds($config['in_app_only_user_ids'] ?? null);
        $emailOnly = self::intIds($config['email_only_user_ids'] ?? null);

        // Legacy single list → treat as in-app & email.
        if ($both === [] && $inAppOnly === [] && $emailOnly === []
            && $inApp === [] && $email === [] && isset($config['user_ids'])) {
            $both = self::intIds($config['user_ids']);
        }

        if ($both !== [] || $inAppOnly !== [] || $emailOnly !== []) {
            $inApp = array_values(array_unique(array_merge($inApp, $both, $inAppOnly)));
            $email = array_values(array_unique(array_merge($email, $both, $emailOnly)));
        }

        $inApp = array_values(array_diff($inApp, $exclude));
        $email = array_values(array_diff($email, $exclude));
        $whatsapp = array_values(array_diff($whatsapp, $exclude));

        return [
            'in_app_user_ids' => $inApp,
            'email_user_ids' => $email,
            'whatsapp_user_ids' => $whatsapp,
            'emails' => $emails,
            'exclude_user_ids' => $exclude,
        ];
    }

    /**
     * Users who should get an inbox-backed notification row (in-app and/or WhatsApp).
     *
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function inboxUserIds(array $normalized): array
    {
        return array_values(array_unique(array_merge(
            $normalized['in_app_user_ids'] ?? [],
            $normalized['whatsapp_user_ids'] ?? []
        )));
    }

    /**
     * @deprecated Use inboxUserIds()
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function inAppUserIds(array $normalized): array
    {
        return self::inboxUserIds($normalized);
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function emailUserIds(array $normalized): array
    {
        return array_values(array_unique($normalized['email_user_ids'] ?? []));
    }

    /**
     * Email-only users: on email list but not receiving an inbox-backed row.
     *
     * @param  array<string, mixed>  $normalized
     * @return list<int>
     */
    public static function emailOnlyUserIds(array $normalized): array
    {
        return array_values(array_diff(
            self::emailUserIds($normalized),
            self::inboxUserIds($normalized)
        ));
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<string>
     */
    public static function channelsForUser(int $userId, array $normalized): array
    {
        $channels = [];
        if (in_array($userId, $normalized['in_app_user_ids'] ?? [], true)) {
            $channels[] = 'in_app';
        }
        if (in_array($userId, $normalized['email_user_ids'] ?? [], true)) {
            $channels[] = 'email';
        }
        if (in_array($userId, $normalized['whatsapp_user_ids'] ?? [], true)) {
            $channels[] = 'whatsapp';
        }

        return $channels;
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
        if (($normalized['in_app_user_ids'] ?? []) !== []) {
            $channels[] = 'in_app';
        }
        if (($normalized['email_user_ids'] ?? []) !== [] || ($normalized['emails'] ?? []) !== []) {
            $channels[] = 'email';
        }
        if (($normalized['whatsapp_user_ids'] ?? []) !== []) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    public static function hasAnyRecipient(array $normalized): bool
    {
        return ($normalized['in_app_user_ids'] ?? []) !== []
            || ($normalized['email_user_ids'] ?? []) !== []
            || ($normalized['whatsapp_user_ids'] ?? []) !== []
            || ($normalized['emails'] ?? []) !== [];
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
