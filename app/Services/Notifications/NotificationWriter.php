<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class NotificationWriter
{
    public function __construct(
        private readonly DedupeKeyBuilder $dedupeKeyBuilder,
        private readonly DeliveryDispatcher $deliveryDispatcher,
        private readonly PreferenceResolver $preferenceResolver,
    ) {
    }

    /**
     * @param  array{
     *   company_id:int,
     *   user:User,
     *   type_key:string,
     *   title:string,
     *   body:?string,
     *   severity?:string,
     *   source_type?:?string,
     *   source_id?:int|string|null,
     *   action_url?:?string,
     *   data?:array,
     *   rule?:?NotificationRule,
     *   dedupe_fingerprint:string,
     *   branch_id?:int|null,
     *   channels?:list<string>|null
     * }  $payload
     */
    public function write(array $payload): ?UserNotification
    {
        $companyId = (int) $payload['company_id'];
        /** @var User $user */
        $user = $payload['user'];
        $typeKey = $payload['type_key'];
        $sourceType = (string) ($payload['source_type'] ?? 'none');
        $sourceId = $payload['source_id'] ?? null;
        $dedupeKey = $this->dedupeKeyBuilder->build(
            $companyId,
            $typeKey,
            $sourceType,
            $sourceId,
            $payload['dedupe_fingerprint']
        );

        // Include user in fingerprint uniqueness by appending user id to key
        // so each recipient gets their own row with unique dedupe.
        $dedupeKey .= '|user:'.$user->id;

        $existing = UserNotification::query()->where('dedupe_key', $dedupeKey)->first();
        if ($existing) {
            Log::info('notification.skipped_duplicate', [
                'dedupe_key' => $dedupeKey,
                'notification_id' => $existing->id,
            ]);

            return null;
        }

        $rule = $payload['rule'] ?? null;
        $channels = $payload['channels'] ?? null;
        if ($channels === null && $rule instanceof NotificationRule) {
            $channels = $this->preferenceResolver->effectiveChannels($rule, $user);
        }
        if (! is_array($channels) || $channels === []) {
            $channels = CompanyNotificationChannels::enabledFor($companyId);
        } else {
            $channels = array_values(array_intersect(
                $channels,
                CompanyNotificationChannels::enabledFor($companyId)
            ));
            $channels = $this->preferenceResolver->filterChannels($companyId, $user, $typeKey, $channels);
        }

        if ($channels === []) {
            Log::info('notification.skipped_no_channels', [
                'company_id' => $companyId,
                'type_key' => $typeKey,
                'user_id' => $user->id,
            ]);

            return null;
        }

        try {
            $notification = UserNotification::query()->create([
                'company_id' => $companyId,
                'branch_id' => $payload['branch_id'] ?? null,
                'user_id' => $user->id,
                'type' => $typeKey,
                'severity' => $payload['severity'] ?? 'info',
                'title' => $payload['title'],
                'body' => $payload['body'] ?? null,
                'data' => $payload['data'] ?? null,
                'source_type' => $sourceType !== 'none' ? $sourceType : null,
                'source_id' => $sourceId,
                'action_url' => $payload['action_url'] ?? null,
                'rule_id' => $rule?->id,
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (QueryException $e) {
            Log::info('notification.skipped_duplicate', [
                'dedupe_key' => $dedupeKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        Log::info('notification.generated', [
            'notification_id' => $notification->id,
            'type_key' => $typeKey,
            'company_id' => $companyId,
            'user_id' => $user->id,
            'dedupe_key' => $dedupeKey,
        ]);

        $this->deliveryDispatcher->dispatch($notification, $channels);

        return $notification;
    }
}
