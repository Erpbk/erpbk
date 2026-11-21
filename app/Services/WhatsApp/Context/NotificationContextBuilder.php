<?php

namespace App\Services\WhatsApp\Context;

use App\Models\Company;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\WhatsApp\WhatsAppSendContext;

/**
 * Context for notification:{type_key} triggers.
 */
class NotificationContextBuilder implements WhatsAppContextBuilder
{
    public function provides(): array
    {
        return [
            'notification_title',
            'notification_body',
            'company_name',
            'recipient_name',
            'recipient_phone',
            'cheque_number',
            'amount',
            'cheque_date',
        ];
    }

    public function build(array $payload): WhatsAppSendContext
    {
        $notification = $payload['notification'] ?? null;
        $user = $payload['user'] ?? null;
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($notification instanceof UserNotification) {
            $data = array_merge(is_array($notification->data) ? $notification->data : [], $data);
            if (! $user instanceof User && $notification->user_id) {
                $user = User::query()->find($notification->user_id);
            }
        }

        $companyId = (int) ($payload['company_id']
            ?? ($notification instanceof UserNotification ? $notification->company_id : 0)
            ?? 0);
        $companyName = $payload['company_name'] ?? null;
        if (($companyName === null || $companyName === '') && $companyId > 0) {
            $companyName = Company::query()->whereKey($companyId)->value('name');
        }

        $title = $payload['notification_title']
            ?? ($notification instanceof UserNotification ? $notification->title : null);
        $body = $payload['notification_body']
            ?? ($notification instanceof UserNotification ? $notification->body : null);

        return WhatsAppSendContext::fromArray([
            'notification_title' => $title,
            'notification_body' => $body,
            'company_name' => $companyName,
            'recipient_name' => $payload['recipient_name'] ?? ($user instanceof User ? $user->name : null),
            'recipient_phone' => $payload['recipient_phone'] ?? $payload['to'] ?? null,
            'cheque_number' => $data['cheque_number'] ?? $payload['cheque_number'] ?? null,
            'amount' => isset($data['amount']) ? (string) $data['amount'] : ($payload['amount'] ?? null),
            'cheque_date' => $data['cheque_date'] ?? $payload['cheque_date'] ?? null,
        ]);
    }
}
