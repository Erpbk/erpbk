<?php

namespace App\Listeners;

use App\Events\BikeAssignedEvent;
use App\Jobs\SendWhatsAppNotificationJob;
use Illuminate\Support\Facades\Log;

class SendBikeAssignmentNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BikeAssignedEvent $event)
    {
        // Check if WhatsApp notifications are enabled
        if (!env('WHATSAPP_NOTIFICATIONS_ENABLED', false)) {
            Log::info('WhatsApp notifications are disabled - skipping');
            return;
        }

        // Dispatch job to send WhatsApp notification
        SendWhatsAppNotificationJob::dispatch(
            $event->bike->id,
            $event->rider->id,
            $event->assignmentDate,
            $event->assignedBy
        )->onQueue('notifications');

        Log::info('WhatsApp notification job dispatched', [
            'bike_id' => $event->bike->id,
            'rider_id' => $event->rider->id
        ]);
    }
}
