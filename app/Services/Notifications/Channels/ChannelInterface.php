<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationDelivery;
use App\Models\UserNotification;

interface ChannelInterface
{
    public function key(): string;

    public function deliver(UserNotification $notification, NotificationDelivery $delivery): void;
}
