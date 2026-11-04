<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;

class NotificationRuleService
{
    public function __construct(private readonly NotificationTypeRegistry $registry)
    {
    }

    public function ensureRule(int $companyId, string $typeKey): NotificationRule
    {
        $existing = NotificationRule::query()
            ->where('company_id', $companyId)
            ->where('type_key', $typeKey)
            ->first();

        if ($existing) {
            return $this->normalizeExplicitRecipients($existing);
        }

        $def = $this->registry->get($typeKey) ?? [];
        $entitled = CompanyNotificationChannels::enabledFor($companyId);
        $defaultChannels = array_values(array_intersect(
            $def['default_channels'] ?? ['in_app', 'email'],
            $entitled
        ));
        if ($defaultChannels === []) {
            $defaultChannels = $entitled !== [] ? $entitled : ['in_app'];
        }

        return NotificationRule::query()->create([
            'company_id' => $companyId,
            'type_key' => $typeKey,
            'enabled' => false,
            'config' => $def['default_config'] ?? [],
            'recipient_config' => [
                'strategies' => ['explicit_users'],
                'both_user_ids' => [],
                'in_app_only_user_ids' => [],
                'email_only_user_ids' => [],
                'emails' => [],
            ],
            'channels' => $defaultChannels,
        ]);
    }

    /**
     * Migrate legacy recipient shapes to three-panel buckets; drop built-in strategies.
     */
    private function normalizeExplicitRecipients(NotificationRule $rule): NotificationRule
    {
        $config = is_array($rule->recipient_config) ? $rule->recipient_config : [];
        $buckets = RecipientBuckets::normalize($config);
        $strategies = $config['strategies'] ?? [];
        if (! is_array($strategies)) {
            $strategies = [];
        }

        $nextConfig = array_merge($buckets, [
            'strategies' => ['explicit_users'],
        ]);

        $hasLegacy = array_key_exists('user_ids', $config)
            || array_key_exists('include_defaults', $config)
            || ($strategies !== ['explicit_users'] && $strategies !== []);

        $alreadyNormalized = ! $hasLegacy
            && array_key_exists('both_user_ids', $config)
            && array_key_exists('in_app_only_user_ids', $config)
            && array_key_exists('email_only_user_ids', $config)
            && array_key_exists('emails', $config)
            && ($strategies === ['explicit_users'] || $strategies === []);

        if ($alreadyNormalized) {
            $hasRecipients = RecipientBuckets::hasAnyRecipient($buckets);
            if (! $hasRecipients && $rule->enabled) {
                $rule->enabled = false;
                $rule->save();
            }

            return $rule;
        }

        $dirty = false;
        if ($rule->recipient_config != $nextConfig) {
            $rule->recipient_config = $nextConfig;
            $dirty = true;
        }

        $implied = RecipientBuckets::impliedChannels($buckets);
        $entitled = CompanyNotificationChannels::enabledFor((int) $rule->company_id);
        $channels = array_values(array_intersect($implied !== [] ? $implied : (is_array($rule->channels) ? $rule->channels : []), $entitled));
        if ($channels !== [] && $rule->channels != $channels) {
            $rule->channels = $channels;
            $dirty = true;
        }

        if (! RecipientBuckets::hasAnyRecipient($buckets) && $rule->enabled) {
            $rule->enabled = false;
            $dirty = true;
        }

        if ($dirty) {
            $rule->save();
        }

        return $rule;
    }

    /**
     * Ensure default rules exist for all registered types for a company.
     *
     * @return list<NotificationRule>
     */
    public function ensureDefaultsForCompany(int $companyId): array
    {
        $rules = [];
        foreach ($this->registry->keys() as $typeKey) {
            $rules[] = $this->ensureRule($companyId, $typeKey);
        }

        return $rules;
    }

    /**
     * @param  array{
     *   enabled?:bool,
     *   config?:array,
     *   recipient_config?:array,
     *   channels?:list<string>
     * }  $data
     */
    public function updateRule(NotificationRule $rule, array $data): NotificationRule
    {
        $entitled = CompanyNotificationChannels::enabledFor((int) $rule->company_id);

        if (array_key_exists('recipient_config', $data) && is_array($data['recipient_config'])) {
            $buckets = RecipientBuckets::normalize($data['recipient_config']);
            $data['recipient_config'] = array_merge($buckets, [
                'strategies' => ['explicit_users'],
            ]);
            // Channels are derived from which panels have recipients.
            $implied = RecipientBuckets::impliedChannels($buckets);
            $data['channels'] = array_values(array_intersect($implied, $entitled));
        } elseif (array_key_exists('channels', $data)) {
            $data['channels'] = array_values(array_intersect($entitled, $data['channels'] ?? []));
        }

        $rule->fill($data);
        $rule->save();

        return $rule->fresh();
    }
}
