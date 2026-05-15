<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCategory;
use App\Models\EmployeeCustomField;
use App\Models\EmployeeDocumentType;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\EmployeeTopCategory;
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
    private function employeeFieldsByCategory(bool $includeCustomFields = true): array
    {
        return EmployeeCustomField::fieldsByCategoryForForm($includeCustomFields);
    }

    private function employeeDynamicFieldRules(?Employee $employee = null): array
    {
        $rules = [];
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));

        EmployeeFieldCategoryAssignment::query()
            ->where('is_required', true)
            ->where(function ($q) {
                $q->where('is_visible', true)->orWhereNull('is_visible');
            })
            ->get(['field_key'])
            ->each(function ($assignment) use (&$rules, $employeeColumns, $employee) {
                $fieldKey = (string) $assignment->field_key;
                if (!isset($employeeColumns[$fieldKey])) {
                    return;
                }
                $rules[$fieldKey] = $this->employeeFieldValidationRule($fieldKey, $employee);
            });

        EmployeeCustomField::query()
            ->whereNotNull('category_id')
            ->where(function ($q) {
                $q->where('is_visible', true)->orWhereNull('is_visible');
            })
            ->where('is_mandatory', true)
            ->get(['id'])
            ->each(function ($field) use (&$rules) {
                $rules['custom_field_values.' . $field->id] = 'required';
            });

        return $rules;
    }

    private function employeeFieldValidationRule(string $fieldKey, ?Employee $employee = null): string
    {
        $idSuffix = $employee ? ',' . $employee->id : '';

        return match ($fieldKey) {
            'company_email' => 'required|email|unique:employees,company_email' . $idSuffix,
            'employee_id' => 'required|string|unique:employees,employee_id' . $idSuffix,
            'emirate_id' => 'nullable|string|unique:employees,emirate_id' . $idSuffix,
            'passport' => 'nullable|string|unique:employees,passport' . $idSuffix,
            'nationality_id' => 'required|exists:countries,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'doj' => 'nullable|date',
            'dob' => 'nullable|date',
            'emirate_expiry' => 'nullable|date',
            'passport_expiry' => 'nullable|date',
            'visa_expiry' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive,on_leave',
            default => 'required',
        };
    }

    private function applyEmployeeDynamicInput(array &$validated, Request $request): void
    {
        $fieldKeys = EmployeeFieldCategoryAssignment::query()
            ->where(function ($q) {
                $q->where('is_visible', true)->orWhereNull('is_visible');
            })
            ->pluck('field_key');

        foreach ($fieldKeys as $fieldKey) {
            if ($request->has($fieldKey)) {
                $validated[$fieldKey] = $request->input($fieldKey);
            }
        }

        if ($request->has('custom_field_values')) {
            $validated['custom_field_values'] = $request->input('custom_field_values', []);
        }
    }

    private function employeeTableLabels(): array
    {
        $labels = [
            'employee_id' => 'Employee ID',
            'name' => 'Name',
            'company_contact' => 'Contact',
            'branch_id' => 'Branch',
            'department_id' => 'Department',
            'designation' => 'Designation',
            'doj' => 'Date of Joining',
            'documents_expiry' => 'Documents Expiry',
            'status' => 'Status',
            'actions' => 'Actions',
        ];

        EmployeeFieldCategoryAssignment::query()
            ->whereIn('field_key', array_keys($labels))
            ->get(['field_key', 'display_label'])
            ->each(function ($assignment) use (&$labels) {
                $fieldKey = (string) $assignment->field_key;
                $label = trim((string) ($assignment->display_label ?? ''));
                if ($label !== '' && isset($labels[$fieldKey])) {
                    $labels[$fieldKey] = $label;
                }
            });

        return $labels;
    }

    private function applyEmployeeIndexFilters($query, Request $request): void
    {
        if ($request->filled('quick_search')) {
            $search = $request->input('quick_search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('employee_id', 'like', '%' . $search . '%')
                    ->orWhere('company_email', 'like', '%' . $search . '%')
                    ->orWhere('company_contact', 'like', '%' . $search . '%');
            });
        }

        $statusFilters = $request->input('employee_status', []);
        if (!empty($statusFilters)) {
            $statusFilters = is_array($statusFilters) ? $statusFilters : [$statusFilters];
            $query->whereIn('status', $statusFilters);
        }

        $topColumn = trim((string) $request->input('employee_top_column', ''));
        $topValue = $request->input('employee_top_value');
        if ($topColumn !== '' && $topValue !== null && $topValue !== '' && Schema::hasColumn('employees', $topColumn)) {
            $query->where($topColumn, $topValue);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employee::query()->with('branch', 'department', 'nationality');
        $this->applyEmployeeIndexFilters($query, $request);
        $employees = $query->orderBy('name')->get();
        $employeeTableLabels = $this->employeeTableLabels();
        $employeeTopCategories = EmployeeTopCategory::with([
            'options' => function ($q) {
                $q->where('is_active', true)->orderBy('display_order')->orderBy('id');
            },
        ])
            ->where('show_in_top_bar', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('employees.index', compact('employees', 'employeeTableLabels', 'employeeTopCategories'));
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
        $employeeCategories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        return view('employees.create', compact('nationalities', 'branches', 'departments', 'accounts', 'empId', 'employeeCategories', 'fieldsByCategory'));
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
        ], $this->employeeDynamicFieldRules(null)));

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

            Flash::success('Employee created successfully.');
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

        $expectedFiles = EmployeeDocumentType::expectedFilesStructure();

        $files = \App\Support\CompanyQuery::table('files')
            ->where('type', 'employee')
            ->where('type_id', $id)
            ->get();

        $missingFiles = [];

        foreach ($expectedFiles['single'] as $key => $name) {
            $found = false;
            foreach ($files as $employeeFile) {
                if (str_contains(strtolower((string) $employeeFile->name), $key)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missingFiles[$key] = $name;
            }
        }

        foreach ($expectedFiles['dual'] as $key => $sides) {
            $foundFront = false;
            $foundBack = false;
            foreach ($files as $employeeFile) {
                $name = strtolower((string) $employeeFile->name);
                if (!str_contains($name, $key)) {
                    continue;
                }

                if (str_contains($name, 'back') || str_contains($name, 'second')) {
                    $foundBack = true;
                } elseif (str_contains($name, 'front') || str_contains($name, 'first')) {
                    $foundFront = true;
                } else {
                    $foundFront = true;
                    $foundBack = true;
                }
            }

            if (!$foundFront) {
                $missingFiles[$key . '_front'] = $sides['front'];
            }
            if (!$foundBack) {
                $missingFiles[$key . '_back'] = $sides['back'];
            }
        }

        return view('employees.files', compact('employee', 'nationalities', 'branches', 'departments', 'missingFiles', 'files'));
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
        $employeeCategories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        return view('employees.edit', compact('employee', 'nationalities', 'branches', 'departments', 'employeeCategories', 'fieldsByCategory'));
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
        ], $this->employeeDynamicFieldRules($employee)));

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
