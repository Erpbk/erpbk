<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the trait and set up model event listeners.
     */
    protected static function bootLogsActivity()
    {
        // Log when a model is created
        static::created(function (Model $model) {
            if (auth()->check()) {
                ActivityLogger::created(static::getModuleName(), $model);
            }
        });

        // Log when a model is updated
        static::updated(function (Model $model) {
            if (auth()->check()) {
                // Get the original attributes before the update
                $oldData = $model->getOriginal();

                // Only log if there are actual changes
                if ($model->wasChanged()) {
                    ActivityLogger::updated(static::getModuleName(), $model, $oldData);
                }
            }
        });

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::forceDeleting(function (Model $model) {
                if (auth()->check()) {
                    ActivityLogger::custom('force_deleted', static::getModuleName(), $model);
                }
            });
        }

        // Keep the original deleting event for soft/regular deletes
        static::deleting(function (Model $model) {
            if (auth()->check()) {
                // Skip if this is a force delete (handled above)
                $isForceDelete = $model->isForceDeleting() ?? false;
                if (!$isForceDelete) {
                    ActivityLogger::deleted(static::getModuleName(), $model);
                }
            }
        });

        // Log when a model is restored (for soft deletes) - only if model uses soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                if (auth()->check()) {
                    ActivityLogger::custom('restored', static::getModuleName(), $model);
                }
            });
        }
    }

    /**
     * Get the module name for this model.
     * Override this method in your model if you want a custom module name.
     */
    protected static function getModuleName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Log a custom activity for this model.
     */
    public function logActivity(string $action, array $changes = null): void
    {
        if (auth()->check()) {
            ActivityLogger::custom($action, static::getModuleName(), $this, $changes);
        }
    }

    /**
     * Get all activity logs for this model.
     */
    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class, 'model_id')
            ->where('model_type', static::class);
    }
}
