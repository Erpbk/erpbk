<?php

namespace App\Services;

use App\Models\Bikes;
use App\Models\Riders;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected $nodeServiceUrl;
    protected $isEnabled;

    public function __construct()
    {
        $this->nodeServiceUrl = rtrim(env('WHATSAPP_NODE_SERVICE_URL', 'http://localhost:3000'), '/');
        $this->isEnabled = env('WHATSAPP_NOTIFICATIONS_ENABLED', false);
    }

    /**
     * Send bike assignment notification to WhatsApp group
     */
    public function sendBikeAssignmentNotification(Bikes $bike, Riders $rider, $assignmentDate = null, $assignedBy = null)
    {
        if (!$this->isEnabled) {
            Log::info('WhatsApp notifications are disabled');
            return false;
        }

        try {
            $message = $this->formatBikeAssignmentMessage($bike, $rider, $assignmentDate, $assignedBy);

            return $this->sendToNodeService([
                'type' => 'bike_assignment',
                'message' => $message,
                'data' => [
                    'rider_id' => $rider->id,
                    'rider_name' => $rider->name,
                    'bike_plate' => $bike->plate,
                    'bike_id' => $bike->id,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('WhatsApp notification failed: ' . $e->getMessage(), [
                'bike_id' => $bike->id,
                'rider_id' => $rider->id,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Format bike assignment message for WhatsApp
     */
    protected function formatBikeAssignmentMessage(Bikes $bike, Riders $rider, $assignmentDate = null, $assignedBy = null)
    {
        $assignmentDate = $assignmentDate ?? now();
        $formattedDate = $assignmentDate->format('d-m-y');
        $formattedTime = $assignmentDate->format('h:i A');

        // Load relationships
        $bike->load('leasingCompany');
        $rider->load('customer', 'vendor');

        // Simple format as requested
        $message = "Bike  🏍\n";
        $message .= "Bike No : {$bike->plate}\n";

        // Noon ID - using rider_id or noon_no if available
        $noonId = $rider->noon_no ?? $rider->rider_id ?? 'N/A';
        $message .= "Noon I,d : {$noonId}\n";

        $message .= "Name : {$rider->name}\n";
        $message .= "Date : {$formattedDate}\n";
        $message .= "Time: {$formattedTime}\n";

        // Note - optional, can be customized
        $note = "Give to {$rider->name}";
        $message .= "Note : {$note}\n";

        // Project - using customer name
        $project = $rider->customer ? $rider->customer->name : 'N/A';
        $message .= "Project : {$project}\n";

        // Emirates - using emirate_hub
        $emirates = $rider->emirate_hub ?? 'N/A';
        $message .= "Emirates : {$emirates}";

        return $message;
    }

    /**
     * Send request to Node.js microservice
     */
    protected function sendToNodeService(array $data)
    {
        try {
            $response = Http::timeout(10)
                ->post($this->nodeServiceUrl . '/api/send-message', $data);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'type' => $data['type'],
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('WhatsApp service returned error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('Failed to connect to WhatsApp Node service', [
                'url' => $this->nodeServiceUrl,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if WhatsApp service is available
     */
    public function checkServiceHealth()
    {
        try {
            $response = Http::timeout(5)
                ->get($this->nodeServiceUrl . '/api/health');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get WhatsApp session status
     */
    public function getSessionStatus()
    {
        try {
            $response = Http::timeout(5)
                ->get($this->nodeServiceUrl . '/api/status');

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp session status: ' . $e->getMessage());
            return null;
        }
    }
}
