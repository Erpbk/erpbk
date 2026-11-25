<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\WhatsApp\Context\WhatsAppContextBuilderRegistry;
use App\Services\WhatsApp\WhatsAppCloudApiClient;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppMessageStore;
use App\Services\WhatsApp\WhatsAppTemplateBindingService;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements ChannelInterface
{
    public function key(): string
    {
        return CompanyNotificationChannels::CHANNEL_WHATSAPP;
    }

    public function deliver(UserNotification $notification, NotificationDelivery $delivery): void
    {
        $delivery->forceFill([
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
        ])->save();

        $data = is_array($notification->data) ? $notification->data : [];
        $user = User::query()->find($notification->user_id);

        $toRaw = $data['whatsapp_to'] ?? $data['phone'] ?? null;
        $to = PhoneNumber::toWhatsAppDigits(is_string($toRaw) ? $toRaw : null);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            $to = $user?->whatsappPhone();
        }

        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => ['reason' => 'invalid_or_missing_phone'],
            ])->save();

            return;
        }

        $delivery->forceFill(['to_phone' => $to])->save();

        $rule = $notification->rule_id
            ? NotificationRule::query()->find($notification->rule_id)
            : null;
        $config = is_array($rule?->config) ? $rule->config : [];
        $typeKey = (string) ($rule?->type_key ?? $notification->type_key ?? '');
        $triggerKey = $typeKey !== '' ? 'notification:'.$typeKey : '';

        $resolved = $triggerKey !== ''
            ? app(WhatsAppTemplateBindingService::class)->resolve($triggerKey)
            : ['ready' => false, 'message' => 'missing_trigger'];

        if (! ($resolved['ready'] ?? false) || ! ($resolved['template'] instanceof WhatsAppTemplate)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => [
                    'reason' => 'no_template_binding',
                    'trigger' => $triggerKey,
                    'message' => $resolved['message'] ?? null,
                ],
            ])->save();

            return;
        }

        /** @var WhatsAppTemplate $template */
        $template = $resolved['template'];
        // Notification rules must set an explicit WhatsApp number — no auto/default fallback.
        $connectionId = isset($config['whatsapp_connection_id']) ? (int) $config['whatsapp_connection_id'] : null;
        if ($connectionId === null || $connectionId <= 0) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => [
                    'reason' => 'whatsapp_connection_not_configured',
                    'trigger' => $triggerKey,
                ],
            ])->save();

            return;
        }

        $prepared = app(WhatsAppConnectionService::class)->prepareSystemConnection(
            $connectionId,
            $notification->company_id ? (int) $notification->company_id : null
        );

        if (! ($prepared['ready'] ?? false) || ! ($prepared['connection'] instanceof WhatsAppConnection)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => ['error' => $prepared['message'] ?? 'connection_not_ready'],
            ])->save();

            throw new \RuntimeException($prepared['message'] ?? 'WhatsApp connection is not ready.');
        }

        /** @var WhatsAppConnection $connection */
        $connection = $prepared['connection'];

        $context = app(WhatsAppContextBuilderRegistry::class)->build($triggerKey, [
            'notification' => $notification,
            'user' => $user,
            'data' => $data,
            'to' => $to,
            'recipient_phone' => $to,
            'company_id' => $notification->company_id,
        ]);
        if ($context === null) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => [
                    'reason' => 'no_context_builder',
                    'trigger' => $triggerKey,
                ],
            ])->save();

            return;
        }

        $injector = app(WhatsAppTemplateInjector::class);
        // No "-" placeholder — missing injects skip the send (existing channel behavior).
        $payload = $injector->previewPayload($template, $context);
        if (! ($payload['ok'] ?? false)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => [
                    'reason' => 'inject_failed',
                    'error' => $payload['error'] ?? null,
                    'template' => $template->name,
                ],
            ])->save();

            return;
        }

        $preview = trim((string) ($payload['text'] ?? ''));
        $templateName = (string) ($payload['template_name'] ?? $template->name);
        $templateLanguage = (string) ($payload['language'] ?? 'en');
        $components = $payload['components'] ?? [];
        $companyId = $notification->company_id ? (int) $notification->company_id : 0;
        $useFreeText = $preview !== ''
            && $companyId > 0
            && app(\App\Services\WhatsApp\WhatsAppInboxService::class)
                ->hasOpenWindow($companyId, (string) $to, (int) $connection->id);
        $mode = $useFreeText ? 'text' : 'template';
        $messages = app(WhatsAppMessageStore::class);
        $client = app(WhatsAppCloudApiClient::class);

        try {
            $result = $useFreeText
                ? $client->sendText($connection, $to, $preview)
                : $client->sendTemplate(
                    $connection,
                    $to,
                    $templateName,
                    $templateLanguage,
                    is_array($components) ? $components : []
                );

            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException((string) ($result['error'] ?? 'WhatsApp send failed.'));
            }

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => [
                    'ok' => true,
                    'mode' => $mode,
                    'downgraded_from_template' => $useFreeText,
                    'to' => $to,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result['response'] ?? null,
                    'connection_id' => $connection->id,
                    'whatsapp_template_id' => $template->id,
                    'trigger' => $triggerKey,
                ],
            ])->save();

            $messages->recordOutbound([
                'company_id' => $notification->company_id,
                'whatsapp_connection_id' => $connection->id,
                'phone_number_id' => $connection->phone_number_id,
                'peer_phone' => $to,
                'wa_message_id' => $result['message_id'] ?? null,
                'message_type' => $mode,
                'body' => $preview !== '' ? $preview : null,
                'template_name' => $mode === 'template' ? $templateName : null,
                'template_language' => $mode === 'template' ? $templateLanguage : null,
                'components' => $mode === 'template' ? (is_array($components) ? $components : null) : null,
                'status' => WhatsAppMessage::STATUS_SENT,
                'notification_delivery_id' => $delivery->id,
                'source' => 'notification',
                'trigger_key' => $triggerKey !== '' ? $triggerKey : null,
                'participant' => $user,
                'payload' => [
                    'notification_id' => $notification->id,
                    'result' => $result['response'] ?? null,
                    'downgraded_from_template' => $useFreeText,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('notification.whatsapp_failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => [
                    'error' => $e->getMessage(),
                    'to' => $to,
                    'mode' => $mode,
                    'downgraded_from_template' => $useFreeText,
                ],
            ])->save();

            $messages->recordOutbound([
                'company_id' => $notification->company_id,
                'whatsapp_connection_id' => $connection->id,
                'phone_number_id' => $connection->phone_number_id,
                'peer_phone' => $to,
                'message_type' => $mode,
                'body' => $preview !== '' ? $preview : null,
                'template_name' => $mode === 'template' ? $templateName : null,
                'template_language' => $mode === 'template' ? $templateLanguage : null,
                'components' => $mode === 'template' ? (is_array($components) ? $components : null) : null,
                'status' => WhatsAppMessage::STATUS_FAILED,
                'notification_delivery_id' => $delivery->id,
                'source' => 'notification',
                'trigger_key' => $triggerKey !== '' ? $triggerKey : null,
                'participant' => $user,
                'payload' => [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                    'downgraded_from_template' => $useFreeText,
                ],
            ]);

            throw $e;
        }
    }
}
