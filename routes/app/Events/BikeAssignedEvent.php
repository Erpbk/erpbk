<?php

namespace App\Events;

use App\Models\Bikes;
use App\Models\Riders;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BikeAssignedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bike;
    public $rider;
    public $assignmentDate;
    public $assignedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Bikes $bike, Riders $rider, $assignmentDate = null, $assignedBy = null)
    {
        $this->bike = $bike;
        $this->rider = $rider;
        $this->assignmentDate = $assignmentDate ?? now();
        $this->assignedBy = $assignedBy;
    }
}
