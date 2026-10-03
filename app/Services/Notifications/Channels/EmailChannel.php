<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Email\EmailAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements ChannelInterface
{
    public function key(): string
    {
        return 'email';
    }

    public function deliver(UserNotification $notification, NotificationDelivery $delivery): void
    {
        $delivery->forceFill([
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
        ])->save();

        $user = User::query()->find($notification->user_id);
        if (! $user || empty($user->email) || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => ['reason' => 'invalid_or_missing_email'],
            ])->save();

            return;
        }

        $body = trim((string) $notification->body);
        if ($notification->action_url) {
            $body .= "\n\nOpen: " . url($notification->action_url);
        }

        $fromMeta = $this->prepareSender($notification);
        if (! ($fromMeta['ready'] ?? false)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => ['error' => $fromMeta['message'] ?? 'sender_not_ready'],
            ])->save();

            throw new \RuntimeException($fromMeta['message'] ?? 'Notification email sender is not ready.');
        }

        try {
            Mail::raw($body !== '' ? $body : $notification->title, function ($message) use ($user, $notification, $fromMeta) {
                $message->to($user->email)->subject($notification->title);
                if (! empty($fromMeta['from_email'])) {
                    $message->from($fromMeta['from_email'], $fromMeta['from_name'] ?? null);
                }
            });

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => [
                    'ok' => true,
                    'to' => $user->email,
                    'from' => $fromMeta['from_email'] ?? null,
                ],
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('notification.email_failed', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => ['error' => $e->getMessage()],
            ])->save();

            throw $e;
        }
    }

    /** @return array{ready: bool, message?: string, from_email?: string, from_name?: string} */
    private function prepareSender(UserNotification $notification): array
    {
        $rule = $notification->rule_id
            ? NotificationRule::query()->find($notification->rule_id)
            : null;

        $config = is_array($rule?->config) ? $rule->config : [];
        $accountId = isset($config['email_account_id']) ? (int) $config['email_account_id'] : null;
        if ($accountId <= 0) {
            $accountId = null;
        }

        return app(EmailAccountService::class)->prepareSystemSender(
            $accountId,
            $notification->company_id ? (int) $notification->company_id : null
        );
    }
}
