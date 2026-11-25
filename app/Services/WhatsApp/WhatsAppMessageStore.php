<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageStore
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordOutbound(array $attributes): WhatsAppMessage
    {
        $peerPhone = PhoneNumber::toWhatsAppDigits((string) ($attributes['peer_phone'] ?? ''))
            ?? (string) ($attributes['peer_phone'] ?? '');
        $now = $attributes['occurred_at'] ?? now();
        $waMessageId = trim((string) ($attributes['wa_message_id'] ?? ''));

        if ($waMessageId !== '') {
            $existing = WhatsAppMessage::withoutGlobalScope('company')
                ->where('wa_message_id', $waMessageId)
                ->where('direction', WhatsAppMessage::DIRECTION_OUT)
                ->orderByDesc('id')
                ->first();
            if ($existing) {
                $existing->forceFill([
                    'status' => $attributes['status'] ?? $existing->status,
                    'status_at' => $attributes['status_at'] ?? $now,
                    'payload' => $attributes['payload'] ?? $existing->payload,
                ])->save();

                return $existing;
            }
        }

        $message = new WhatsAppMessage();
        $message->forceFill([
            'company_id' => $attributes['company_id'] ?? null,
            'whatsapp_connection_id' => $attributes['whatsapp_connection_id'] ?? null,
            'phone_number_id' => $attributes['phone_number_id'] ?? null,
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'peer_phone' => $peerPhone,
            'wa_message_id' => $waMessageId !== '' ? $waMessageId : null,
            'message_type' => $attributes['message_type'] ?? null,
            'body' => $attributes['body'] ?? null,
            'media_id' => $attributes['media_id'] ?? null,
            'media_mime' => $attributes['media_mime'] ?? null,
            'media_filename' => $attributes['media_filename'] ?? null,
            'media_path' => $attributes['media_path'] ?? null,
            'media_size' => $attributes['media_size'] ?? null,
            'media_sha256' => $attributes['media_sha256'] ?? null,
            'media_cached_at' => $attributes['media_cached_at'] ?? null,
            'template_name' => $attributes['template_name'] ?? null,
            'template_language' => $attributes['template_language'] ?? null,
            'components' => $attributes['components'] ?? null,
            'status' => $attributes['status'] ?? WhatsAppMessage::STATUS_PENDING,
            'status_at' => $attributes['status_at'] ?? $now,
            'notification_delivery_id' => $attributes['notification_delivery_id'] ?? null,
            'source' => $attributes['source'] ?? null,
            'is_system' => (bool) ($attributes['is_system'] ?? false),
            'trigger_key' => $attributes['trigger_key'] ?? null,
            'user_id' => $attributes['user_id'] ?? $attributes['sent_by_user_id'] ?? null,
            'participant_conflict' => $attributes['participant_conflict'] ?? null,
            'payload' => $attributes['payload'] ?? null,
            'occurred_at' => $now,
        ]);

        $participant = $attributes['participant'] ?? null;
        if ($participant instanceof Model) {
            $message->participant()->associate($participant);
        } elseif (! empty($attributes['participant_type']) && ! empty($attributes['participant_id'])) {
            $message->participant_type = $attributes['participant_type'];
            $message->participant_id = $attributes['participant_id'];
        }
        // Do not inherit "latest on this phone" — same number can belong to
        // different participants over time (separate chat threads).

        $message->save();

        return $message;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordInbound(array $attributes): ?WhatsAppMessage
    {
        $waMessageId = trim((string) ($attributes['wa_message_id'] ?? ''));
        if ($waMessageId !== '') {
            $exists = WhatsAppMessage::withoutGlobalScope('company')
                ->where('wa_message_id', $waMessageId)
                ->exists();
            if ($exists) {
                return null;
            }
        }

        $occurredAt = $attributes['occurred_at'] ?? now();
        $peerPhone = PhoneNumber::toWhatsAppDigits((string) ($attributes['peer_phone'] ?? ''))
            ?? (string) ($attributes['peer_phone'] ?? '');

        $message = new WhatsAppMessage();
        $message->forceFill([
            'company_id' => $attributes['company_id'] ?? null,
            'whatsapp_connection_id' => $attributes['whatsapp_connection_id'] ?? null,
            'phone_number_id' => $attributes['phone_number_id'] ?? null,
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'peer_phone' => $peerPhone,
            'wa_message_id' => $waMessageId !== '' ? $waMessageId : null,
            'message_type' => $attributes['message_type'] ?? null,
            'body' => $attributes['body'] ?? null,
            'media_id' => $attributes['media_id'] ?? null,
            'media_mime' => $attributes['media_mime'] ?? null,
            'media_filename' => $attributes['media_filename'] ?? null,
            'media_path' => $attributes['media_path'] ?? null,
            'media_size' => $attributes['media_size'] ?? null,
            'media_sha256' => $attributes['media_sha256'] ?? null,
            'media_cached_at' => $attributes['media_cached_at'] ?? null,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'status_at' => $occurredAt,
            'source' => $attributes['source'] ?? 'webhook',
            'is_system' => (bool) ($attributes['is_system'] ?? false),
            'user_id' => $attributes['user_id'] ?? null,
            'participant_conflict' => $attributes['participant_conflict'] ?? null,
            'payload' => $attributes['payload'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        $participant = $attributes['participant'] ?? null;
        if ($participant instanceof Model) {
            $message->participant()->associate($participant);
        } elseif (! empty($attributes['participant_type']) && ! empty($attributes['participant_id'])) {
            $message->participant_type = $attributes['participant_type'];
            $message->participant_id = $attributes['participant_id'];
        }

        $message->save();

        return $message;
    }

    /**
     * Local-only system row (no Meta API) — e.g. chat transfer stub.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function recordSystem(array $attributes): WhatsAppMessage
    {
        $attributes['is_system'] = true;
        $attributes['direction'] = $attributes['direction'] ?? WhatsAppMessage::DIRECTION_OUT;
        $attributes['status'] = $attributes['status'] ?? WhatsAppMessage::STATUS_SENT;
        $attributes['message_type'] = $attributes['message_type'] ?? 'system';
        $attributes['source'] = $attributes['source'] ?? WhatsAppMessage::SOURCE_CHAT_TRANSFER;

        return $this->recordOutbound($attributes);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function applyStatus(string $waMessageId, string $status, ?array $payload = null): ?WhatsAppMessage
    {
        $waMessageId = trim($waMessageId);
        $status = strtolower(trim($status));
        if ($waMessageId === '' || $status === '') {
            return null;
        }

        $message = WhatsAppMessage::withoutGlobalScope('company')
            ->where('wa_message_id', $waMessageId)
            ->where('direction', WhatsAppMessage::DIRECTION_OUT)
            ->orderByDesc('id')
            ->first();

        if (! $message) {
            return null;
        }

        $mapped = match ($status) {
            'sent' => WhatsAppMessage::STATUS_SENT,
            'delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'read' => WhatsAppMessage::STATUS_READ,
            'failed' => WhatsAppMessage::STATUS_FAILED,
            default => $status,
        };

        $provider = is_array($message->payload) ? $message->payload : [];
        if ($payload !== null) {
            $provider['webhook_status'] = $status;
            $provider['webhook_payload'] = $payload;
        }

        $message->forceFill([
            'status' => $mapped,
            'status_at' => now(),
            'payload' => $provider,
        ])->save();

        return $message;
    }
}
