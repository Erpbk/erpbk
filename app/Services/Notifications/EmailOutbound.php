<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Services\Email\EmailAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends notification emails that are not tied to an in-app inbox row
 * (email-only users and external addresses).
 */
class EmailOutbound
{
    public function __construct(private readonly EmailAccountService $emailAccounts)
    {
    }

    public function send(
        int $companyId,
        ?NotificationRule $rule,
        string $toEmail,
        string $title,
        ?string $body,
        ?string $actionUrl = null,
        ?string $dedupeFingerprint = null
    ): void {
        $toEmail = strtolower(trim($toEmail));
        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            Log::info('notification.email_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'invalid_email',
                'to' => $toEmail,
            ]);

            return;
        }

        if (! CompanyNotificationChannels::isEnabled($companyId, 'email')) {
            Log::info('notification.email_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'channel_not_entitled',
                'to' => $toEmail,
            ]);

            return;
        }

        $dedupeKey = hash('sha256', implode('|', [
            $companyId,
            $toEmail,
            $dedupeFingerprint ?: ($title.'|'.now()->toDateString()),
        ]));

        $alreadySent = NotificationDelivery::query()
            ->where('company_id', $companyId)
            ->whereNull('user_notification_id')
            ->where('to_email', $toEmail)
            ->where('channel', 'email')
            ->where('status', NotificationDelivery::STATUS_SENT)
            ->where('provider_response->dedupe_key', $dedupeKey)
            ->exists();

        if ($alreadySent) {
            Log::info('notification.email_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'duplicate',
                'to' => $toEmail,
            ]);

            return;
        }

        $delivery = NotificationDelivery::query()->create([
            'company_id' => $companyId,
            'user_notification_id' => null,
            'channel' => 'email',
            'to_email' => $toEmail,
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 0,
            'provider_response' => ['dedupe_key' => $dedupeKey],
        ]);

        $delivery->forceFill([
            'attempts' => 1,
            'last_attempt_at' => now(),
        ])->save();

        $messageBody = trim((string) $body);
        if ($actionUrl) {
            $messageBody .= "\n\nOpen: " . url($actionUrl);
        }

        $accountId = null;
        $config = is_array($rule?->config) ? $rule->config : [];
        if (isset($config['email_account_id'])) {
            $accountId = (int) $config['email_account_id'];
            if ($accountId <= 0) {
                $accountId = null;
            }
        }

        $fromMeta = $this->emailAccounts->prepareSystemSender($accountId, $companyId);
        if (! ($fromMeta['ready'] ?? false)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'error' => $fromMeta['message'] ?? 'sender_not_ready',
                    'to' => $toEmail,
                ],
            ])->save();

            return;
        }

        try {
            Mail::raw($messageBody !== '' ? $messageBody : $title, function ($message) use ($toEmail, $title, $fromMeta) {
                $message->to($toEmail)->subject($title);
                if (! empty($fromMeta['from_email'])) {
                    $message->from($fromMeta['from_email'], $fromMeta['from_name'] ?? null);
                }
            });

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'ok' => true,
                    'to' => $toEmail,
                    'from' => $fromMeta['from_email'] ?? null,
                ],
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('notification.email_outbound_failed', [
                'company_id' => $companyId,
                'to' => $toEmail,
                'error' => $e->getMessage(),
            ]);

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'error' => $e->getMessage(),
                    'to' => $toEmail,
                ],
            ])->save();
        }
    }
}
