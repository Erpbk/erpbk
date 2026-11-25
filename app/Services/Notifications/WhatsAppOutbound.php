<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\Context\WhatsAppContextBuilderRegistry;
use App\Services\WhatsApp\WhatsAppCloudApiClient;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppMessageStore;
use App\Services\WhatsApp\WhatsAppTemplateBindingService;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppSendContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp messages that are not tied to an in-app inbox row.
 * Template sends require a trigger binding + config injection.
 */
class WhatsAppOutbound
{
    public function __construct(
        private readonly WhatsAppConnectionService $connections,
        private readonly WhatsAppCloudApiClient $client,
        private readonly WhatsAppTemplateBindingService $bindings,
        private readonly WhatsAppTemplateInjector $injector,
        private readonly WhatsAppContextBuilderRegistry $contexts,
        private readonly WhatsAppMessageStore $messages
    ) {
    }

    /**
     * Send a bound Meta template for a trigger key.
     */
    public function sendBoundTemplate(
        string $triggerKey,
        int $companyId,
        string $toPhone,
        WhatsAppSendContext $context,
        ?string $dedupeFingerprint = null,
        ?Model $participant = null
    ): void {
        $resolved = $this->bindings->resolve($triggerKey);
        if (! ($resolved['ready'] ?? false) || ! ($resolved['template'] instanceof WhatsAppTemplate)) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'no_template_binding',
                'trigger' => $triggerKey,
                'message' => $resolved['message'] ?? null,
            ]);

            return;
        }

        /** @var WhatsAppTemplate $template */
        $template = $resolved['template'];
        $this->send(
            companyId: $companyId,
            toPhone: $toPhone,
            mode: 'template',
            body: null,
            templateName: $template->name,
            language: $template->language ?: 'en',
            bodyParams: [],
            connectionId: $resolved['connection_id'] ?? null,
            rule: null,
            dedupeFingerprint: $dedupeFingerprint,
            catalogTemplate: $template,
            sendContext: $context,
            triggerKey: $triggerKey,
            participant: $participant
        );
    }

    /**
     * @deprecated Prefer sendBoundTemplate() for automated/template sends.
     * @param  list<string>  $bodyParams
     */
    public function sendTemplate(
        int $companyId,
        string $toPhone,
        string $templateName,
        string $language = 'en',
        array $bodyParams = [],
        ?int $connectionId = null,
        ?NotificationRule $rule = null,
        ?string $dedupeFingerprint = null,
        ?int $whatsappTemplateId = null,
        array $context = []
    ): void {
        $triggerKey = $rule?->type_key ? 'notification:'.$rule->type_key : null;
        if ($triggerKey) {
            $builtContext = $this->contexts->build($triggerKey, array_merge($context, [
                'company_id' => $companyId,
                'company_name' => Company::query()->whereKey($companyId)->value('name'),
                'to' => $toPhone,
                'recipient_phone' => $toPhone,
            ])) ?? WhatsAppSendContext::fromArray(array_merge($context, [
                'company_name' => Company::query()->whereKey($companyId)->value('name'),
                'recipient_phone' => $toPhone,
            ]));

            $this->sendBoundTemplate(
                $triggerKey,
                $companyId,
                $toPhone,
                $builtContext,
                $dedupeFingerprint
            );

            return;
        }

        Log::info('notification.whatsapp_outbound_skipped', [
            'company_id' => $companyId,
            'reason' => 'template_send_requires_binding',
        ]);
    }

    public function sendText(
        int $companyId,
        string $toPhone,
        string $body,
        ?int $connectionId = null,
        ?NotificationRule $rule = null,
        ?string $dedupeFingerprint = null
    ): void {
        $this->send(
            companyId: $companyId,
            toPhone: $toPhone,
            mode: 'text',
            body: $body,
            templateName: null,
            language: 'en',
            bodyParams: [],
            connectionId: $connectionId,
            rule: $rule,
            dedupeFingerprint: $dedupeFingerprint
        );
    }

    /**
     * @param  list<string>  $bodyParams
     */
    public function send(
        int $companyId,
        string $toPhone,
        string $mode,
        ?string $body = null,
        ?string $templateName = null,
        string $language = 'en',
        array $bodyParams = [],
        ?int $connectionId = null,
        ?NotificationRule $rule = null,
        ?string $dedupeFingerprint = null,
        ?WhatsAppTemplate $catalogTemplate = null,
        ?WhatsAppSendContext $sendContext = null,
        ?string $triggerKey = null,
        ?Model $participant = null
    ): void {
        $to = PhoneNumber::toWhatsAppDigits($toPhone);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'invalid_phone',
                'to' => $toPhone,
            ]);

            return;
        }

        if (! CompanyNotificationChannels::isEnabled($companyId, CompanyNotificationChannels::CHANNEL_WHATSAPP)) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'channel_not_entitled',
                'to' => $to,
            ]);

            return;
        }

        $config = is_array($rule?->config) ? $rule->config : [];
        if ($connectionId === null && isset($config['whatsapp_connection_id'])) {
            $connectionId = (int) $config['whatsapp_connection_id'];
            if ($connectionId <= 0) {
                $connectionId = null;
            }
        }

        // Notification-rule sends require an explicit number on the rule (no auto-resolve).
        if ($rule !== null && ($connectionId === null || $connectionId <= 0)) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'whatsapp_connection_not_configured',
                'trigger' => $triggerKey,
            ]);

            return;
        }

        if ($connectionId === null || $connectionId <= 0) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'whatsapp_connection_required',
                'trigger' => $triggerKey,
            ]);

            return;
        }

        $dedupeKey = hash('sha256', implode('|', [
            $companyId,
            $to,
            $mode,
            $dedupeFingerprint ?: (($templateName ?: $body).'|'.now()->toDateString()),
        ]));

        $alreadySent = NotificationDelivery::query()
            ->where('company_id', $companyId)
            ->whereNull('user_notification_id')
            ->where('to_phone', $to)
            ->where('channel', CompanyNotificationChannels::CHANNEL_WHATSAPP)
            ->where('status', NotificationDelivery::STATUS_SENT)
            ->where('provider_response->dedupe_key', $dedupeKey)
            ->exists();

        if ($alreadySent) {
            Log::info('notification.whatsapp_outbound_skipped', [
                'company_id' => $companyId,
                'reason' => 'duplicate',
                'to' => $to,
            ]);

            return;
        }

        $delivery = NotificationDelivery::query()->create([
            'company_id' => $companyId,
            'user_notification_id' => null,
            'channel' => CompanyNotificationChannels::CHANNEL_WHATSAPP,
            'to_phone' => $to,
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 0,
            'provider_response' => ['dedupe_key' => $dedupeKey, 'mode' => $mode, 'trigger' => $triggerKey],
        ]);

        $delivery->forceFill([
            'attempts' => 1,
            'last_attempt_at' => now(),
        ])->save();

        $prepared = $this->connections->prepareSystemConnection($connectionId, $companyId);
        if (! ($prepared['ready'] ?? false) || ! ($prepared['connection'] instanceof WhatsAppConnection)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'mode' => $mode,
                    'error' => $prepared['message'] ?? 'connection_not_ready',
                    'to' => $to,
                ],
            ])->save();

            return;
        }

        /** @var WhatsAppConnection $connection */
        $connection = $prepared['connection'];

        $previewBody = $body;
        $components = null;
        $resolvedTemplateName = $templateName;
        $resolvedLanguage = $language;

        $downgradedFromTemplate = false;
        $sendMode = $mode;

        if ($mode === 'template') {
            if (! $catalogTemplate instanceof WhatsAppTemplate || ! $sendContext instanceof WhatsAppSendContext) {
                $delivery->forceFill([
                    'status' => NotificationDelivery::STATUS_SKIPPED,
                    'provider_response' => [
                        'dedupe_key' => $dedupeKey,
                        'reason' => 'template_requires_binding_and_context',
                    ],
                ])->save();

                return;
            }

            // Single-pass inject + preview (no "-" — missing injects skip).
            $payload = $this->injector->previewPayload($catalogTemplate, $sendContext);
            if (! ($payload['ok'] ?? false)) {
                $delivery->forceFill([
                    'status' => NotificationDelivery::STATUS_SKIPPED,
                    'provider_response' => [
                        'dedupe_key' => $dedupeKey,
                        'reason' => 'inject_failed',
                        'error' => $payload['error'] ?? null,
                    ],
                ])->save();

                return;
            }

            $resolvedTemplateName = (string) ($payload['template_name'] ?? $catalogTemplate->name);
            $resolvedLanguage = (string) ($payload['language'] ?? 'en');
            $components = $payload['components'] ?? null;
            $previewBody = trim((string) ($payload['text'] ?? ''));

            $useFreeText = $previewBody !== ''
                && app(\App\Services\WhatsApp\WhatsAppInboxService::class)
                    ->hasOpenWindow($companyId, (string) $to, (int) $connection->id);

            if ($useFreeText) {
                $result = $this->client->sendText($connection, $to, $previewBody);
                $sendMode = 'text';
                $downgradedFromTemplate = true;
                $components = null;
            } else {
                $result = $this->client->sendTemplate(
                    $connection,
                    $to,
                    $resolvedTemplateName,
                    $resolvedLanguage,
                    is_array($components) ? $components : []
                );
            }
        } else {
            $result = $this->client->sendText($connection, $to, (string) $body);
        }

        $ok = (bool) ($result['ok'] ?? false);
        if ($ok) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'mode' => $sendMode,
                    'downgraded_from_template' => $downgradedFromTemplate,
                    'ok' => true,
                    'to' => $to,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result['response'] ?? null,
                    'connection_id' => $connection->id,
                    'trigger' => $triggerKey,
                ],
            ])->save();
        } else {
            Log::warning('notification.whatsapp_outbound_failed', [
                'company_id' => $companyId,
                'to' => $to,
                'error' => $result['error'] ?? null,
            ]);

            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => [
                    'dedupe_key' => $dedupeKey,
                    'mode' => $sendMode,
                    'downgraded_from_template' => $downgradedFromTemplate,
                    'error' => $result['error'] ?? 'send_failed',
                    'to' => $to,
                    'response' => $result['response'] ?? null,
                    'connection_id' => $connection->id,
                ],
            ])->save();
        }

        $this->messages->recordOutbound([
            'company_id' => $companyId,
            'whatsapp_connection_id' => $connection->id,
            'phone_number_id' => $connection->phone_number_id,
            'peer_phone' => $to,
            'wa_message_id' => $result['message_id'] ?? null,
            'message_type' => $sendMode === 'template' ? 'template' : 'text',
            'body' => $previewBody,
            'template_name' => $sendMode === 'template' ? $resolvedTemplateName : null,
            'template_language' => $sendMode === 'template' ? $resolvedLanguage : null,
            'components' => $sendMode === 'template' ? $components : null,
            'status' => $ok ? WhatsAppMessage::STATUS_SENT : WhatsAppMessage::STATUS_FAILED,
            'notification_delivery_id' => $delivery->id,
            'source' => 'notification',
            'trigger_key' => $triggerKey,
            'participant' => $participant,
            'payload' => [
                'dedupe_key' => $dedupeKey,
                'result' => $result['response'] ?? null,
                'error' => $ok ? null : ($result['error'] ?? null),
                'downgraded_from_template' => $downgradedFromTemplate,
            ],
        ]);
    }
}
