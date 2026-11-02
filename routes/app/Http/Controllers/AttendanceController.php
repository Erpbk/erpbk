<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
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
        $this->middleware('permission:employees_attendance_view|riders_attendance_view')->only('index', 'summary', 'export', 'exportSummary');
        $this->middleware('permission:employees_attendance_create|riders_attendance_create|employees_attendance_edit|riders_attendance_edit')->only('upsertMark');
        $this->middleware('permission:employees_attendance_create|riders_attendance_create')->only('create', 'store', 'bulkMark', 'import', 'importTemplate');
        $this->middleware('permission:employees_attendance_edit|riders_attendance_edit')->only('edit', 'update');
        $this->middleware('permission:employees_attendance_delete|riders_attendance_delete')->only('destroy', 'clearMark');
    }
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request)
    {
        if (! \App\Support\RoleFieldAccess::canAccessModule('attendance')) {
            abort(403, 'Unauthorized action.');
        }

        $refType = $request->get('ref_type', $request->get('user_type', 'employee')) ?: 'employee';
        $request->merge(['ref_type' => $refType]);

        $query = Attendance::with('user');
        $this->applyAttendanceListFilters($query, $request);

        $query->orderBy('date', 'desc');

        $attendances = $query->get();

        Riders::hydrateEmploymentStatusDays(
            $attendances->where('ref_type', 'rider')->map->user
        );

        ['projects' => $projects, 'fleetSupervisors' => $fleetSupervisors] = $this->riderFilterOptions();

        $reportKpis = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'on leave')->count(),
            'half' => $attendances->where('status', 'half day')->count(),
            'weekend' => $attendances->where('status', 'weekend')->count(),
        ];

        $monthStart = $request->filled('from_date')
            ? Carbon::parse($request->get('from_date'))->startOfDay()
            : now()->startOfMonth();
        $monthEnd = $request->filled('to_date')
            ? Carbon::parse($request->get('to_date'))->endOfDay()
            : now()->endOfMonth();
        if ($request->filled('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->get('date'))) {
            $monthStart = Carbon::parse($request->get('date'))->startOfMonth();
            $monthEnd = Carbon::parse($request->get('date'))->endOfMonth();
        }

        $refIdFilter = $request->get('ref_id', $request->get('user_id', 'all')) ?: 'all';
        $projectId = $request->get('project_id');
        $fleetSupervisor = $request->get('fleet_supervisor');
        $users = $this->getUsersForSummary($refType, $refIdFilter, $projectId, $fleetSupervisor);

        $attendancesByUser = $attendances
            ->where('ref_type', $refType)
            ->groupBy('ref_id');

        $personSheets = [];
        foreach ($users as $user) {
            $group = collect($attendancesByUser->get($user->id, []));
            $byDate = [];
            foreach ($group as $row) {
                $key = $row->date instanceof Carbon
                    ? $row->date->format('Y-m-d')
                    : Carbon::parse($row->date)->format('Y-m-d');
                $byDate[$key] = $row->status;
            }
            $presentCount = $group->where('status', 'present')->count();
            $lateCount = $group->where('status', 'late')->count();
            $halfCount = $group->where('status', 'half day')->count();
            $absentCount = $group->where('status', 'absent')->count();
            $leaveCount = $group->where('status', 'on leave')->count();
            $weekendCount = $group->where('status', 'weekend')->count();
            $credited = $presentCount + $lateCount + $halfCount;
            $workingDays = max(0, $group->where('status', '!=', 'weekend')->count());
            $pct = $workingDays > 0 ? (int) round(($credited / $workingDays) * 100) : 0;

            $personSheets[] = [
                'ref_type' => $refType,
                'ref_id' => $user->id,
                'name' => $user->name,
                'code' => $user->employee_id ?? $user->rider_id ?? '',
                'designation' => $user->designation ?? '',
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'leave' => $leaveCount,
                'half' => $halfCount,
                'weekend' => $weekendCount,
                'pct' => $pct,
                'by_date' => $byDate,
            ];
        }

        // Keep name order stable for the report table
        usort($personSheets, fn ($a, $b) => strcasecmp((string) $a['name'], (string) $b['name']));

        $importPeople = $users;
        $openImport = $request->boolean('import');

        return view('attendance.index', compact(
            'attendances',
            'projects',
            'fleetSupervisors',
            'reportKpis',
            'personSheets',
            'monthStart',
            'monthEnd',
            'refType',
            'importPeople',
            'openImport'
        ));
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
        if (($validated['ref_type'] ?? '') === 'rider') {
            $validated = $this->applyRiderMetricDefaults($validated);
        }
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
        if (($validated['ref_type'] ?? '') === 'rider') {
            $validated = $this->applyRiderMetricDefaults($validated);
        }

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
                'attendances.*.total_orders' => $request->ref_type === 'rider' ? 'required|integer|min:0' : 'nullable|integer|min:0',
                'attendances.*.working_hours' => $request->ref_type === 'rider' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
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
                    $attendanceData = $this->applyRiderMetricDefaults($attendanceData);
                    $metricData = RiderAttendanceActivitySync::metricDataFromRequest($attendanceData);
                    foreach (RiderAttendanceActivitySync::RIDER_METRIC_KEYS as $metricKey) {
                        if (array_key_exists($metricKey, $metricData)) {
                            $data[$metricKey] = $metricData[$metricKey];
                        }
                    }
                    $data['cancelled_orders'] = (int) ($attendanceData['cancelled_orders'] ?? 0);
                    $data['rejected_orders'] = (int) ($attendanceData['rejected_orders'] ?? 0);
                    $data['total_orders'] = (int) ($attendanceData['total_orders'] ?? 0);
                    $data['working_hours'] = (float) ($attendanceData['working_hours'] ?? 0);
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
     * Show / process CSV attendance import (Employee Name, Date, Status, Check In, Check Out).
     */
    public function import(Request $request)
    {
        $refType = $request->input('ref_type', $request->input('user_type', 'employee'));
        if (! in_array($refType, ['employee', 'rider'], true)) {
            $refType = 'employee';
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
                'ref_type' => 'required|in:employee,rider',
            ]);

            $refType = $request->input('ref_type');
            $handle = fopen($request->file('file')->getRealPath(), 'r');
            if ($handle === false) {
                return back()->with('error', 'Unable to read uploaded file.');
            }

            $header = fgetcsv($handle);
            $imported = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $name = trim((string) ($row[0] ?? ''));
                $date = trim((string) ($row[1] ?? ''));
                $statusRaw = strtolower(trim((string) ($row[2] ?? 'present')));
                $checkIn = trim((string) ($row[3] ?? ''));
                $checkOut = trim((string) ($row[4] ?? ''));

                if ($name === '' || $date === '') {
                    $skipped++;
                    continue;
                }

                try {
                    $dateVal = Carbon::parse($date)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $skipped++;
                    continue;
                }

                $person = $refType === 'employee'
                    ? Employee::query()->where(function ($q) use ($name) {
                        $q->where('name', $name)->orWhere('employee_id', $name);
                    })->first()
                    : Riders::query()->where(function ($q) use ($name) {
                        $q->where('name', $name)->orWhere('rider_id', $name);
                    })->first();

                if (! $person) {
                    $skipped++;
                    continue;
                }

                $status = $this->normalizeImportedAttendanceStatus($statusRaw);
                $payload = [
                    'ref_id' => $person->id,
                    'ref_type' => $refType,
                    'date' => $dateVal,
                    'status' => $status,
                    'branch_id' => $this->resolveBranchIdForAttendance($refType, $person->id),
                ];
                if ($checkIn !== '') {
                    $payload['check_in'] = $dateVal . ' ' . $checkIn;
                }
                if ($checkOut !== '') {
                    $payload['check_out'] = $dateVal . ' ' . $checkOut;
                }

                Attendance::updateOrCreate(
                    [
                        'ref_id' => $person->id,
                        'ref_type' => $refType,
                        'date' => $dateVal,
                    ],
                    $payload
                );
                $imported++;
            }
            fclose($handle);

            $message = "Imported {$imported} attendance row(s). Skipped {$skipped}.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'redirect' => route('attendance.summary', ['user_type' => $refType]),
                ]);
            }

            return redirect()
                ->route('attendance.summary', ['user_type' => $refType])
                ->with('success', $message);
        }

        // GET: open Attendance with import modal (HTML demo parity)
        return redirect()->route('attendance.summary', [
            'user_type' => $refType,
            'import' => 1,
        ]);
    }

    public function importTemplate(Request $request)
    {
        $refType = $request->get('ref_type', $request->get('user_type', 'employee')) ?: 'employee';
        if (! in_array($refType, ['employee', 'rider'], true)) {
            $refType = 'employee';
        }
        $people = $this->getUsersForSummary($refType, 'all');
        $today = now()->format('Y-m-d');
        $nameHeader = $refType === 'rider' ? 'Rider Name' : 'Employee Name';
        $filename = 'attendance-import-template-' . $refType . '-' . $today . '.csv';

        return response()->streamDownload(function () use ($people, $today, $nameHeader) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [$nameHeader, 'Date', 'Status', 'Check In', 'Check Out']);
            // Match HTML demo: name + today filled; Status / Check In / Check Out left blank
            if ($people->isEmpty()) {
                fputcsv($handle, ['Example Name', $today, '', '', '']);
            } else {
                foreach ($people as $person) {
                    fputcsv($handle, [
                        $person->name,
                        $today,
                        '',
                        '',
                        '',
                    ]);
                }
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export attendance to CSV
     */
    public function export(Request $request)
    {
        $query = Attendance::with('user');
        $this->applyAttendanceListFilters($query, $request, false);

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
        $userType = $request->get('user_type', $request->get('ref_type', 'employee')) ?: 'employee';
        $usersId = $request->get('user_id', $request->get('ref_id', 'all')) ?: 'all';
        $projectId = $request->get('project_id');
        $fleetSupervisor = $request->get('fleet_supervisor');
        $viewMode = $request->get('view_mode', 'ten_days');
        $viewStart = max(1, (int) $request->get('view_start', 1));
        $statusFilter = $this->normalizeAttendanceStatus($request->get('status'));
        $fromDate = $request->filled('from_date') ? $request->get('from_date') : null;
        $toDate = $request->filled('to_date') ? $request->get('to_date') : null;
        $filterDate = $request->filled('filter_date') ? $request->get('filter_date') : null;

        $rawDate = trim((string) $request->get('date', ''));
        if ($rawDate === '') {
            $rawDate = $filterDate ?: now()->format('Y-m-d');
        } elseif (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
            $rawDate .= '-01';
        }

        $date = Carbon::parse($rawDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        if (!$fromDate && !$toDate && $filterDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
            $fromDate = $filterDate;
            $toDate = $filterDate;
            $date = Carbon::parse($filterDate);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $daysInMonth = $date->daysInMonth;
        }

        $isCustomRange = filled($fromDate) || filled($toDate);
        $rangeStart = $isCustomRange
            ? Carbon::parse($fromDate ?: $startOfMonth)
            : $startOfMonth;
        $rangeEnd = $isCustomRange
            ? Carbon::parse($toDate ?: $endOfMonth)
            : $endOfMonth;
        if ($rangeStart->gt($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy(), $rangeStart->copy()];
        }
        if ($rangeStart->diffInDays($rangeEnd) > 62) {
            $rangeEnd = $rangeStart->copy()->addDays(62);
        }

        // Get all users based on type
        $users = $this->getUsersForSummary($userType, $usersId, $projectId, $fleetSupervisor);
        $filterUsers = $usersId === 'all'
            ? $users
            : $this->getUsersForSummary($userType, 'all', $projectId, $fleetSupervisor);
        ['projects' => $projects, 'fleetSupervisors' => $fleetSupervisors] = $this->riderFilterOptions();

        // Get attendance for the visible range (scoped to user type)
        $attendances = Attendance::where('ref_type', $userType)
            ->whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')])
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
                ->whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')])
                ->get(['rider_id', 'date', 'ontime_orders_percentage']);

            foreach ($activities as $activity) {
                $activityDate = $activity->date instanceof Carbon
                    ? $activity->date->format('Y-m-d')
                    : Carbon::parse($activity->date)->format('Y-m-d');

                $riderActivitiesByUser[$activity->rider_id][$activityDate] = $activity->ontime_orders_percentage;
            }
        }

        $days = [];
        $dates = [];
        $windowSize = 10;
        $windowEnd = $daysInMonth;

        if ($isCustomRange) {
            $viewMode = 'range';
            $viewStart = 1;
            for ($currentDate = $rangeStart->copy(); $currentDate->lte($rangeEnd); $currentDate->addDay()) {
                $dateString = $currentDate->format('Y-m-d');
                $dates[] = $dateString;
                $days[] = [
                    'number' => $currentDate->format('j'),
                    'date' => $dateString,
                    'day_name' => $currentDate->format('D'),
                    'is_weekend' => $currentDate->isWeekend(),
                    'is_today' => $currentDate->isToday(),
                ];
            }
            $windowSize = max(1, count($dates));
            $windowEnd = $windowSize;
            $hasPrevWindow = false;
            $hasNextWindow = false;
            $prevStart = 1;
            $nextStart = 1;
        } else {
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
            $prevStart = max(1, $viewStart - $windowSize);
            $nextStart = min(max(1, $daysInMonth - $windowSize + 1), $viewStart + $windowSize);
            $hasPrevWindow = $viewStart > 1;
            $hasNextWindow = $windowEnd < $daysInMonth;
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

        $rawStatus = trim((string) $request->get('status', ''));
        if ($rawStatus === 'unmarked') {
            $users = $users->filter(function ($user) {
                foreach ($user->attendance_data ?? [] as $row) {
                    if (empty($row['exists'])) {
                        return true;
                    }
                }
                return false;
            })->values();
            $statusFilter = 'unmarked';
        } elseif ($statusFilter) {
            $users = $users->filter(function ($user) use ($statusFilter) {
                foreach ($user->attendance_data ?? [] as $row) {
                    if (($row['status'] ?? null) === $statusFilter) {
                        return true;
                    }
                }
                return false;
            })->values();
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

        $branchTabs = collect();
        if ($userType === 'employee') {
            $branchTabs = $users
                ->groupBy(fn ($u) => $u->branch_id ?: 0)
                ->map(function ($group, $branchId) {
                    $first = $group->first();

                    return [
                        'id' => (int) $branchId,
                        'name' => $branchId
                            ? (string) ($first->branch->name ?? 'Branch')
                            : 'No branch',
                        'count' => $group->count(),
                    ];
                })
                ->sortBy('name')
                ->values();
        }
        $selectedBranchId = $request->filled('branch_id') ? (int) $request->get('branch_id') : null;
        if ($selectedBranchId !== null) {
            $users = $users->filter(function ($user) use ($selectedBranchId) {
                return (int) ($user->branch_id ?: 0) === $selectedBranchId;
            })->values();
            $totalUsers = $users->count();
        }

        $todayKey = now()->format('Y-m-d');
        $todayPresent = 0;
        $todayLate = 0;
        $todayAbsent = 0;
        $todayUnmarked = 0;
        foreach ($users as $user) {
            $row = $user->attendance_data[$todayKey] ?? null;
            if (! $row || empty($row['exists'])) {
                $todayUnmarked++;
                continue;
            }
            match ($row['status'] ?? '') {
                'present' => $todayPresent++,
                'late' => $todayLate++,
                'absent' => $todayAbsent++,
                default => null,
            };
        }
        $todayKpis = [
            'total' => $totalUsers,
            'present' => $todayPresent,
            'late' => $todayLate,
            'absent' => $todayAbsent,
            'unmarked' => $todayUnmarked,
        ];

        $importPeople = $filterUsers ?? $users;
        $openImport = $request->boolean('import');

        return view('attendance.summary', compact(
            'users',
            'filterUsers',
            'importPeople',
            'openImport',
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
            'hasNextWindow',
            'statusFilter',
            'fromDate',
            'toDate',
            'isCustomRange',
            'filterDate',
            'todayKpis',
            'branchTabs',
            'selectedBranchId'
        ));
    }

    /**
     * Create or update a single day attendance mark (HTML-demo mark modal).
     */
    public function upsertMark(Request $request)
    {
        $validated = $request->validate([
            'ref_type' => 'required|in:employee,rider',
            'ref_id' => 'required|integer',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,half day,weekend,on leave',
            'check_in' => 'nullable|string',
            'check_out' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['ref_type'] === 'employee') {
            abort_unless(Employee::where('id', $validated['ref_id'])->exists(), 404);
        } else {
            abort_unless(Riders::where('id', $validated['ref_id'])->exists(), 404);
        }

        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $checkIn = null;
        $checkOut = null;
        if (! empty($validated['check_in'])) {
            $checkIn = Carbon::parse($date . ' ' . $validated['check_in'])->format('Y-m-d H:i:s');
        }
        if (! empty($validated['check_out'])) {
            $checkOut = Carbon::parse($date . ' ' . $validated['check_out'])->format('Y-m-d H:i:s');
        }

        $payload = [
            'status' => $validated['status'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'notes' => $validated['notes'] ?? null,
            'branch_id' => $this->resolveBranchIdForAttendance($validated['ref_type'], (int) $validated['ref_id']),
        ];

        $attendance = Attendance::updateOrCreate(
            [
                'ref_id' => (int) $validated['ref_id'],
                'ref_type' => $validated['ref_type'],
                'date' => $date,
            ],
            $payload
        );

        if ($validated['ref_type'] === 'rider') {
            RiderAttendanceActivitySync::syncActivityFromAttendance(
                (int) $validated['ref_id'],
                $date,
                RiderAttendanceActivitySync::syncDataFromAttendance($attendance, $payload)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved.',
            'attendance' => [
                'id' => $attendance->id,
                'status' => $attendance->status,
                'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
            ],
        ]);
    }

    public function clearMark(Request $request)
    {
        $validated = $request->validate([
            'ref_type' => 'required|in:employee,rider',
            'ref_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $deleted = Attendance::where('ref_id', (int) $validated['ref_id'])
            ->where('ref_type', $validated['ref_type'])
            ->whereDate('date', $date)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Attendance cleared.' : 'Nothing to clear.',
            'cleared' => (bool) $deleted,
        ]);
    }

    /**
     * Get users for summary based on type
     */
    private function getUsersForSummary($userType, $userId, $projectId = null, $fleetSupervisor = null)
    {
        $users = null;

        if ($userType === 'employee') {
            if ($userId === 'all') {
                $users = Employee::with('branch')->select('id', 'name', 'employee_id', 'branch_id', 'designation', 'status')
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
            $riderQuery = Riders::with('branch');

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
        $users = collect($users)->sortBy('name')->values();

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
        if (is_string($selectedDate) && preg_match('/^\d{4}-\d{2}$/', $selectedDate)) {
            $selectedDate .= '-01';
        }
        $userType = $request->get('user_type', $request->get('ref_type', 'employee')) ?: 'employee';
        $usersId = $request->get('user_id', $request->get('ref_id', 'all')) ?: 'all';
        $projectId = $request->get('project_id');
        $fleetSupervisor = $request->get('fleet_supervisor');
        $statusFilter = $this->normalizeAttendanceStatus($request->get('status'));
        $fromDate = $request->filled('from_date') ? $request->get('from_date') : null;
        $toDate = $request->filled('to_date') ? $request->get('to_date') : null;
        $filterDate = $request->filled('filter_date') ? $request->get('filter_date') : null;

        if (!$fromDate && !$toDate && $filterDate) {
            $fromDate = $filterDate;
            $toDate = $filterDate;
            $selectedDate = $filterDate;
        }

        $date = Carbon::parse($selectedDate ?: now()->format('Y-m-d'));
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;
        $rangeStart = $fromDate ? Carbon::parse($fromDate) : $startOfMonth;
        $rangeEnd = $toDate ? Carbon::parse($toDate) : $endOfMonth;
        if ($rangeStart->gt($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy(), $rangeStart->copy()];
        }

        $users = $this->getUsersForSummary($userType, $usersId, $projectId, $fleetSupervisor);

        $userIds = $users->pluck('id');

        // Only fetch relevant attendance records
        $attendances = Attendance::whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')])
            ->where('ref_type', $userType)
            ->whereIn('ref_id', $userIds)
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->get();

        // Build fast lookup array
        $attendanceMap = [];
        foreach ($attendances as $att) {
            $key = $att->ref_type . '_' . $att->ref_id . '_' . $att->date->format('Y-m-d');
            $attendanceMap[$key] = $att->status;
        }

        // Build days list once
        $days = [];
        for ($current = $rangeStart->copy(); $current->lte($rangeEnd); $current->addDay()) {
            $days[] = $current->format('Y-m-d');
        }

        $filename = 'attendance_summary_' . $rangeStart->format('Y_m_d') . '_' . $rangeEnd->format('Y_m_d') . '.csv';

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

    /**
     * Shared list/export filters for the attendance report.
     */
    private function applyAttendanceListFilters($query, Request $request, bool $defaultToCurrentMonth = true): void
    {
        $exactDate = $request->get('date');
        if (is_string($exactDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $exactDate)) {
            $query->whereDate('date', $exactDate);
        } else {
            if ($request->filled('from_date')) {
                $query->whereDate('date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('date', '<=', $request->to_date);
            }
            if ($defaultToCurrentMonth && !$request->filled('from_date') && !$request->filled('to_date')) {
                $query->currentMonth();
            }
        }

        $refType = $request->get('ref_type', $request->get('user_type'));
        if (filled($refType)) {
            $query->where('ref_type', $refType);
        }

        $refId = $request->get('ref_id', $request->get('user_id'));
        if (filled($refId) && $refId !== 'all') {
            $query->where('ref_id', $refId);
        }

        $status = $this->normalizeAttendanceStatus($request->get('status'));
        if ($status) {
            $query->where('status', $status);
        }

        if (strtolower((string) $refType) === 'rider' && ($request->filled('project_id') || $request->filled('fleet_supervisor'))) {
            $riderQuery = Riders::query();
            if ($request->filled('project_id')) {
                $riderQuery->where('customer_id', $request->project_id);
            }
            if ($request->filled('fleet_supervisor')) {
                $riderQuery->where('fleet_supervisor', $request->fleet_supervisor);
            }
            $query->where('ref_type', 'rider')->whereIn('ref_id', $riderQuery->pluck('id'));
        }
    }

    private function normalizeAttendanceStatus(?string $status): ?string
    {
        $status = trim((string) $status);
        if ($status === '') {
            return null;
        }
        if ($status === 'half-day') {
            return 'half day';
        }

        return $status;
    }

    private function normalizeImportedAttendanceStatus(?string $status): string
    {
        $raw = strtolower(trim((string) $status));
        $map = [
            'p' => 'present',
            'present' => 'present',
            'l' => 'late',
            'late' => 'late',
            'late in' => 'late',
            'a' => 'absent',
            'absent' => 'absent',
            'v' => 'on leave',
            'leave' => 'on leave',
            'on leave' => 'on leave',
            'h' => 'half day',
            'half' => 'half day',
            'half day' => 'half day',
            'half-day' => 'half day',
            'w' => 'weekend',
            'weekend' => 'weekend',
            'off' => 'weekend',
        ];

        return $map[$raw] ?? 'present';
    }

    /**
     * @return array{projects: \Illuminate\Support\Collection, fleetSupervisors: \Illuminate\Support\Collection}
     */
    private function riderFilterOptions(): array
    {
        $projectIds = company_table('riders')
            ->whereNotNull('customer_id')
            ->where('customer_id', '!=', '')
            ->pluck('customer_id')
            ->unique()
            ->filter()
            ->values();

        $fromRiders = company_table('riders')
            ->whereNotNull('fleet_supervisor')
            ->where('fleet_supervisor', '!=', '')
            ->distinct()
            ->orderBy('fleet_supervisor')
            ->pluck('fleet_supervisor');

        $fromDropdown = collect();
        $dropdownRow = company_table('dropdowns')
            ->where(function ($query) {
                $query->where('label', 'Fleet Supervisor')
                    ->orWhere('name', 'Fleet Supervisor')
                    ->orWhere('key', 'fleet_supervisor');
            })
            ->whereNotNull('values')
            ->first();
        if ($dropdownRow && $dropdownRow->values) {
            $decoded = json_decode($dropdownRow->values, true);
            if (is_array($decoded)) {
                $fromDropdown = collect($decoded);
            }
        }

        return [
            'projects' => company_table('customers')
                ->whereIn('id', $projectIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'fleetSupervisors' => $fromRiders
                ->merge($fromDropdown)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ];
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
            'total_orders' => 'required|integer|min:0',
            'working_hours' => 'required|numeric|min:0',
            'rejected_orders' => 'nullable|integer|min:0',
            'cancelled_orders' => 'nullable|integer|min:0',
            'ontime_orders_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Cancelled / rejected default to 0 when left blank; user may override.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyRiderMetricDefaults(array $validated): array
    {
        $validated['cancelled_orders'] = (int) ($validated['cancelled_orders'] ?? 0);
        $validated['rejected_orders'] = (int) ($validated['rejected_orders'] ?? 0);

        return $validated;
    }
}
