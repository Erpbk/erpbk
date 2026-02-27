<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Accounts;
use App\DataTables\LedgerDataTable;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $employees = Employee::all()->sortBy('name')->load('branch', 'department', 'nationality');

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();
        $accounts = \App\Models\Accounts::where('ref_name','Rider')->get();
        $empId = 'EMP-'.( Employee::latest()->first()->id + 1001);
        return view('employees.create', compact('nationalities', 'branches', 'departments', 'accounts', 'empId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:employees,company_email',
            'personal_email' => 'required|email|unique:employees,personal_email',
            'personal_contact' => 'nullable|string|max:20',
            'company_contact' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'nationality_id' => 'required|exists:countries,id',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'emirate_id' => 'nullable|string|unique:employees,emirate_id',
            'emirate_expiry' => 'nullable|date',
            'passport' => 'nullable|string|unique:employees,passport',
            'passport_expiry' => 'nullable|date',
            'doj' => 'required|date',
            'dob' => 'required|date|before:today',
            'visa_sponsor' => 'nullable|string|max:255',
            'visa_occupation' => 'nullable|string|max:255',
            'visa_expiry' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave',
            'address' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
            'account' => 'required|in:new,existing',
            'account_id' => 'nullable|required_if:account,existing|exists:accounts,id',
        ]);

        try {
            DB::beginTransaction();

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('employees/profile', 'public');
                $validated['profile_image'] = $path;
            }

            // Set created_by
            $validated['created_by'] = auth()->id();

            // Create employee
            $employee = Employee::create($validated);

            

            // Handle account creation or linking
            if ($request->account === 'new') {
                // Create new account
                $account = Accounts::create([
                    'name' => $employee->name, // Use employee name as account name
                    'account_code' => 'EMP'.($employee->id+1000),
                    'ref_name' => 'employee',
                    'ref_id' => $employee->id,
                    'account_type' =>'Liability',
                    'parent_id' => '1', // Rider salaries payable account, for now we are hardcoding it, but ideally this should be configurable
                    'created_by' => auth()->id(),
                ]);
                $employee->account_id = $account->id;
                $employee->save();
            } else {
                // Use existing account
                $account = Accounts::find($request->account_id);
                $account->update([
                    'name' => $employee->name,
                    'account_code' => 'EMP'.($employee->id+1000),
                    'ref_name' => 'employee',
                    'ref_id' => $employee->id,
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            // Check if request is AJAX
            if (request()->ajax()) {
                return response()->json([
                'success' => true,
                'message' => 'Employee created successfully!',
                'redirect' => route('employees.index')
                ],200);
            }

            Flash::success('Rider created successfully.');
            return redirect(route('employees.index'));

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded image if exists
            if (isset($validated['profile_image'])) {
                Storage::disk('public')->delete($validated['profile_image']);
            }

            // Log the error
            \Log::error('Employee creation failed: ' . $e->getMessage());

            if(request()->ajax()){
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create employee. Please try again. Error:'.$e->getMessage(),
                ],500);
            }
            // Redirect back with error
            Flash::error('Failed to create employee. Please try again. Error:'.$e->getMessage());
            return redirect() ->back() ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();
        return view('employees.show', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();
        return view('employees.edit', compact('employee','nationalities', 'branches', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:employees,company_email,'.$employee->id,
            'personal_email' => 'required|email|unique:employees,personal_email,'.$employee->id,
            'personal_contact' => 'nullable|string|max:20',
            'company_contact' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'nationality_id' => 'required|exists:countries,id',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'emirate_id' => 'nullable|string|unique:employees,emirate_id',
            'emirate_expiry' => 'nullable|date',
            'passport' => 'nullable|string|unique:employees,passport',
            'passport_expiry' => 'nullable|date',
            'doj' => 'required|date',
            'dob' => 'required|date|before:today',
            'visa_sponsor' => 'nullable|string|max:255',
            'visa_occupation' => 'nullable|string|max:255',
            'visa_expiry' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave',
            'address' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        // Handle file upload
        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($employee->profile_image) {
                Storage::disk('public')->delete($employee->profile_image);
            }
            
            $imagePath = $request->file('profile_image')->store('employees/profile', 'public');
            $validated['profile_image'] = $imagePath;
        }

        $employee->update($validated);
        if(request()->ajax()){
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'redirect' => route('employees.index')
            ],200);
        }
        Flash::success('Employee updated successfully.');
        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        if(Transactions::where('account_id', $employee->account_id)->count() > 0){
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete employee with existing financial transactions. Please remove related transactions first.',
            ],400);
        }

        // Delete profile image
        if ($employee->profile_image) {
            Storage::disk('public')->delete($employee->profile_image);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully!',
        ],200);
    }

    public function ledger($id, LedgerDataTable $ledgerDataTable)
    {
        $employee = Employee::findOrFail($id);
        $account = $employee->account_id;
        return $ledgerDataTable->with(['account_id' => $account])->render('employees.ledger', compact('employee'));
    }

    public function updateSection(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $section = $request->input('section');
        
        // Validate section exists
        $validSections = ['personal', 'employment', 'documents', 'notes', 'photo'];
        if (!in_array($section, $validSections)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid section specified'
            ], 400);
        }
        
        // Get validation rules
        $rules = $this->getSectionRules($section);
        
        // Create validator
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            \Log::error('Employee section update validation failed', [
                'employee_id' => $employee->id,
                'section' => $section,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if($section == 'photo' && $request->hasFile('profile_image')) {
            // Handle file upload
            if ($employee->profile_image) {
                Storage::disk('public')->delete($employee->profile_image);
            }
            
            $imagePath = $request->file('profile_image')->store('employees/profile', 'public');
            $employee->profile_image = $imagePath;
            $employee->save();
             return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'data' => $employee,
                'image_url' => Storage::url($imagePath)
            ]);
        }
        
        // Update employee
        $employee->update($request->all());
        $employee->refresh();
        
        return response()->json([
            'success' => true,
            'message' => ucfirst($section) . ' information updated successfully',
            'data' => $employee
        ]);
    }

    private function getSectionRules($section)
    {
        switch ($section) {
            case 'personal':
                return [
                    'name' => 'required|string|max:255',
                    'dob' => 'required|date',
                    'nationality_id' => 'required|exists:countries,id',
                    'personal_email' => 'nullable|email|max:255',
                    'personal_contact' => 'nullable|string|max:20',
                    'emergency_contact' => 'nullable|string|max:20',
                    'address' => 'nullable|string'
                ];
                
            case 'employment':
                return [
                    'department_id' => 'required|exists:departments,id',
                    'designation' => 'nullable|string|max:255',
                    'branch_id' => 'required|exists:branches,id',
                    'doj' => 'required|date',
                    'salary' => 'nullable|numeric|min:0',
                    'company_email' => 'nullable|email|max:255',
                    'company_contact' => 'nullable|string|max:20'
                ];
                
            case 'documents':
                return [
                    'emirate_id' => 'nullable|string|max:50',
                    'passport' => 'nullable|string|max:50',
                    'visa_sponsor' => 'nullable|string|max:255',
                    'visa_occupation' => 'nullable|string|max:255',
                    'emirate_expiry' => 'nullable|date',
                    'passport_expiry' => 'nullable|date',
                    'visa_expiry' => 'nullable|date'
                ];
                
            case 'notes':
                return [
                    'notes' => 'nullable|string'
                ];
                
            case 'photo':
                return [
                    'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                ];
                
            default:
                return [];
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'status' => 'required|in:active,inactive,on_leave'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);
            $employee->status = $request->status;
            $employee->save();

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update employee status', [
                'employee_id' => $request->employee_id,
                'status' => $request->status,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee status: ' . $e->getMessage()
            ], 500);
        }
    }
}