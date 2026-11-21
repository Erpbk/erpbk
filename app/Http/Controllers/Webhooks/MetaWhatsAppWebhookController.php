<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppUnknownInboxSetting;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\WhatsApp\WhatsAppMessageStore;
use App\Services\WhatsApp\WhatsAppParticipantResolver;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $expected = (string) config('whatsapp.verify_token', '');
        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(
        Request $request,
        WhatsAppMessageStore $messages,
        WhatsAppParticipantResolver $resolver
    ) {
        if (! $this->signatureValid($request)) {
            Log::warning('whatsapp.webhook_invalid_signature');

            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return response()->json(['ok' => true]);
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }
                $this->processStatuses($value, $messages);
                $this->processInbound($value, $messages, $resolver);
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function processStatuses(array $value, WhatsAppMessageStore $messages): void
    {
        $statuses = $value['statuses'] ?? [];
        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $status) {
            if (! is_array($status)) {
                continue;
            }
            $messageId = (string) ($status['id'] ?? '');
            $statusName = strtolower((string) ($status['status'] ?? ''));
            if ($messageId === '' || $statusName === '') {
                continue;
            }

            $messages->applyStatus($messageId, $statusName, $status);

            $delivery = NotificationDelivery::withoutGlobalScope('company')
                ->where('channel', CompanyNotificationChannels::CHANNEL_WHATSAPP)
                ->where('provider_response->message_id', $messageId)
                ->orderByDesc('id')
                ->first();

            if (! $delivery) {
                continue;
            }

            $provider = is_array($delivery->provider_response) ? $delivery->provider_response : [];
            $provider['webhook_status'] = $statusName;
            $provider['webhook_payload'] = $status;

            $updates = ['provider_response' => $provider];
            if (in_array($statusName, ['sent', 'delivered', 'read'], true)) {
                if ($delivery->status !== NotificationDelivery::STATUS_SENT) {
                    $updates['status'] = NotificationDelivery::STATUS_SENT;
                }
                if (! $delivery->sent_at) {
                    $updates['sent_at'] = now();
                }
            } elseif ($statusName === 'failed') {
                $updates['status'] = NotificationDelivery::STATUS_FAILED;
                $provider['error'] = data_get($status, 'errors.0.title')
                    ?? data_get($status, 'errors.0.message')
                    ?? 'webhook_failed';
                $updates['provider_response'] = $provider;
            }

            $delivery->forceFill($updates)->save();
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function processInbound(
        array $value,
        WhatsAppMessageStore $messages,
        WhatsAppParticipantResolver $resolver
    ): void {
        $inboundMessages = $value['messages'] ?? [];
        if (! is_array($inboundMessages) || $inboundMessages === []) {
            return;
        }

        $metadata = is_array($value['metadata'] ?? null) ? $value['metadata'] : [];
        $phoneNumberId = (string) ($metadata['phone_number_id'] ?? '');
        $connection = null;
        if ($phoneNumberId !== '') {
            $connection = WhatsAppConnection::query()
                ->where('phone_number_id', $phoneNumberId)
                ->first();
        }

        foreach ($inboundMessages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $from = PhoneNumber::toWhatsAppDigits((string) ($message['from'] ?? ''));
            if (! PhoneNumber::isValidWhatsAppDigits($from)) {
                continue;
            }

            $type = (string) ($message['type'] ?? 'unknown');
            $body = null;
            if ($type === 'text') {
                $body = (string) data_get($message, 'text.body', '');
            } elseif ($type === 'button') {
                $body = (string) data_get($message, 'button.text', '');
            } elseif ($type === 'interactive') {
                $body = (string) (
                    data_get($message, 'interactive.button_reply.title')
                    ?? data_get($message, 'interactive.list_reply.title')
                    ?? ''
                );
            }

            $timestamp = isset($message['timestamp']) ? (int) $message['timestamp'] : null;
            $companyId = $connection?->company_id ? (int) $connection->company_id : null;

            $resolved = [
                'participant' => null,
                'participant_type' => null,
                'participant_id' => null,
                'user_id' => null,
                'participant_conflict' => null,
            ];
            try {
                $resolved = $resolver->resolve(
                    $companyId,
                    (string) $from,
                    $connection?->id ? (int) $connection->id : null
                );
            } catch (\Throwable $e) {
                Log::error('whatsapp.webhook_participant_resolve_failed', [
                    'peer_phone' => $from,
                    'company_id' => $companyId,
                    'connection_id' => $connection?->id,
                    'error' => $e->getMessage(),
                ]);

                // Still apply the configured unknown-inbound assignee so the
                // message is owned even when registry lookup fails.
                if ($companyId && $connection?->id && empty($resolved['user_id'])) {
                    $fallback = WhatsAppUnknownInboxSetting::withoutGlobalScope('company')
                        ->where('company_id', $companyId)
                        ->where('whatsapp_connection_id', (int) $connection->id)
                        ->value('user_id');
                    if ($fallback) {
                        $resolved['user_id'] = (int) $fallback;
                    }
                }
            }

            try {
                $messages->recordInbound([
                    'company_id' => $companyId,
                    'whatsapp_connection_id' => $connection?->id,
                    'phone_number_id' => $phoneNumberId !== '' ? $phoneNumberId : null,
                    'peer_phone' => $from,
                    'wa_message_id' => (string) ($message['id'] ?? ''),
                    'message_type' => $type,
                    'body' => $body !== '' ? $body : null,
                    'payload' => $message,
                    'occurred_at' => $timestamp ? now()->setTimestamp($timestamp) : now(),
                    'source' => 'webhook',
                    'user_id' => $resolved['user_id'],
                    'participant' => $resolved['participant'],
                    'participant_type' => $resolved['participant_type'],
                    'participant_id' => $resolved['participant_id'],
                    'participant_conflict' => $resolved['participant_conflict'],
                ]);
            } catch (\Throwable $e) {
                Log::error('whatsapp.webhook_inbound_persist_failed', [
                    'peer_phone' => $from,
                    'wa_message_id' => (string) ($message['id'] ?? ''),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function signatureValid(Request $request): bool
    {
        $secret = (string) config('whatsapp.app_secret', '');
        if ($secret === '') {
            // Allow local/dev without secret; production should set META_WHATSAPP_APP_SECRET.
            return app()->environment(['local', 'testing']);
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }
}
