<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDelivery;
use App\Models\UserNotification;

class InAppChannel implements ChannelInterface
{
    public function key(): string
    {
        return 'in_app';
    }

    public function deliver(UserNotification $notification, NotificationDelivery $delivery): void
    {
        // Inbox row already exists — mark delivery sent.
        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_SENT,
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
            'sent_at' => now(),
            'provider_response' => ['ok' => true],
        ])->save();
    }
}
