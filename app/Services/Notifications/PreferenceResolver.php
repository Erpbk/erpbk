<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\User;

class PreferenceResolver
{
    /**
     * @param  list<string>  $channels
     * @return list<string>
     */
    public function filterChannels(
        int $companyId,
        ?User $user,
        string $typeKey,
        array $channels
    ): array {
        if ($channels === []) {
            return [];
        }

        $prefs = NotificationPreference::query()
            ->where('company_id', $companyId)
            ->where('type_key', $typeKey)
            ->whereIn('channel', $channels)
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id');
                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            })
            ->get();

        if ($prefs->isEmpty()) {
            return $channels;
        }

        $companyDefaults = $prefs->whereNull('user_id')->keyBy('channel');
        $userPrefs = $user
            ? $prefs->where('user_id', $user->id)->keyBy('channel')
            : collect();

        $result = [];
        foreach ($channels as $channel) {
            $pref = $userPrefs->get($channel) ?? $companyDefaults->get($channel);
            if ($pref === null || $pref->enabled) {
                $result[] = $channel;
            }
        }

        return $result;
    }

    /**
     * Resolve effective channels for a rule + company entitlements + prefs.
     *
     * @return list<string>
     */
    public function effectiveChannels(NotificationRule $rule, ?User $user = null): array
    {
        $ruleChannels = is_array($rule->channels) ? $rule->channels : [];
        $entitled = CompanyNotificationChannels::enabledFor((int) $rule->company_id);
        $intersected = array_values(array_intersect($ruleChannels, $entitled));

        return $this->filterChannels(
            (int) $rule->company_id,
            $user,
            $rule->type_key,
            $intersected
        );
    }

    /**
     * Whether a single channel is allowed for this user after preference filtering.
     */
    public function channelEnabled(
        int $companyId,
        ?User $user,
        string $typeKey,
        string $channel
    ): bool {
        return in_array($channel, $this->filterChannels($companyId, $user, $typeKey, [$channel]), true);
    }
}
