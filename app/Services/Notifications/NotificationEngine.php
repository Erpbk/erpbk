<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotificationEngine
{
    public function __construct(
        private readonly NotificationTypeRegistry $registry,
        private readonly RecipientResolver $recipientResolver,
        private readonly NotificationWriter $writer,
        private readonly NotificationRuleService $ruleService,
        private readonly EmailOutbound $emailOutbound,
        private readonly PreferenceResolver $preferenceResolver,
    ) {
    }

    /**
     * Dispatch a notification for a type key (event-based or manual).
     *
     * @param  array{
     *   type_key:string,
     *   company_id:int,
     *   source?:?Model,
     *   title?:?string,
     *   body?:?string,
     *   severity?:?string,
     *   action_url?:?string,
     *   data?:array,
     *   dedupe_context?:array<string,mixed>,
     *   recipient_context?:array<string,mixed>,
     *   exclude_user_ids?:list<int>,
     *   users?:list<User>|null
     * }  $input
     * @return list<\App\Models\UserNotification>
     */
    public function dispatch(array $input): array
    {
        $typeKey = $input['type_key'];
        $typeDef = $this->registry->get($typeKey);
        if (! $typeDef) {
            Log::warning('notification.unknown_type', ['type_key' => $typeKey]);

            return [];
        }

        $companyId = (int) $input['company_id'];
        $rule = $this->ruleService->ensureRule($companyId, $typeKey);
        if (! $rule->enabled) {
            Log::info('notification.skipped_rule_disabled', [
                'company_id' => $companyId,
                'type_key' => $typeKey,
            ]);

            return [];
        }

        $source = $input['source'] ?? null;
        $sourceType = $source ? $source->getTable() : ($input['data']['source_type'] ?? 'none');
        $sourceId = $source ? $source->getKey() : ($input['data']['source_id'] ?? null);

        $recipientConfig = is_array($rule->recipient_config) ? $rule->recipient_config : [];
        if (! empty($input['exclude_user_ids'])) {
            $recipientConfig['exclude_user_ids'] = array_values(array_unique(array_merge(
                $recipientConfig['exclude_user_ids'] ?? [],
                $input['exclude_user_ids']
            )));
        }

        $context = array_merge(
            $input['recipient_context'] ?? [],
            [
                'created_by' => $source?->created_by ?? ($input['recipient_context']['created_by'] ?? null),
                'owner_id' => $source?->created_by ?? ($input['recipient_context']['owner_id'] ?? null),
            ]
        );

        $buckets = RecipientBuckets::normalize($recipientConfig);
        $fingerprintParts = $input['dedupe_context'] ?? [];
        $fingerprint = $this->fingerprint($fingerprintParts);

        $title = $input['title'] ?? ($typeDef['label'] ?? $typeKey);
        $body = $input['body'] ?? null;
        $severity = $input['severity'] ?? ($typeDef['severity'] ?? 'info');
        $actionUrl = $input['action_url'] ?? null;
        $created = [];

        // Manual override: treat provided users as in-app & email.
        if (! empty($input['users'])) {
            foreach ($input['users'] as $user) {
                if (! $user instanceof User) {
                    continue;
                }
                $notification = $this->writer->write([
                    'company_id' => $companyId,
                    'user' => $user,
                    'type_key' => $typeKey,
                    'title' => $title,
                    'body' => $body,
                    'severity' => $severity,
                    'source_type' => is_string($sourceType) ? $sourceType : 'none',
                    'source_id' => $sourceId,
                    'action_url' => $actionUrl,
                    'data' => $input['data'] ?? [],
                    'rule' => $rule,
                    'dedupe_fingerprint' => $fingerprint,
                    'branch_id' => $source?->branch_id ?? null,
                    'channels' => ['in_app', 'email'],
                ]);
                if ($notification) {
                    $created[] = $notification;
                }
            }

            return $created;
        }

        $inAppRecipients = $this->recipientResolver->resolve($companyId, $recipientConfig, $context);
        foreach ($inAppRecipients as $user) {
            $channels = RecipientBuckets::channelsForUser((int) $user->id, $buckets);
            if ($channels === []) {
                continue;
            }

            $notification = $this->writer->write([
                'company_id' => $companyId,
                'user' => $user,
                'type_key' => $typeKey,
                'title' => $title,
                'body' => $body,
                'severity' => $severity,
                'source_type' => is_string($sourceType) ? $sourceType : 'none',
                'source_id' => $sourceId,
                'action_url' => $actionUrl,
                'data' => $input['data'] ?? [],
                'rule' => $rule,
                'dedupe_fingerprint' => $fingerprint,
                'branch_id' => $source?->branch_id ?? null,
                'channels' => $channels,
            ]);

            if ($notification) {
                $created[] = $notification;
            }
        }

        // Email-only users: send mail without creating an inbox row.
        $emailOnlyUsers = $this->recipientResolver->resolveEmailOnlyUsers($companyId, $recipientConfig);
        foreach ($emailOnlyUsers as $user) {
            if (! $this->preferenceResolver->channelEnabled($companyId, $user, $typeKey, 'email')) {
                Log::info('notification.email_only_skipped_pref', [
                    'company_id' => $companyId,
                    'type_key' => $typeKey,
                    'user_id' => $user->id,
                ]);
                continue;
            }
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email === '') {
                Log::info('notification.email_only_skipped_no_email', [
                    'company_id' => $companyId,
                    'type_key' => $typeKey,
                    'user_id' => $user->id,
                ]);
                continue;
            }
            $this->emailOutbound->send($companyId, $rule, $email, $title, $body, $actionUrl, $fingerprint);
        }

        foreach ($buckets['emails'] as $externalEmail) {
            $this->emailOutbound->send($companyId, $rule, $externalEmail, $title, $body, $actionUrl, $fingerprint);
        }

        if ($created === [] && $emailOnlyUsers->isEmpty() && $buckets['emails'] === []) {
            Log::info('notification.recipient_empty', [
                'company_id' => $companyId,
                'type_key' => $typeKey,
            ]);
        }

        return $created;
    }

    public function notifyUsers(
        int $companyId,
        string $typeKey,
        iterable $users,
        string $title,
        ?string $body = null,
        array $extra = []
    ): array {
        return $this->dispatch(array_merge($extra, [
            'type_key' => $typeKey,
            'company_id' => $companyId,
            'users' => $users,
            'title' => $title,
            'body' => $body,
        ]));
    }

    /** @param  array<string, mixed>  $parts */
    private function fingerprint(array $parts): string
    {
        if ($parts === []) {
            return 'once:'.now()->toDateString();
        }

        ksort($parts);
        $chunks = [];
        foreach ($parts as $k => $v) {
            if (is_bool($v)) {
                $v = $v ? '1' : '0';
            } elseif (is_array($v)) {
                $v = json_encode($v);
            }
            $chunks[] = $k.':'.$v;
        }

        return implode('|', $chunks);
    }
}
