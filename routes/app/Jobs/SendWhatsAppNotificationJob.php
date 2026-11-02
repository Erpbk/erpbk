<?php

namespace App\Jobs;

use App\Models\Bikes;
use App\Models\Riders;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bikeId;
    public $riderId;
    public $assignmentDate;
    public $assignedBy;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct($bikeId, $riderId, $assignmentDate = null, $assignedBy = null)
    {
        $this->bikeId = $bikeId;
        $this->riderId = $riderId;
        $this->assignmentDate = $assignmentDate;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService)
    {
        try {
            // Fetch fresh models from database
            $bike = Bikes::find($this->bikeId);
            $rider = Riders::find($this->riderId);

            if (!$bike || !$rider) {
                Log::error('Bike or Rider not found for WhatsApp notification', [
                    'bike_id' => $this->bikeId,
                    'rider_id' => $this->riderId
                ]);
                return;
            }

            // Send WhatsApp notification
            $result = $whatsAppService->sendBikeAssignmentNotification(
                $bike,
                $rider,
                $this->assignmentDate,
                $this->assignedBy
            );

            if ($result) {
                Log::info('WhatsApp notification job completed successfully', [
                    'bike_id' => $this->bikeId,
                    'rider_id' => $this->riderId
                ]);
            } else {
                Log::warning('WhatsApp notification job completed but message not sent', [
                    'bike_id' => $this->bikeId,
                    'rider_id' => $this->riderId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp notification job failed', [
                'bike_id' => $this->bikeId,
                'rider_id' => $this->riderId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw exception to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('WhatsApp notification job failed after all retries', [
            'bike_id' => $this->bikeId,
            'rider_id' => $this->riderId,
            'exception' => $exception->getMessage()
        ]);
    }
}
