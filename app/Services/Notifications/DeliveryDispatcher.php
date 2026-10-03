<?php

namespace App\Services\Notifications;

use App\Jobs\Notifications\DeliverNotificationChannelJob;
use App\Models\NotificationDelivery;
use App\Models\UserNotification;
use App\Services\Notifications\Channels\ChannelInterface;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\InAppChannel;
use Illuminate\Support\Facades\Log;

class DeliveryDispatcher
{
    /** @var array<string, ChannelInterface> */
    private array $channels;

    public function __construct()
    {
        $drivers = [
            new InAppChannel(),
            new EmailChannel(),
        ];
        $this->channels = [];
        foreach ($drivers as $driver) {
            $this->channels[$driver->key()] = $driver;
        }
    }

    /**
     * @param  list<string>  $channels
     */
    public function dispatch(UserNotification $notification, array $channels): void
    {
        $companyId = (int) $notification->company_id;
        $entitled = CompanyNotificationChannels::enabledFor($companyId);

        foreach ($channels as $channel) {
            if (! in_array($channel, $entitled, true)) {
                Log::info('notification.skipped_channel_not_entitled', [
                    'notification_id' => $notification->id,
                    'channel' => $channel,
                    'company_id' => $companyId,
                ]);
                continue;
            }

            if (! isset($this->channels[$channel])) {
                continue;
            }

            $delivery = NotificationDelivery::query()->firstOrCreate(
                [
                    'user_notification_id' => $notification->id,
                    'channel' => $channel,
                ],
                [
                    'company_id' => $companyId,
                    'status' => NotificationDelivery::STATUS_PENDING,
                    'attempts' => 0,
                ]
            );

            if (in_array($delivery->status, [NotificationDelivery::STATUS_SENT, NotificationDelivery::STATUS_SKIPPED], true)) {
                continue;
            }

            if ($channel === 'in_app') {
                $this->channels[$channel]->deliver($notification, $delivery);
                continue;
            }

            DeliverNotificationChannelJob::dispatch($delivery->id)->onQueue('notifications');
        }
    }

    public function deliverNow(NotificationDelivery $delivery): void
    {
        $channel = $this->channels[$delivery->channel] ?? null;
        if (! $channel) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => ['reason' => 'unknown_channel'],
            ])->save();

            return;
        }

        $notification = $delivery->notification;
        if (! $notification) {
            return;
        }

        if (! CompanyNotificationChannels::isEnabled((int) $notification->company_id, $delivery->channel)) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'provider_response' => ['reason' => 'channel_not_entitled'],
            ])->save();

            return;
        }

        $channel->deliver($notification, $delivery);
    }
}
