<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Jobs\LogActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity to the activity_logs table.
     *
     * @param string $action The action performed (e.g., 'created', 'updated', 'deleted')
     * @param string $moduleName The module name (e.g., 'invoices', 'users', 'payments')
     * @param mixed $model The model instance that was affected (optional)
     * @param array|null $changes Array containing 'old' and 'new' values (optional)
     * @return ActivityLog
     */
    public static function log(string $action, string $moduleName, $model = null, $changes = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module_name' => $moduleName,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'changes' => $changes,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log a created action.
     *
     * @param string $moduleName
     * @param mixed $model
     * @return ActivityLog
     */
    public static function created(string $moduleName, $model): ActivityLog
    {
        return self::log('created', $moduleName, $model, ['new' => $model->toArray()]);
    }

    /**
     * Log an updated action.
     *
     * @param string $moduleName
     * @param mixed $model
     * @param array $oldData
     * @return ActivityLog
     */
    public static function updated(string $moduleName, $model, array $oldData): ActivityLog
    {
        return self::log('updated', $moduleName, $model, [
            'old' => $oldData,
            'new' => $model->toArray()
        ]);
    }

    /**
     * Log a deleted action.
     *
     * @param string $moduleName
     * @param mixed $model
     * @return ActivityLog
     */
    public static function deleted(string $moduleName, $model): ActivityLog
    {
        return self::log('deleted', $moduleName, $model, ['old' => $model->toArray()]);
    }

    /**
     * Log a login action.
     *
     * @param mixed $user
     * @return ActivityLog
     */
    public static function login($user): ActivityLog
    {
        return self::log('logged_in', 'authentication', $user);
    }

    /**
     * Log a logout action.
     *
     * @param mixed $user
     * @return ActivityLog
     */
    public static function logout($user): ActivityLog
    {
        return self::log('logged_out', 'authentication', $user);
    }

    /**
     * Log a custom action.
     *
     * @param string $action
     * @param string $moduleName
     * @param mixed $model
     * @param array|null $changes
     * @return ActivityLog
     */
    public static function custom(string $action, string $moduleName, $model = null, $changes = null): ActivityLog
    {
        return self::log($action, $moduleName, $model, $changes);
    }

    /**
     * Log an activity asynchronously using a queued job.
     *
     * @param string $action
     * @param string $moduleName
     * @param mixed $model
     * @param array|null $changes
     * @return void
     */
    public static function logAsync(string $action, string $moduleName, $model = null, $changes = null): void
    {
        LogActivity::dispatch(
            Auth::id(),
            $action,
            $moduleName,
            $model ? get_class($model) : null,
            $model?->id,
            $changes,
            Request::ip()
        );
    }

    /**
     * Log a created action asynchronously.
     *
     * @param string $moduleName
     * @param mixed $model
     * @return void
     */
    public static function createdAsync(string $moduleName, $model): void
    {
        self::logAsync('created', $moduleName, $model, ['new' => $model->toArray()]);
    }

    /**
     * Log an updated action asynchronously.
     *
     * @param string $moduleName
     * @param mixed $model
     * @param array $oldData
     * @return void
     */
    public static function updatedAsync(string $moduleName, $model, array $oldData): void
    {
        self::logAsync('updated', $moduleName, $model, [
            'old' => $oldData,
            'new' => $model->toArray()
        ]);
    }

    /**
     * Log a deleted action asynchronously.
     *
     * @param string $moduleName
     * @param mixed $model
     * @return void
     */
    public static function deletedAsync(string $moduleName, $model): void
    {
        self::logAsync('deleted', $moduleName, $model, ['old' => $model->toArray()]);
    }
}
