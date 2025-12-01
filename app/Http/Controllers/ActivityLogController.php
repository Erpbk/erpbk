<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:activity_logs_view')->only(['index', 'show']);
        $this->middleware('permission:activity_logs_delete')->only(['destroy']);
    }
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->filled('module_name')) {
            $query->forModule($request->module_name);
        }

        if ($request->filled('action')) {
            $query->forAction($request->action);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Get unique values for filter dropdowns
        $users = User::select('id', 'name')->orderBy('name')->get();
        $modules = ActivityLog::select('module_name')
            ->distinct()
            ->orderBy('module_name')
            ->pluck('module_name');

        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        // Paginate results - Changed to descending order by created_at
        $activityLogs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('activity_logs.index', compact(
            'activityLogs',
            'users',
            'modules',
            'actions'
        ));
    }

    /**
     * Show the specified activity log.
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user');

        // Process changes to highlight differences
        if ($activityLog->changes && isset($activityLog->changes['old']) && isset($activityLog->changes['new'])) {
            $highlightedChanges = $this->highlightChanges($activityLog->changes['old'], $activityLog->changes['new']);
            $activityLog->highlighted_changes = $highlightedChanges;

            // Filter to only show changed fields
            $activityLog->changed_fields = array_filter($highlightedChanges, function ($change) {
                return $change['changed'] === true;
            });
        }

        return view('activity_logs.show', compact('activityLog'));
    }

    /**
     * Get activity log statistics.
     */
    public function statistics(Request $request)
    {
        $query = ActivityLog::query();

        // Apply date filter if provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        $stats = [
            'total_activities' => (clone $query)->count(),

            'activities_by_action' => (clone $query)
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->orderByDesc('count')
                ->get(),

            'activities_by_module' => (clone $query)
                ->select('module_name', DB::raw('COUNT(*) as count'))
                ->groupBy('module_name')
                ->orderByDesc('count')
                ->get(),

            'activities_by_user' => (clone $query)
                ->with('user')
                ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_activity'))
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            'recent_activities' => (clone $query)
                ->with('user')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    $activity->user_name = $activity->user?->name ?? 'System';
                    return $activity;
                }),
        ];


        return response()->json($stats);
    }

    /**
     * Highlight changes between old and new values.
     * 
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    private function highlightChanges(array $oldValues, array $newValues): array
    {
        $highlightedChanges = [];

        // Process all fields in both old and new values
        $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allFields as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Skip if both values are arrays or objects
            if (is_array($newValue) || is_array($oldValue)) {
                $highlightedChanges[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'changed' => $this->hasArrayChanged($oldValue, $newValue)
                ];
                continue;
            }

            // Convert to string for comparison
            $oldValueStr = (string)$oldValue;
            $newValueStr = (string)$newValue;

            $highlightedChanges[$key] = [
                'old' => $oldValueStr,
                'new' => $newValueStr,
                'changed' => $oldValueStr !== $newValueStr
            ];
        }

        return $highlightedChanges;
    }

    /**
     * Check if array values have changed
     * 
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    private function hasArrayChanged($oldValue, $newValue): bool
    {
        // If one is array and other is not, they're different
        if (is_array($oldValue) !== is_array($newValue)) {
            return true;
        }

        // If both are arrays, compare serialized versions
        if (is_array($oldValue) && is_array($newValue)) {
            return json_encode($oldValue) !== json_encode($newValue);
        }

        // Compare as strings
        return (string)$oldValue !== (string)$newValue;
    }
}
