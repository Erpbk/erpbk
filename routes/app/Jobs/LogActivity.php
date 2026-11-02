<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $action;
    public $moduleName;
    public $modelType;
    public $modelId;
    public $changes;
    public $ipAddress;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $action, $moduleName, $modelType = null, $modelId = null, $changes = null, $ipAddress = null)
    {
        $this->userId = $userId;
        $this->action = $action;
        $this->moduleName = $moduleName;
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->changes = $changes;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ActivityLog::create([
            'user_id' => $this->userId,
            'action' => $this->action,
            'module_name' => $this->moduleName,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'changes' => $this->changes,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
