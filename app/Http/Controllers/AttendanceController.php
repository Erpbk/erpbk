<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Riders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request)
    {
        $query = Attendance::query();

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

        if($request->has('from_date') && $request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if($request->has('to_date') && $request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if(!$request->has('date') && !$request->has('from_date') && !$request->has('to_date')) {
            $query->currentMonth();
        }

        $query->orderBy('date', 'desc');

        $attendances = $query->get();

        return view('attendance.index', compact('attendances'));
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create()
    {
        $employees = Employee::all();
        $riders = Riders::all();
        
        return view('attendance.create', compact('employees', 'riders'));
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
            'status' => 'required|in:present,absent,late,half day,holiday',
            'notes' => 'nullable|string|max:500'
        ];
        if($request->ref_type === 'employee') {
            $rules['ref_id'] .= '|exists:employees,id';
        } else {
            $rules['ref_id'] .= '|exists:riders,id';
        }
        if($request->status === 'present' || $request->status === 'late' || $request->status === 'half day') {
            $rules['check_in'] = 'required';
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

        Attendance::create($validated);

        return response()->json(['success' => true, 'message' => 'Attendance record created successfully.']);
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load('user');
        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record.
     */
    public function edit(Attendance $attendance)
    {
        $employees = Employee::all();
        $riders = Riders::all();
        
        return view('attendance.edit', compact('attendance', 'employees', 'riders'));
    }

    /**
     * Update the specified attendance record.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $rules = [
            'ref_type' => 'required|in:employee,rider',
            'ref_id' => 'required|integer',
            'date' => 'required|date',
            'check_out' => 'nullable|after:check_in',
            'status' => 'required|in:present,absent,late,half-day,holiday',
            'notes' => 'nullable|string|max:500'
        ];
        if($request->status === 'present' || $request->status === 'late' || $request->status === 'half-day') {
            $rules['check_in'] = 'required';
        }else {
            $rules['check_in'] = 'nullable';
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

        $attendance->update($validated);

            return response()->json(['success' => true, 'message' => 'Attendance record updated successfully.']);
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        try{
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
    public function getUsers($refType)
    {

        if ($refType === 'employee') {
            $users = Employee::active()->select('id', 'name')->get();
        } else {
            $users = Riders::active()->select('id', 'name')->get();
        }

        return response()->json($users);
    }

    /**
     * Check-in for today (for authenticated user)
     */
    public function checkIn(Request $request)
    {
        // Determine user type based on authentication guard
        if (Auth::guard('employee')->check()) {
            $refType = 'employee';
            $refId = Auth::guard('employee')->id();
            $user = Auth::guard('employee')->user();
        } elseif (Auth::guard('rider')->check()) {
            $refType = 'rider';
            $refId = Auth::guard('rider')->id();
            $user = Auth::guard('rider')->user();
        } else {
            return redirect()->back()->with('error', 'Unauthenticated.');
        }

        $today = Carbon::today();
        
        $attendance = Attendance::firstOrCreate(
            [
                'ref_id' => $refId,
                'ref_type' => $refType,
                'date' => $today
            ],
            [
                'check_in' => Carbon::now()->format('H:i:s'),
                'status' => 'present'
            ]
        );

        if (!$attendance->wasRecentlyCreated && !$attendance->check_in) {
            $attendance->update([
                'check_in' => Carbon::now()->format('H:i:s'),
                'status' => 'present'
            ]);
            $message = 'Checked in successfully.';
        } elseif ($attendance->wasRecentlyCreated) {
            $message = 'Checked in successfully.';
        } else {
            return redirect()->back()->with('error', 'Already checked in today.');
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Check-out for today (for authenticated user)
     */
    public function checkOut()
    {
        // Determine user type based on authentication guard
        if (Auth::guard('employee')->check()) {
            $refType = 'employee';
            $refId = Auth::guard('employee')->id();
        } elseif (Auth::guard('rider')->check()) {
            $refType = 'rider';
            $refId = Auth::guard('rider')->id();
        } else {
            return redirect()->back()->with('error', 'Unauthenticated.');
        }

        $attendance = Attendance::where('ref_id', $refId)
            ->where('ref_type', $refType)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'No check-in record found for today.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'Already checked out today.');
        }

        $attendance->update([
            'check_out' => Carbon::now()->format('H:i:s')
        ]);

        return redirect()->back()->with('success', 'Checked out successfully.');
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
                'attendances.*.status' => 'required|in:present,absent,late,half day, on leave, holiday',
                'attendances.*.check_in' => 'nullable',
                'attendances.*.check_out' => 'nullable',
                'attendances.*.notes' => 'nullable|string|max:500'
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
     * Show monthly attendance report
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;
        $refType = $request->ref_type ?? 'employee';

        if ($refType === 'employee') {
            $users = Employee::with(['attendance' => function($query) use ($month, $year) {
                $query->whereMonth('date', $month)
                      ->whereYear('date', $year);
            }])->get();
        } else {
            $users = Riders::with(['attendance' => function($query) use ($month, $year) {
                $query->whereMonth('date', $month)
                      ->whereYear('date', $year);
            }])->get();
        }

        // Calculate summary
        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $summary = [];

        foreach ($users as $user) {
            $attendances = $user->attendance;
            $summary[$user->id] = [
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'half_day' => $attendances->where('status', 'half-day')->count(),
                'holiday' => $attendances->where('status', 'holiday')->count(),
            ];
        }

        return view('attendance.monthly-report', compact('users', 'summary', 'month', 'year', 'refType', 'totalDays'));
    }

    /**
     * Export attendance to CSV
     */
    public function export(Request $request)
    {
        $query = Attendance::with('user');

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->has('ref_type') && $request->ref_type) {
            $query->where('ref_type', $request->ref_type);
        }

        $attendances = $query->orderBy('date', 'desc')->get();
        
        $filename = 'attendance_export_' . Carbon::now()->format('Y_m_d_His') . '.csv';
        $handle = fopen('php://output', 'w');
         if($attendances->isEmpty()) {
            fputcsv($handle, ['No records found for the selected criteria.']);
        } else {


            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            // Add CSV headers
            fputcsv($handle, ['Type','ID', 'Name', 'Date', 'Check In', 'Check Out', 'Status', 'Notes', 'Created At']);
            // Add data rows
            foreach ($attendances as $attendance) {
                $id = '';
                if( $attendance->ref_type === 'employee') {
                    $id = $attendance->user->employee_id . ' ';
                } else {
                    $id = $attendance->user->rider_id . ' ';
                }
                fputcsv($handle, [
                    ucfirst($attendance->ref_type),
                    $id,
                    $attendance->user->name ?? 'N/A',
                    $attendance->date->format('Y-m-d'),
                    $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : '-',
                    $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : '-',
                    ucfirst($attendance->status),
                    $attendance->notes,
                    $attendance->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
            exit;
        }
    }
}