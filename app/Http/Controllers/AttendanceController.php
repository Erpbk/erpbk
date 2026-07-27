<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Customers;
use App\Models\Employee;
use App\Models\RiderActivities;
use App\Models\Riders;
use App\Services\Attendance\RiderAttendanceActivitySync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:employees_attendance_view|riders_attendance_view')->only('index', 'summary');
        $this->middleware('permission:employees_attendance_create|riders_attendance_create')->only('create', 'store');
        $this->middleware('permission:employees_attendance_edit|riders_attendance_edit')->only('edit', 'update');
        $this->middleware('permission:employees_attendance_delete|riders_attendance_delete')->only('destroy');
    }
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request)
    {
        if (! \App\Support\RoleFieldAccess::canAccessModule('attendance')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Attendance::with('user');

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('date', $request->date);
        }

        // Filter by reference type
        if ($request->has('ref_type') && $request->ref_type != '') {
            $query->where('ref_type', $request->ref_type);
        }

        if ($request->has('ref_id') && $request->ref_id != '') {
            $query->where('ref_id', $request->ref_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if (!$request->has('date') && !$request->has('from_date') && !$request->has('to_date')) {
            $query->currentMonth();
        }

        $query->orderBy('date', 'desc');

        $attendances = $query->get();

        Riders::hydrateEmploymentStatusDays(
            $attendances->where('ref_type', 'rider')->map->user
        );

        return view('attendance.index', compact('attendances'));
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create($company_slug, $refType)
    {
        if ($refType) {
            $refTypes = $refType;
            $refId = request()->get('ref_id');
            $date = request()->get('date', date('Y-m-d'));
            return view('attendance.create', compact('refTypes', 'refId', 'date'));
        }
        return view('attendance.create');
    }

    /**
     * Store a newly created attendance record.
     */
    public function store(Request $request)
    {
        $rules = [
            'ref_type' => 'required|in:employee,rider',
            'ref_id' => 'required',
            'date' => 'required|date',
            'check_in' => 'nullable',
            'check_out' => 'nullable|after:check_in',
            'status' => 'required|in:present,absent,late,half day,weekend,on leave',
            'notes' => 'nullable|string|max:500'
        ];
        if ($request->ref_type === 'employee') {
            $rules['ref_id'] .= '|exists:employees,id';
        } else {
            $rules['ref_id'] .= '|exists:riders,id';
        }
        if ($request->status === 'present' || $request->status === 'late' || $request->status === 'half day') {
            $rules['check_in'] = 'required';
        }
        if ($request->ref_type === 'rider') {
            $rules = array_merge($rules, $this->riderAttendanceMetricRules());
        }
        $validated = $request->validate($rules);
        // Validate that the reference ID exists in the appropriate table
        if ($validated['ref_type'] === 'employee') {
            $exists = Employee::where('id', $validated['ref_id'])->exists();
            $typeName = 'Employee';
        } else {
            $exists = Riders::where('id', $validated['ref_id'])->exists();
            $typeName = 'Rider';
        }

        if (!$exists) {
            return response()->json(['success' => false, 'message' => "Selected {$typeName} does not exist."]);
        }

        // Check if attendance already exists for this user on this date
        $existing = Attendance::where('ref_id', $validated['ref_id'])
            ->where('ref_type', $validated['ref_type'])
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Attendance record already exists for this user on this date.']);
        }

        $validated['branch_id'] = $this->resolveBranchIdForAttendance(
            $validated['ref_type'],
            (int) $validated['ref_id']
        );

        $attendance = Attendance::create($validated);

        if ($validated['ref_type'] === 'rider') {
            RiderAttendanceActivitySync::syncActivityFromAttendance(
                (int) $validated['ref_id'],
                $validated['date'],
                RiderAttendanceActivitySync::syncDataFromAttendance($attendance, $validated)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance record created successfully.',
        ]);
    }

    /**
     * Display the specified attendance record.
     */
    public function show($company_slug, Attendance $attendance)
    {
        $attendance->load('user');
        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record.
     */
    public function edit($company_slug, Attendance $attendance)
    {
        $attendance->load('user');
        $refType = $attendance->ref_type;
        $employees = collect();
        $riders = collect();

        $riderActivity = null;
        if ($refType == 'employee') {
            $employees = Employee::all();
        } else {
            $riders = Riders::all();
            $riderActivity = RiderActivities::where('rider_id', $attendance->ref_id)
                ->whereDate('date', $attendance->date)
                ->first();
        }

        return view('attendance.edit', compact('attendance', 'refType', 'employees', 'riders', 'riderActivity'));
    }

    /**
     * Update the specified attendance record.
     */
    public function update(Request $request, $company_slug, Attendance $attendance)
    {
        $rules = [
            'ref_type' => 'required|in:employee,rider',
            'ref_id' => 'required|integer',
            'date' => 'required|date',
            'check_out' => 'nullable|after:check_in',
            'status' => 'required|in:present,absent,late,half day,weekend,on leave',
            'notes' => 'nullable|string|max:500'
        ];
        if ($request->status === 'present' || $request->status === 'late' || $request->status === 'half day') {
            $rules['check_in'] = 'required';
        } else {
            $rules['check_in'] = 'nullable';
        }
        if ($request->ref_type === 'rider') {
            $rules = array_merge($rules, $this->riderAttendanceMetricRules());
        }
        $validated = $request->validate($rules);

        // Validate that the reference ID exists in the appropriate table
        if ($validated['ref_type'] === 'employee') {
            $exists = Employee::where('id', $validated['ref_id'])->exists();
            $typeName = 'Employee';
        } else {
            $exists = Riders::where('id', $validated['ref_id'])->exists();
            $typeName = 'Rider';
        }

        if (!$exists) {
            return response()->json(['success' => false, 'message' => "Selected {$typeName} does not exist."]);
        }

        // Check if attendance already exists for another record
        $existing = Attendance::where('ref_id', $validated['ref_id'])
            ->where('ref_type', $validated['ref_type'])
            ->whereDate('date', $validated['date'])
            ->where('id', '!=', $attendance->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Another attendance record already exists for this user on this date.'
            ]);
        }

        $validated['branch_id'] = $this->resolveBranchIdForAttendance(
            $validated['ref_type'],
            (int) $validated['ref_id']
        );

        $attendance->update($validated);

        if ($validated['ref_type'] === 'rider') {
            RiderAttendanceActivitySync::syncActivityFromAttendance(
                (int) $validated['ref_id'],
                $validated['date'],
                RiderAttendanceActivitySync::syncDataFromAttendance($attendance, $validated)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance record updated successfully.',
        ]);
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy($company_slug, Attendance $attendance)
    {
        try {
            $attendance->delete();
            return response()->json(['success' => true, 'message' => 'Attendance record deleted successfully.', 'reload' => true]);
        } catch (\Exception $e) {
            \Log::error('Failed to delete attendance record: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete attendance record.']);
        }
    }

    /**
     * Get users based on reference type (for AJAX requests)
     */
    public function getUsers($company_slug, $refType)
    {
        $users = null;
        if ($refType === 'employee') {
            $users = Employee::select('id', 'name')->get();
        } else {
            $users = Riders::select('id', 'name')->get();
        }

        return response()->json($users);
    }

    /**
     * Mark bulk attendance (admin function)
     */
    public function bulkMark(Request $request)
    {
        try {
            $validated = $request->validate([
                'ref_type' => 'required|in:employee,rider',
                'date' => 'required|date',
                'attendances' => 'required|array|min:1',
                'attendances.*.ref_id' => 'required|integer',
                'attendances.*.ref_type' => 'required|in:employee,rider',
                'attendances.*.status' => 'required|in:present,absent,late,half day,on leave,weekend',
                'attendances.*.check_in' => 'nullable',
                'attendances.*.check_out' => 'nullable',
                'attendances.*.notes' => 'nullable|string|max:500',
                'attendances.*.total_orders' => 'nullable|integer|min:0',
                'attendances.*.working_hours' => 'nullable|numeric|min:0',
                'attendances.*.rejected_orders' => 'nullable|integer|min:0',
                'attendances.*.cancelled_orders' => 'nullable|integer|min:0',
                'attendances.*.ontime_orders_percentage' => 'nullable|numeric|min:0|max:100',
            ]);

            $successCount = 0;
            $results = [];

            foreach ($validated['attendances'] as $attendanceData) {
                // Skip if ref_type doesn't match
                if ($attendanceData['ref_type'] !== $validated['ref_type']) {
                    continue;
                }

                // Prepare data
                $data = [
                    'ref_id' => $attendanceData['ref_id'],
                    'ref_type' => $attendanceData['ref_type'],
                    'date' => $validated['date'],
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null,
                ];

                // Add time fields if provided
                if (!empty($attendanceData['check_in'])) {
                    $data['check_in'] = $attendanceData['check_in'];;
                }

                if (!empty($attendanceData['check_out'])) {
                    $data['check_out'] = $attendanceData['check_out'];
                }

                if ($attendanceData['ref_type'] === 'rider') {
                    $metricData = RiderAttendanceActivitySync::metricDataFromRequest($attendanceData);
                    foreach (RiderAttendanceActivitySync::RIDER_METRIC_KEYS as $metricKey) {
                        if (array_key_exists($metricKey, $metricData)) {
                            $data[$metricKey] = $metricData[$metricKey];
                        }
                    }
                }

                $data['branch_id'] = $this->resolveBranchIdForAttendance(
                    $attendanceData['ref_type'],
                    (int) $attendanceData['ref_id']
                );

                // Check if exists
                $existing = Attendance::where([
                    'ref_id' => $attendanceData['ref_id'],
                    'ref_type' => $attendanceData['ref_type'],
                    'date' => $validated['date']
                ])->first();

                // Update or create
                $attendance = Attendance::updateOrCreate(
                    [
                        'ref_id' => $attendanceData['ref_id'],
                        'ref_type' => $attendanceData['ref_type'],
                        'date' => $validated['date']
                    ],
                    $data
                );

                if ($attendanceData['ref_type'] === 'rider') {
                    RiderAttendanceActivitySync::syncActivityFromAttendance(
                        (int) $attendanceData['ref_id'],
                        $validated['date'],
                        RiderAttendanceActivitySync::syncDataFromAttendance($attendance, $attendanceData)
                    );
                }

                $successCount++;

                // Store result for this user
                $results[] = [
                    'user_id' => $attendanceData['ref_id'],
                    'action' => $existing ? 'updated' : 'created',
                    'status' => $attendanceData['status']
                ];
            }

            // Return JSON response for AJAX
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount} attendance records marked successfully.",
                    'count' => $successCount,
                    'results' => $results
                ]);
            }

            // Return redirect response for normal form submission
            return redirect()->back()
                ->with('success', "{$successCount} attendance records marked successfully.");
        } catch (\Exception $e) {
            \Log::error('Error marking bulk attendance: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            // Return JSON error response for AJAX
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server error: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error marking attendance: ' . $e->getMessage())
                ->withInput();
        }
    }


    /**
     * Export attendance to CSV
     */ public function export(Request $request)
    {
        $query = Attendance::with('user');
        if ($request->date) {
            $query->where('date', $request->date);
        } else {
            if ($request->from_date) {
                $query->whereDate('date', '>=', $request->from_date);
            }

            if ($request->to_date) {
                $query->whereDate('date', '<=', $request->to_date);
            }
        }
        if ($request->ref_type) {
            $query->where('ref_type', $request->ref_type);
        }
        if ($request->ref_id) {
            $query->where('ref_id', $request->ref_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        $filename = 'attendance_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($attendances) {

            $handle = fopen('php://output', 'w');

            if ($attendances->isEmpty()) {

                fputcsv($handle, ['No records found for the selected criteria.']);
            } else {

                fputcsv($handle, ['Type', 'ID', 'Name', 'Date', 'Check In', 'Check Out', 'Status', 'Total Orders', 'Working Hours', 'Cancelled Orders', 'Rejected Orders', 'Notes', 'Created At']);

                foreach ($attendances as $attendance) {

                    $id = '';

                    if ($attendance->ref_type === 'employee') {
                        $id = $attendance->user->employee_id ?? '';
                    } else {
                        $id = $attendance->user->rider_id ?? '';
                    }

                    fputcsv($handle, [
                        ucfirst($attendance->ref_type),
                        $id,
                        $attendance->user->name ?? 'N/A',
                        $attendance->date->format('Y-m-d'),
                        $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('h:i:s A') : '-',
                        $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('h:i:s A') : '-',
                        ucfirst($attendance->status),
                        $attendance->ref_type === 'rider' ? ($attendance->total_orders ?? '-') : '-',
                        $attendance->ref_type === 'rider' ? ($attendance->working_hours ?? '-') : '-',
                        $attendance->ref_type === 'rider' ? ($attendance->cancelled_orders ?? '-') : '-',
                        $attendance->ref_type === 'rider' ? ($attendance->rejected_orders ?? '-') : '-',
                        $attendance->notes,
                        $attendance->created_at->format('Y-m-d H:i:s')
                    ]);
                }
            }

            fclose($handle);
        }, $filename);
    }

    public function summary(Request $request)
    {
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $userType = $request->get('user_type', 'employee');
        $usersId = $request->get('user_id', 'all');
        $projectId = $request->get('project_id');
        $fleetSupervisor = $request->get('fleet_supervisor');
        $viewMode = $request->get('view_mode', 'ten_days');
        $viewStart = max(1, (int) $request->get('view_start', 1));

        $date = Carbon::parse($selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        // Get all users based on type
        $users = $this->getUsersForSummary($userType, $usersId, $projectId, $fleetSupervisor);
        $projects = collect();
        $fleetSupervisors = collect();

        if ($userType === 'rider') {
            $projectIds = Riders::query()
                ->whereNotNull('customer_id')
                ->where('customer_id', '!=', '')
                ->distinct()
                ->pluck('customer_id')
                ->filter()
                ->values();

            $projects = Customers::query()
                ->whereIn('id', $projectIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            $fleetSupervisors = Riders::query()
                ->whereNotNull('fleet_supervisor')
                ->where('fleet_supervisor', '!=', '')
                ->distinct()
                ->orderBy('fleet_supervisor')
                ->pluck('fleet_supervisor');
        }

        // Get attendance for the month
        $attendances = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // Group attendances by ref_id for easier access
        $attendancesByUser = [];
        foreach ($attendances as $attendance) {
            $userId = $attendance->ref_id;
            if (!isset($attendancesByUser[$userId])) {
                $attendancesByUser[$userId] = [];
            }
            // Use date as key for easy lookup
            $dateKey = $attendance->date instanceof Carbon
                ? $attendance->date->format('Y-m-d')
                : Carbon::parse($attendance->date)->format('Y-m-d');

            $attendancesByUser[$userId][$dateKey] = $attendance;
        }

        $riderActivitiesByUser = [];
        if ($userType === 'rider' && $users->isNotEmpty()) {
            $activities = RiderActivities::query()
                ->whereIn('rider_id', $users->pluck('id'))
                ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->get(['rider_id', 'date', 'ontime_orders_percentage']);

            foreach ($activities as $activity) {
                $activityDate = $activity->date instanceof Carbon
                    ? $activity->date->format('Y-m-d')
                    : Carbon::parse($activity->date)->format('Y-m-d');

                $riderActivitiesByUser[$activity->rider_id][$activityDate] = $activity->ontime_orders_percentage;
            }
        }

        // Build date window based on selected view mode
        $windowSize = 10;
        if ($viewMode === 'week') {
            $windowSize = 7;
        } elseif ($viewMode === 'month') {
            $windowSize = $daysInMonth;
            $viewStart = 1;
        } else {
            $viewMode = 'ten_days';
        }
        $viewStart = min($viewStart, $daysInMonth);
        $windowEnd = min($viewStart + $windowSize - 1, $daysInMonth);

        // Prepare days array
        $days = [];
        $dates = [];
        for ($day = $viewStart; $day <= $windowEnd; $day++) {
            $currentDate = $startOfMonth->copy()->addDays($day - 1);
            $dateString = $currentDate->format('Y-m-d');
            $dates[] = $dateString;
            $days[] = [
                'number' => $day,
                'date' => $dateString,
                'day_name' => $currentDate->format('D'),
                'is_weekend' => $currentDate->isWeekend(),
                'is_today' => $currentDate->isToday(),
            ];
        }

        // Prepare user attendance data
        foreach ($users as $user) {
            $attendance_data = [];
            $user->total_present = 0;
            $user->total_absent = 0;
            $user->total_late = 0;
            $user->total_halfday = 0;
            $user->total_weekend = 0;
            $user->total_leave = 0;
            $user->total_unmarked = 0;

            // Get attendances for this user
            $userAttendances = $attendancesByUser[$user->id] ?? [];

            foreach ($dates as $dateString) {
                if (isset($userAttendances[$dateString])) {
                    $attendance = $userAttendances[$dateString];

                    $attendance_data[$dateString] = [
                        'exists' => true,
                        'id' => $attendance->id,
                        'status' => $attendance->status,
                        'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('h:i A') : null,
                        'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('h:i A') : null,
                        'notes' => $attendance->notes,
                        'ontime_orders_percentage' => $user->type === 'rider'
                            ? ($riderActivitiesByUser[$user->id][$dateString] ?? null)
                            : null,
                    ];

                    // Count totals
                    switch ($attendance->status) {
                        case 'present':
                            $user->total_present++;
                            break;
                        case 'absent':
                            $user->total_absent++;
                            break;
                        case 'late':
                            $user->total_late++;
                            $user->total_present++;
                            break;
                        case 'half day':
                            $user->total_halfday++;
                            $user->total_present++;
                            break;
                        case 'weekend':
                            $user->total_weekend++;
                            break;
                        case 'on leave':
                            $user->total_leave++;
                            break;
                    }
                } else {
                    $attendance_data[$dateString] = [
                        'exists' => false,
                        'status' => null
                    ];
                    $user->total_unmarked++;
                }
            }
            $user->attendance_data = $attendance_data;
        }

        // Calculate summary statistics
        $summary = [
            'total_present' => $users->sum('total_present'),
            'total_absent' => $users->sum('total_absent'),
            'total_late' => $users->sum('total_late'),
            'total_halfday' => $users->sum('total_halfday'),
            'total_weekend' => $users->sum('total_weekend'),
            'total_leave' => $users->sum('total_leave'),
            'total_unmarked' => $users->sum('total_unmarked')
        ];

        $totalUsers = $users->count();
        $totalDays = count($dates);
        $monthTotalDays = $daysInMonth;
        $totalAttendances = $totalUsers * $totalDays;
        $presentRate = 0;
        $absentRate = 0;
        $unmarkRate = 0;

        if ($totalAttendances > 0) {
            $presentRate = round(($summary['total_present'] / $totalAttendances) * 100);
            $absentRate = round(($summary['total_absent'] / $totalAttendances) * 100);
            $unmarkRate = round(($summary['total_unmarked'] / $totalAttendances) * 100);
        }
        $prevMonth = $date->copy()->subMonth()->format('Y-m-d');
        $nextMonth = $date->copy()->addMonth()->format('Y-m-d');
        $prevStart = max(1, $viewStart - $windowSize);
        $nextStart = min(max(1, $daysInMonth - $windowSize + 1), $viewStart + $windowSize);
        $hasPrevWindow = $viewStart > 1;
        $hasNextWindow = $windowEnd < $daysInMonth;

        return view('attendance.summary', compact(
            'users',
            'days',
            'date',
            'userType',
            'usersId',
            'projectId',
            'fleetSupervisor',
            'projects',
            'fleetSupervisors',
            'viewMode',
            'viewStart',
            'summary',
            'presentRate',
            'absentRate',
            'unmarkRate',
            'totalAttendances',
            'totalUsers',
            'totalDays',
            'monthTotalDays',
            'prevMonth',
            'nextMonth',
            'prevStart',
            'nextStart',
            'hasPrevWindow',
            'hasNextWindow'
        ));
    }

    /**
     * Get users for summary based on type
     */
    private function getUsersForSummary($userType, $userId, $projectId = null, $fleetSupervisor = null)
    {
        $users = null;

        if ($userType === 'employee') {
            if ($userId === 'all') {
                $users = Employee::active()->with('branch')->select('id', 'name', 'employee_id', 'branch_id', 'designation', 'status')
                    ->get()
                    ->map(function ($item) {
                        $item->type = 'employee';
                        $item->type_label = 'Employee';
                        $item->type_badge_class = 'bg-primary';
                        return $item;
                    });
            } else {
                $users = Employee::with('branch')->where('id', $userId)
                    ->get()
                    ->map(function ($item) {
                        $item->type = 'employee';
                        $item->type_label = 'Employee';
                        $item->type_badge_class = 'bg-primary';
                        return $item;
                    });
            }
        }

        if ($userType === 'rider') {
            $riderQuery = Riders::active()->with('branch');

            if (!empty($projectId)) {
                $riderQuery->where('customer_id', $projectId);
            }

            if (!empty($fleetSupervisor)) {
                $riderQuery->where('fleet_supervisor', $fleetSupervisor);
            }

            if ($userId === 'all') {
                $users = $riderQuery->select('id', 'name', 'rider_id', 'status', 'branch_id', 'designation', 'customer_id', 'fleet_supervisor')
                    ->get()
                    ->map(function ($item) {
                        $item->type = 'rider';
                        $item->type_label = 'Rider';
                        $item->type_badge_class = 'bg-success';
                        return $item;
                    });
            } else {
                $users = $riderQuery->where('id', $userId)
                    ->select('id', 'name', 'rider_id', 'status', 'branch_id', 'designation', 'customer_id', 'fleet_supervisor')
                    ->get()
                    ->map(function ($item) {
                        $item->type = 'rider';
                        $item->type_label = 'Rider';
                        $item->type_badge_class = 'bg-success';
                        return $item;
                    });
            }
        }

        // Sort by name and reset keys
        $users = $users->sortBy('name')->values();

        if ($userType === 'rider') {
            Riders::hydrateEmploymentStatusDays($users);
        }

        return $users;
    }

    /**
     * Export summary to Excel/CSV
     */
    public function exportSummary(Request $request)
    {
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $userType = $request->get('user_type', 'all');
        $usersId = $request->get('user_id', 'all');
        $projectId = $request->get('project_id');
        $fleetSupervisor = $request->get('fleet_supervisor');

        $date = Carbon::parse($selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        $users = $this->getUsersForSummary($userType, $usersId, $projectId, $fleetSupervisor);

        $userIds = $users->pluck('id');

        // Only fetch relevant attendance records
        $attendances = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('ref_type', $userType)
            ->whereIn('ref_id', $userIds)
            ->get();

        // Build fast lookup array
        $attendanceMap = [];
        foreach ($attendances as $att) {
            $key = $att->ref_type . '_' . $att->ref_id . '_' . $att->date->format('Y-m-d');
            $attendanceMap[$key] = $att->status;
        }

        // Build days list once
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = $startOfMonth->copy()->addDays($day - 1)->format('Y-m-d');
        }

        $filename = 'attendance_summary_' . $date->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($users, $days, $attendanceMap) {

            $handle = fopen('php://output', 'w');

            // Excel UTF-8 support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = ['ID', 'Name', 'Type'];

            foreach ($days as $day) {
                $header[] = Carbon::parse($day)->format('d D');
            }

            $header[] = 'Total Present';
            $header[] = 'Total Late';
            $header[] = 'Total Half-Day';

            fputcsv($handle, $header);

            foreach ($users as $user) {

                $row = [
                    $user->id,
                    $user->name,
                    $user->type_label
                ];

                $totalPresent = 0;
                $totalLate = 0;
                $totalHalfday = 0;

                foreach ($days as $day) {

                    $key = $user->type . '_' . $user->id . '_' . $day;

                    if (isset($attendanceMap[$key])) {

                        switch ($attendanceMap[$key]) {

                            case 'present':
                                $row[] = "Present";
                                $totalPresent++;
                                break;

                            case 'absent':
                                $row[] = 'Absent';
                                break;

                            case 'late':
                                $row[] = 'Late';
                                $totalPresent++;
                                $totalLate++;
                                break;

                            case 'half day':
                                $row[] = 'Half Day';
                                $totalPresent++;
                                $totalHalfday++;
                                break;

                            case 'weekend':
                                $row[] = 'Weekend';
                                break;

                            case 'on leave':
                                $row[] = 'On Leave';
                                break;

                            default:
                                $row[] = '-';
                        }
                    } else {
                        $row[] = '-';
                    }
                }

                $row[] = $totalPresent;
                $row[] = $totalLate;
                $row[] = $totalHalfday;

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename);
    }

    private function resolveBranchIdForAttendance(string $refType, int $refId): ?int
    {
        if ($refType === 'rider') {
            $branchId = Riders::where('id', $refId)->value('branch_id');

            return $branchId !== null ? (int) $branchId : null;
        }

        if ($refType === 'employee') {
            $branchId = Employee::where('id', $refId)->value('branch_id');

            return $branchId !== null ? (int) $branchId : null;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function riderAttendanceMetricRules(): array
    {
        return [
            'total_orders' => 'nullable|integer|min:0',
            'working_hours' => 'nullable|numeric|min:0',
            'rejected_orders' => 'nullable|integer|min:0',
            'cancelled_orders' => 'nullable|integer|min:0',
            'ontime_orders_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
