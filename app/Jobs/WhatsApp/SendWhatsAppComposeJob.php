<?php

namespace App\Jobs\WhatsApp;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppCloudApiClient;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppMessageStore;
use App\Support\PhoneNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppComposeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  list<string>  $templateParams
     */
    public function __construct(
        public int $companyId,
        public int $userId,
        public int $connectionId,
        public string $toPhone,
        public string $mode,
        public ?string $body = null,
        public ?string $templateName = null,
        public string $templateLanguage = 'en',
        public array $templateParams = [],
        public bool $asAdmin = false,
        public ?string $participantType = null,
        public ?int $participantId = null,
    ) {
    }

    public function handle(
        WhatsAppConnectionService $connections,
        WhatsAppCloudApiClient $client,
        WhatsAppMessageStore $messages
    ): void {
        $to = PhoneNumber::toWhatsAppDigits($this->toPhone);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            Log::warning('whatsapp.compose_invalid_phone', [
                'company_id' => $this->companyId,
                'to' => $this->toPhone,
            ]);

            return;
        }

        if ($this->asAdmin || $this->userId <= 0) {
            $prepared = $connections->prepareConnectionById($this->connectionId);
        } else {
            $user = User::query()->find($this->userId);
            if (! $user) {
                return;
            }
            $prepared = $connections->prepareUserConnection($user, $this->connectionId);
        }

        if (! ($prepared['ready'] ?? false) || ! ($prepared['connection'] instanceof WhatsAppConnection)) {
            Log::warning('whatsapp.compose_connection_not_ready', [
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'as_admin' => $this->asAdmin,
                'message' => $prepared['message'] ?? null,
            ]);

            return;
        }

        /** @var WhatsAppConnection $connection */
        $connection = $prepared['connection'];

        $deliveryCompanyId = $this->companyId > 0
            ? $this->companyId
            : (int) ($connection->company_id ?? 0);

        // notification_deliveries.company_id is required; use 0 for unassigned platform default sends.
        $delivery = NotificationDelivery::query()->create([
            'company_id' => $deliveryCompanyId > 0 ? $deliveryCompanyId : 0,
            'user_notification_id' => null,
            'channel' => CompanyNotificationChannels::CHANNEL_WHATSAPP,
            'to_phone' => $to,
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 1,
            'last_attempt_at' => now(),
            'provider_response' => [
                'mode' => $this->mode,
                'source' => $this->asAdmin ? 'admin_compose' : 'compose',
                'user_id' => $this->userId > 0 ? $this->userId : null,
                'connection_id' => $connection->id,
            ],
        ]);

        $components = [];
        if ($this->mode === 'template') {
            if ($this->templateParams !== []) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $param) => ['type' => 'text', 'text' => $param],
                        array_values($this->templateParams)
                    ),
                ];
            }
            $result = $client->sendTemplate(
                $connection,
                $to,
                (string) $this->templateName,
                $this->templateLanguage ?: 'en',
                $components
            );
        } else {
            $result = $client->sendText($connection, $to, (string) $this->body);
        }

        $ok = (bool) ($result['ok'] ?? false);
        $delivery->forceFill($ok
            ? [
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'provider_response' => array_merge($delivery->provider_response ?? [], [
                    'ok' => true,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result['response'] ?? null,
                ]),
            ]
            : [
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider_response' => array_merge($delivery->provider_response ?? [], [
                    'error' => $result['error'] ?? 'send_failed',
                    'response' => $result['response'] ?? null,
                ]),
            ]
        )->save();

        $messages->recordOutbound([
            'company_id' => $deliveryCompanyId > 0 ? $deliveryCompanyId : null,
            'whatsapp_connection_id' => $connection->id,
            'phone_number_id' => $connection->phone_number_id,
            'peer_phone' => $to,
            'wa_message_id' => $result['message_id'] ?? null,
            'message_type' => $this->mode === 'template' ? 'template' : 'text',
            'body' => $this->mode === 'template'
                ? (($this->templateName ?: 'template').($this->templateParams !== [] ? ' · '.implode(' · ', $this->templateParams) : ''))
                : (string) $this->body,
            'template_name' => $this->mode === 'template' ? $this->templateName : null,
            'template_language' => $this->mode === 'template' ? ($this->templateLanguage ?: 'en') : null,
            'components' => $components !== [] ? $components : null,
            'status' => $ok ? WhatsAppMessage::STATUS_SENT : WhatsAppMessage::STATUS_FAILED,
            'notification_delivery_id' => $delivery->id,
            'source' => $this->asAdmin ? 'admin_compose' : 'compose',
            'user_id' => $this->userId > 0 ? $this->userId : null,
            'participant_type' => $this->participantType,
            'participant_id' => $this->participantId,
            'payload' => [
                'result' => $result['response'] ?? null,
                'error' => $ok ? null : ($result['error'] ?? null),
            ],
        ]);

        if (! $ok) {
            throw new \RuntimeException((string) ($result['error'] ?? 'WhatsApp compose send failed.'));
        }
    }
}
