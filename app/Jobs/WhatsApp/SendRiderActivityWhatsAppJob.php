<?php

namespace App\Jobs\WhatsApp;

use App\Models\NotificationDelivery;
use App\Models\Riders;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\RiderActivities\RiderActivityWhatsAppAlertService;
use App\Services\WhatsApp\Context\WhatsAppContextBuilderRegistry;
use App\Services\WhatsApp\WhatsAppCloudApiClient;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppInboxService;
use App\Services\WhatsApp\WhatsAppTemplateBindingService;
use App\Services\WhatsApp\WhatsAppMessageStore;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use App\Models\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRiderActivityWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $riderSnapshot
     */
    public function __construct(
        public int $companyId,
        public string $triggerKey,
        public int $riderId,
        public array $riderSnapshot = [],
        public ?int $connectionId = null,
        public ?int $actingUserId = null,
        public bool $preferFreeText = false,
        public ?string $alertDate = null,
        public ?int $projectId = null,
        public ?string $supervisor = null
    ) {
    }

    public function handle(
        WhatsAppTemplateBindingService $bindings,
        WhatsAppConnectionService $connections,
        WhatsAppTemplateInjector $injector,
        WhatsAppCloudApiClient $client,
        WhatsAppContextBuilderRegistry $contexts,
        WhatsAppMessageStore $messages,
        WhatsAppInboxService $inbox,
        RiderActivityWhatsAppAlertService $alerts
    ): void {
        $alertDate = $this->alertDate
            ?: (isset($this->riderSnapshot['alert_date_iso']) ? (string) $this->riderSnapshot['alert_date_iso'] : null);
        $projectId = $this->projectId;
        $supervisor = $this->supervisor;
        $dedupeKey = $alerts->alertDedupeKey(
            $this->companyId,
            $this->triggerKey,
            $this->riderId,
            $alertDate,
            $projectId,
            $supervisor
        );

        if ($alerts->hasAlreadySentAlert(
            $this->companyId,
            $this->triggerKey,
            $this->riderId,
            $alertDate,
            $projectId,
            $supervisor
        )) {
            Log::info('whatsapp.rider_activity_skipped', [
                'rider_id' => $this->riderId,
                'trigger' => $this->triggerKey,
                'reason' => 'duplicate',
                'dedupe_key' => $dedupeKey,
            ]);

            return;
        }

        $resolved = $bindings->resolve($this->triggerKey);
        if (! ($resolved['ready'] ?? false) || ! ($resolved['template'] instanceof WhatsAppTemplate)) {
            Log::info('whatsapp.rider_activity_skipped', [
                'rider_id' => $this->riderId,
                'trigger' => $this->triggerKey,
                'reason' => $resolved['message'] ?? 'no_binding',
            ]);

            return;
        }

        /** @var WhatsAppTemplate $template */
        $template = $resolved['template'];

        $rider = Riders::query()->find($this->riderId);
        $context = $contexts->build($this->triggerKey, [
            'rider' => $this->riderSnapshot,
            'rider_model' => $rider,
            'rider_id' => $this->riderId,
            'company_id' => $this->companyId,
            'company_name' => $this->riderSnapshot['company_name'] ?? null,
        ]);
        if ($context === null) {
            Log::info('whatsapp.rider_activity_skipped', [
                'rider_id' => $this->riderId,
                'trigger' => $this->triggerKey,
                'reason' => 'no_context_builder',
            ]);

            return;
        }

        $to = $context->get('rider_phone');
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            Log::info('whatsapp.rider_activity_skipped', [
                'rider_id' => $this->riderId,
                'reason' => 'invalid_phone',
            ]);

            return;
        }

        // Missing template injects are sent as "-" (same as drawer preview). One pass.
        $payload = $injector->previewPayload($template, $context, '-');
        if (! ($payload['ok'] ?? false)) {
            Log::info('whatsapp.rider_activity_skipped', [
                'rider_id' => $this->riderId,
                'reason' => 'inject_failed',
                'error' => $payload['error'] ?? null,
                'missing_injects' => $payload['missing_injects'] ?? [],
            ]);

            return;
        }

        $connectionId = $this->connectionId > 0
            ? $this->connectionId
            : (isset($resolved['connection_id']) ? (int) $resolved['connection_id'] : null);
        if (! $connectionId || $connectionId <= 0) {
            Log::warning('whatsapp.rider_activity_failed', [
                'rider_id' => $this->riderId,
                'reason' => 'whatsapp_connection_required',
            ]);

            return;
        }

        $prepared = $connections->prepareSystemConnection($connectionId, $this->companyId);
        if (! ($prepared['ready'] ?? false) || ! ($prepared['connection'] instanceof WhatsAppConnection)) {
            Log::warning('whatsapp.rider_activity_failed', [
                'rider_id' => $this->riderId,
                'reason' => $prepared['message'] ?? 'connection_not_ready',
            ]);

            return;
        }

        /** @var WhatsAppConnection $connection */
        $connection = $prepared['connection'];

        $preview = trim((string) ($payload['text'] ?? ''));
        $useFreeText = false;
        if ($this->preferFreeText && $preview !== '') {
            // Recheck at send time — window may have closed while queued.
            $useFreeText = $inbox->hasOpenWindow($this->companyId, (string) $to, (int) $connection->id);
        }

        $delivery = NotificationDelivery::query()->create([
            'company_id' => $this->companyId,
            'user_notification_id' => null,
            'channel' => CompanyNotificationChannels::CHANNEL_WHATSAPP,
            'to_phone' => $to,
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 1,
            'last_attempt_at' => now(),
            'provider_response' => [
                'source' => 'rider_activity_alert',
                'trigger' => $this->triggerKey,
                'rider_id' => $this->riderId,
                'alert_date' => $alertDate,
                'project_id' => $projectId,
                'fleet_supervisor' => $supervisor,
                'dedupe_key' => $dedupeKey,
                'template_id' => $template->id,
                'prefer_free_text' => $this->preferFreeText,
            ],
        ]);

        $templateName = (string) ($payload['template_name'] ?? $template->name);
        $templateLanguage = (string) ($payload['language'] ?? ($template->language ?: 'en'));
        $components = $payload['components'] ?? [];

        if ($useFreeText) {
            $result = $client->sendText($connection, $to, $preview);
            $mode = 'text';
            $downgraded = true;
        } else {
            $result = $client->sendTemplate(
                $connection,
                $to,
                $templateName,
                $templateLanguage,
                is_array($components) ? $components : []
            );
            $mode = 'template';
            $downgraded = false;
        }

        $ok = (bool) ($result['ok'] ?? false);
        $delivery->forceFill($ok
            ? [
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => array_merge($delivery->provider_response ?? [], [
                    'ok' => true,
                    'mode' => $mode,
                    'downgraded_from_template' => $downgraded,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result['response'] ?? null,
                    'connection_id' => $connection->id,
                ]),
            ]
            : [
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => array_merge($delivery->provider_response ?? [], [
                    'mode' => $mode,
                    'downgraded_from_template' => $downgraded,
                    'error' => $result['error'] ?? 'send_failed',
                    'response' => $result['response'] ?? null,
                ]),
            ]
        )->save();

        $messages->recordOutbound([
            'company_id' => $this->companyId,
            'whatsapp_connection_id' => $connection->id,
            'phone_number_id' => $connection->phone_number_id,
            'peer_phone' => $to,
            'wa_message_id' => $result['message_id'] ?? null,
            'message_type' => $mode,
            'body' => $preview !== '' ? $preview : null,
            'template_name' => $mode === 'template' ? $templateName : null,
            'template_language' => $mode === 'template' ? $templateLanguage : null,
            'components' => $mode === 'template' ? (is_array($components) ? $components : null) : null,
            'status' => $ok ? WhatsAppMessage::STATUS_SENT : WhatsAppMessage::STATUS_FAILED,
            'notification_delivery_id' => $delivery->id,
            'source' => 'rider_activity_alert',
            'trigger_key' => $this->triggerKey,
            'participant' => $rider,
            'user_id' => $this->actingUserId && $this->actingUserId > 0 ? $this->actingUserId : null,
            'payload' => [
                'result' => $result['response'] ?? null,
                'error' => $ok ? null : ($result['error'] ?? null),
                'downgraded_from_template' => $downgraded,
            ],
        ]);

        if (! $ok) {
            throw new \RuntimeException((string) ($result['error'] ?? 'Rider activity WhatsApp send failed.'));
        }
    }
}
