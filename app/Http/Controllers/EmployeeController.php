<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ModuleCustomField;
use App\Models\ModuleFieldCategoryAssignment;
use App\Models\ModuleSettingCategory;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Accounts;
use App\DataTables\LedgerDataTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;

class EmployeeController extends Controller
{
    private const EMPLOYEE_MODULE_KEY = 'employees';
    private const EMPLOYEE_HIDDEN_FIELD_KEYS = [
        'personal_email',
        'personal_contact',
        'emergency_contact',
        'status',
        'profile_image',
        'account_id',
    ];

    private function employeeHiddenFieldLookup(): array
    {
        return array_flip(self::EMPLOYEE_HIDDEN_FIELD_KEYS);
    }

    private function employeeFieldsByCategory(): array
    {
        $moduleKey = self::EMPLOYEE_MODULE_KEY;
        $categories = ModuleSettingCategory::query()
            ->where('module_key', $moduleKey)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $categoryIds = $categories->pluck('id')->all();
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));
        $hiddenLookup = $this->employeeHiddenFieldLookup();
        $assignmentQuery = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $moduleKey)
            ->orderBy('display_order')
            ->orderBy('id');
        if (!empty($categoryIds)) {
            $assignmentQuery->whereIn('category_id', $categoryIds);
        } else {
            $assignmentQuery->whereRaw('1 = 0');
        }
        $fixedAssignments = $assignmentQuery->get()->filter(function ($assignment) {
            return (bool) ($assignment->is_visible ?? true);
        });
        $customQuery = ModuleCustomField::query()
            ->where('module_key', $moduleKey)
            ->orderBy('display_order')
            ->orderBy('id');
        if (!empty($categoryIds)) {
            $customQuery->whereIn('category_id', $categoryIds);
        } else {
            $customQuery->whereRaw('1 = 0');
        }
        $customFields = $customQuery->get();

        $normalized = [];
        foreach ($categories as $category) {
            $items = [];
            foreach ($fixedAssignments->where('category_id', $category->id) as $assignment) {
                if (!isset($employeeColumns[$assignment->field_key]) || isset($hiddenLookup[$assignment->field_key])) {
                    continue;
                }
                $spec = ['type' => 'text'];
                if ($assignment->field_key === 'branch_id') {
                    $spec['type'] = 'select';
                }
                if (!empty($assignment->input_type)) {
                    $spec['type'] = $assignment->input_type === 'dropdown' ? 'select' : $assignment->input_type;
                }
                if (is_array($assignment->input_config) && array_key_exists('options', $assignment->input_config)) {
                    $spec['options'] = $assignment->input_config['options'];
                }
                $spec['required'] = (bool) ($assignment->is_required ?? false);

                $items[] = (object) [
                    'kind' => 'fixed',
                    'field_key' => $assignment->field_key,
                    'label' => !empty($assignment->display_label) ? $assignment->display_label : (!empty($assignment->field_label) ? $assignment->field_label : ucwords(str_replace('_', ' ', $assignment->field_key))),
                    'spec' => $spec,
                ];
            }
            foreach ($customFields->where('category_id', $category->id) as $field) {
                $items[] = (object) [
                    'kind' => 'custom',
                    'field' => $field,
                ];
            }

            if (!empty($items)) {
                $normalized[] = (object) [
                    'category' => $category,
                    'fields' => $items,
                ];
            }
        }

        return $normalized;
    }

    private function employeeDynamicFieldRules(): array
    {
        $rules = [];
        $moduleKey = self::EMPLOYEE_MODULE_KEY;
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));

        ModuleFieldCategoryAssignment::query()
            ->where('module_key', $moduleKey)
            ->where('is_required', true)
            ->where(function ($q) {
                $q->where('is_visible', true)->orWhereNull('is_visible');
            })
            ->get(['field_key'])
            ->each(function ($assignment) use (&$rules, $employeeColumns) {
                $fieldKey = (string) $assignment->field_key;
                if (isset($employeeColumns[$fieldKey])) {
                    if (!isset($this->employeeHiddenFieldLookup()[$fieldKey])) {
                        $rules[$fieldKey] = 'required';
                    }
                }
            });

        ModuleCustomField::query()
            ->where('module_key', $moduleKey)
            ->whereNotNull('category_id')
            ->where('is_mandatory', true)
            ->get(['id'])
            ->each(function ($field) use (&$rules) {
                $rules['custom_field_values.' . $field->id] = 'required';
            });

        return $rules;
    }

    private function applyEmployeeDynamicInput(array &$validated, Request $request): void
    {
        $hiddenLookup = $this->employeeHiddenFieldLookup();
        foreach (ModuleFieldCategoryAssignment::query()->where('module_key', self::EMPLOYEE_MODULE_KEY)->get(['field_key']) as $assignment) {
            $fieldKey = (string) $assignment->field_key;
            if ($fieldKey !== '' && !isset($hiddenLookup[$fieldKey]) && $request->has($fieldKey)) {
                $validated[$fieldKey] = $request->input($fieldKey);
            }
        }
        $validated['custom_field_values'] = $request->input('custom_field_values', []);
    }

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
        $accounts = \App\Models\Accounts::where('ref_name', 'Rider')->get();
        $empId = 'EMP-' . ((Employee::latest()->first()->id ?? 0) + 1001);
        $riderCategories = \App\Models\RiderCategory::orderBy('display_order')->orderBy('id')->get();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        return view('employees.create', compact('nationalities', 'branches', 'departments', 'accounts', 'empId', 'riderCategories', 'fieldsByCategory'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate(array_merge([
            'employee_id' => 'required|string',
            'name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:employees,company_email',
            'company_contact' => 'nullable|string|max:20',
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
        ], $this->employeeDynamicFieldRules()));

        try {
            DB::beginTransaction();

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('employees/profile', 'public');
                $validated['profile_image'] = $path;
            }

            // Set created_by
            $validated['created_by'] = auth()->id();

            $this->applyEmployeeDynamicInput($validated, $request);

            // Create employee
            $employee = Employee::create($validated);



            // Handle account creation or linking
            if ($request->account === 'new') {
                // Create new account
                $account = Accounts::create([
                    'name' => $employee->name, // Use employee name as account name
                    'account_code' => 'EMP' . ($employee->id + 1000),
                    'ref_name' => 'employee',
                    'ref_id' => $employee->id,
                    'account_type' => 'Liability',
                    'parent_id' => '1', // Rider salaries payable account, for now we are hardcoding it, but ideally this should be configurable
                    'created_by' => auth()->id(),
                    'branch_id' => $employee->branch_id,
                ]);
                $employee->account_id = $account->id;
                $employee->save();
            } else {
                // Use existing account
                $account = Accounts::find($request->account_id);
                $account->update([
                    'name' => $employee->name,
                    'account_code' => 'EMP' . ($employee->id + 1000),
                    'ref_name' => 'employee',
                    'ref_id' => $employee->id,
                    'updated_by' => auth()->id(),
                    'branch_id' => $employee->branch_id,
                ]);
            }

            DB::commit();

            // Check if request is AJAX
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee created successfully!',
                    'redirect' => route('employees.index')
                ], 200);
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
            Log::error('Employee creation failed: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create employee. Please try again. Error:' . $e->getMessage(),
                ], 500);
            }
            // Redirect back with error
            Flash::error('Failed to create employee. Please try again. Error:' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($comapny_slug, Employee $employee)
    {
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        $result = $employee->toArray();
        return view('employees.show_clean', compact('employee', 'nationalities', 'branches', 'departments', 'fieldsByCategory', 'result'));
    }

    public function files($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.files', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function salary($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.salary', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function attendance($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.attendance', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function leaves($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.leaves', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function timeline($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.timeline', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function voucher($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.voucher', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($comapny_slug, Employee $employee)
    {
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();
        $riderCategories = \App\Models\RiderCategory::orderBy('display_order')->orderBy('id')->get();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        return view('employees.edit', compact('employee', 'nationalities', 'branches', 'departments', 'riderCategories', 'fieldsByCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $comapny_slug, Employee $employee)
    {
        $validated = $request->validate(array_merge([
            'employee_id' => 'required|string',
            'name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:employees,company_email,' . $employee->id,
            'company_contact' => 'nullable|string|max:20',
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
        ], $this->employeeDynamicFieldRules()));

        $this->applyEmployeeDynamicInput($validated, $request);

        // Handle file upload
        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($employee->profile_image) {
                Storage::disk('public')->delete($employee->profile_image);
            }

            $imagePath = $request->file('profile_image')->store('employees/profile', 'public');
            $validated['profile_image'] = $imagePath;
        }
        $validated['updated_by'] = auth()->id();
        $employee->update($validated);
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'redirect' => route('employees.index')
            ], 200);
        }
        Flash::success('Employee updated successfully.');
        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($comapny_slug, Employee $employee)
    {
        if (Transactions::where('account_id', $employee->account_id)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete employee with existing financial transactions. Please remove related transactions first.',
            ], 400);
        }

        // Delete profile image
        if ($employee->profile_image) {
            Storage::disk('public')->delete($employee->profile_image);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully!',
        ], 200);
    }

    public function ledger($comapny_slug, $id, LedgerDataTable $ledgerDataTable)
    {
        $employee = Employee::findOrFail($id);
        if (empty($employee) || !in_array($employee->branch_id, app('user_branches'))) {
            Flash::error('Employee not Found');
            return redirect(route('employees.index'));
        }
        $account = $employee->account_id;
        return $ledgerDataTable->with(['account_id' => $account])->render('employees.ledger', compact('employee'));
    }

    public function updateSection(Request $request, $comapny_slug,  $id)
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
            Log::error('Employee section update validation failed', [
                'employee_id' => $employee->id,
                'section' => $section,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($section == 'photo' && $request->hasFile('profile_image')) {
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
            Log::error('Failed to update employee status', [
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
