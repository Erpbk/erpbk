<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\DeliveryDispatcher;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverNotificationChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(DeliveryDispatcher $dispatcher): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);
        if (! $delivery) {
            return;
        }

        if (in_array($delivery->status, [NotificationDelivery::STATUS_SENT, NotificationDelivery::STATUS_SKIPPED], true)) {
            return;
        }

        try {
            $dispatcher->deliverNow($delivery);
            Log::info('notification.delivered', [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->channel,
                'status' => $delivery->fresh()?->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('notification.delivery_failed', [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->channel,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
