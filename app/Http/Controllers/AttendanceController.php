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
        if(request()->has('ref_type')){
            $refType = request()->get('ref_type');
            $refId = request()->get('ref_id');
            $date = request()->get('date', date('Y-m-d'));
            return view('attendance.create', compact('refType', 'refId', 'date'));
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
            'status' => 'required|in:present,absent,late,half day,holiday,on leave',
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

        return response()->json([
            'success' => true,
            'message' => 'Attendance record created successfully.',
        ]);
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
            'status' => 'required|in:present,absent,late,half day, holiday, on leave',
            'notes' => 'nullable|string|max:500'
        ];
        if($request->status === 'present' || $request->status === 'late' || $request->status === 'half day') {
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

            return response()->json([
                'success' => true,
                'message' => 'Attendance record updated successfully.',
            ]);
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
        $users = null;
        if ($refType === 'employee') {
            $users = Employee::active()->select('id', 'name')->get();
        } else {
            $users = Riders::active()->select('id', 'name')->get();
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

    public function summary(Request $request)
{
    $selectedDate = $request->get('date', now()->format('Y-m-d'));
    $userType = $request->get('user_type', 'employee');
    $usersId = $request->get('user_id','all');
    
    $date = Carbon::parse($selectedDate);
    $startOfMonth = $date->copy()->startOfMonth();
    $endOfMonth = $date->copy()->endOfMonth();
    $daysInMonth = $date->daysInMonth;
    
    // Get all users based on type
    $users = $this->getUsersForSummary($userType, $usersId);
    
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
    
    // Prepare days array
    $days = [];
    $dates = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
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
        $user->total_holiday = 0;
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
                    'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                    'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                    'notes' => $attendance->notes
                ];
                
                // Count totals
                switch($attendance->status) {
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
                    case 'holiday':
                        $user->total_holiday++;
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
        'total_holiday' => $users->sum('total_holiday'),
        'total_leave' => $users->sum('total_leave'),
        'total_unmarked' => $users->sum('total_unmarked')
    ];

    $totalUsers = $users->count();
    $totalDays = $daysInMonth;
    $totalAttendances = $totalUsers * $totalDays;
    $presentRate = 0;
    $absentRate = 0;
    $unmarkRate = 0;

    if($totalAttendances > 0){
        $presentRate = round(($summary['total_present'] / $totalAttendances) * 100) ;
        $absentRate = round(($summary['total_absent'] / $totalAttendances) * 100) ;
        $unmarkRate = round(($summary['total_unmarked'] / $totalAttendances) * 100);
    }
    $prevMonth = $date->copy()->subMonth()->format('Y-m-d');
    $nextMonth = $date->copy()->addMonth()->format('Y-m-d');
    
    return view('attendance.summary', compact(
        'users', 
        'days', 
        'date', 
        'userType',
        'usersId',
        'summary',
        'presentRate',
        'absentRate',
        'unmarkRate',
        'totalAttendances',
        'totalUsers',
        'totalDays',
        'prevMonth',
        'nextMonth'
    ));
}
    
    /**
     * Get users for summary based on type
     */
    private function getUsersForSummary($userType, $userId)
{
    $users = null;
    
    if ($userType === 'employee') {
        if($userId === 'all') {
            $users = Employee::active()->select('id', 'name', 'employee_id')
                ->get()
                ->map(function($item) {
                    $item->type = 'employee';
                    $item->type_label = 'Employee';
                    $item->type_badge_class = 'bg-primary';
                    return $item;
                });
        } else {
            $users = Employee::where('id',$userId)
                ->get()
                ->map(function($item){
                    $item->type = 'employee';
                    $item->type_label = 'Employee';
                    $item->type_badge_class = 'bg-primary';
                    return $item;
                });
        }
    }
    
    if ($userType === 'rider') {
        if($userId === 'all') {
            $users = Riders::active()->select('id', 'name', 'rider_id')
                ->get()
                ->map(function($item) {
                    $item->type = 'rider';
                    $item->type_label = 'Rider';
                    $item->type_badge_class = 'bg-success';
                    return $item;
                });
        } else {
            $users = Riders::where('id',$userId)
                ->get()
                ->map(function($item){
                    $item->type = 'employee';
                    $item->type_label = 'Employee';
                    $item->type_badge_class = 'bg-primary';
                    return $item;
                });
        }
    }
    
    // Sort by name and reset keys
    return $users->sortBy('name')->values();
}
    
    /**
     * Get user attendance history (AJAX endpoint for modal)
     */
    public function userHistory(Request $request, $userId)
    {
        $userType = $request->get('type');
        $months = $request->get('months', 3); // Last 3 months by default
        
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $attendances = Attendance::where('ref_id', $userId)
            ->where('ref_type', $userType)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
        
        // Get user details
        if ($userType === 'employee') {
            $user = Employee::find($userId);
        } else {
            $user = Riders::find($userId);
        }
        
        if ($request->ajax()) {
            return view('attendance.partials.user_history', compact('attendances', 'user', 'userType', 'months'));
        }
        
        return response()->json(['attendances' => $attendances, 'user' => $user]);
    }
    
    /**
     * Export summary to Excel/CSV
     */
    public function exportSummary(Request $request)
    {
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $userType = $request->get('user_type', 'all');
        
        $date = Carbon::parse($selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;
        
        // Get users and attendance data (similar to summary method)
        $users = $this->getUsersForSummary($userType);
        
        $attendances = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function($item) {
                return $item->ref_type . '_' . $item->ref_id;
            });
        
        // Prepare days array
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = $startOfMonth->copy()->addDays($day - 1)->format('Y-m-d');
        }
        
        // Set headers for CSV download
        $filename = 'attendance_summary_' . $date->format('Y_m') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Add UTF-8 BOM for Excel
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Create header row
        $header = ['ID', 'Name', 'Type', 'Email'];
        foreach ($days as $day) {
            $header[] = Carbon::parse($day)->format('d D');
        }
        $header[] = 'Total Present';
        $header[] = 'Total Late';
        $header[] = 'Total Half-Day';
        
        fputcsv($handle, $header);
        
        // Add data rows
        foreach ($users as $user) {
            $key = $user->type . '_' . $user->id;
            $userAttendances = $attendances->get($key, collect());
            
            $row = [
                $user->id,
                $user->name,
                $user->type_label,
                $user->email ?? ''
            ];
            
            $totalPresent = 0;
            $totalLate = 0;
            $totalHalfday = 0;
            
            foreach ($days as $day) {
                $attendance = $userAttendances->firstWhere('date', $day);
                
                if ($attendance) {
                    $statusCode = '';
                    switch($attendance->status) {
                        case 'present':
                            $statusCode = 'P';
                            $totalPresent++;
                            break;
                        case 'absent':
                            $statusCode = 'A';
                            break;
                        case 'late':
                            $statusCode = 'L';
                            $totalPresent++;
                            $totalLate++;
                            break;
                        case 'half-day':
                            $statusCode = 'HD';
                            $totalPresent++;
                            $totalHalfday++;
                            break;
                        case 'holiday':
                            $statusCode = 'H';
                            break;
                    }
                    $row[] = $statusCode;
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
        exit;
    }
}