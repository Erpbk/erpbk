<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCategory;
use App\Models\EmployeeCustomField;
use App\Models\EmployeeDocumentType;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\EmployeeTopCategory;
use App\Models\Attendance;
use App\Models\SimHistory;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Accounts;
use App\Models\Payment;
use App\DataTables\LedgerDataTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Traits\GlobalPagination;
use Laracasts\Flash\Flash;

class EmployeeController extends Controller
{
    use AppliesModuleTopBarFilters, GlobalPagination;
    private function employeeFieldsByCategory(bool $includeCustomFields = true): array
    {
        return EmployeeCustomField::fieldsByCategoryForForm($includeCustomFields);
    }

    /**
     * Required custom + fixed fields from employee settings (same pattern as riders dynamicFieldRules).
     */
    private function employeeDynamicFieldRules(): array
    {
        $rules = [];
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));
        $assignmentTable = (new EmployeeFieldCategoryAssignment())->getTable();
        $hasRequiredColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_required');
        $hasVisibleColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_visible');

        if ($hasRequiredColumn) {
            $query = EmployeeFieldCategoryAssignment::query()->where('is_required', 1);
            if ($hasVisibleColumn) {
                $query->where(function ($q) {
                    $q->where('is_visible', 1)->orWhereNull('is_visible');
                });
            }
            $query->get(['field_key'])->each(function ($assignment) use (&$rules, $employeeColumns) {
                $fieldKey = (string) $assignment->field_key;
                if (!isset($employeeColumns[$fieldKey])) {
                    return;
                }
                $rules[$fieldKey] = 'required';
            });
        }

        EmployeeCustomField::query()
            ->where('is_mandatory', 1)
            ->whereNotNull('category_id')
            ->where(function ($q) {
                $q->where('is_visible', 1)->orWhereNull('is_visible');
            })
            ->get(['id'])
            ->each(function ($field) use (&$rules) {
                $rules['custom_field_values.' . $field->id] = 'required';
            });

        return $rules;
    }

    /**
     * Build employee create/update validation from Employee Settings assignments (same as riders).
     */
    private function employeeValidationRules(?int $ignoreEmployeeId = null): array
    {
        $rules = Employee::$rules;
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));
        $assignmentTable = (new EmployeeFieldCategoryAssignment())->getTable();
        $hasRequiredColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_required');
        $hasVisibleColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_visible');

        $normalizePresenceRule = function ($rule, bool $required) {
            if (is_array($rule)) {
                $tokens = array_values(array_filter($rule, function ($item) {
                    return !is_string($item) || ($item !== 'required' && $item !== 'nullable');
                }));
                array_unshift($tokens, $required ? 'required' : 'nullable');

                return $tokens;
            }

            $tokens = array_values(array_filter(explode('|', (string) $rule), function ($item) {
                return $item !== '' && $item !== 'required' && $item !== 'nullable';
            }));
            array_unshift($tokens, $required ? 'required' : 'nullable');

            return implode('|', $tokens);
        };

        $assignmentColumns = ['field_key'];
        if ($hasRequiredColumn) {
            $assignmentColumns[] = 'is_required';
        }
        if ($hasVisibleColumn) {
            $assignmentColumns[] = 'is_visible';
        }

        $assignments = EmployeeFieldCategoryAssignment::query()
            ->get($assignmentColumns)
            ->keyBy('field_key');
        $fixedKeys = EmployeeCustomField::allFixedFieldKeys();

        foreach ($fixedKeys as $fieldKey) {
            if (!isset($employeeColumns[$fieldKey])) {
                continue;
            }
            $assignment = $assignments->get($fieldKey);
            $isVisible = !$hasVisibleColumn || !$assignment || $assignment->is_visible === null
                ? true
                : (bool) $assignment->is_visible;
            $isRequired = ($assignment && $hasRequiredColumn) ? (bool) $assignment->is_required : false;
            $baseRule = $rules[$fieldKey] ?? 'nullable';
            $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isVisible && $isRequired);
        }

        $rules['account'] = 'nullable|in:new,existing';
        $rules['account_id'] = 'nullable|required_if:account,existing|exists:accounts,id';

        foreach (['employee_id', 'company_email', 'personal_email', 'passport', 'emirate_id'] as $uniqueKey) {
            if (!isset($employeeColumns[$uniqueKey])) {
                continue;
            }
            $baseRule = $rules[$uniqueKey] ?? 'nullable|string|max:191';
            $tokens = is_array($baseRule) ? $baseRule : explode('|', (string) $baseRule);
            $tokens = array_values(array_filter($tokens, function ($token) {
                return !(is_string($token) && str_starts_with($token, 'unique:'));
            }));
            $tokens[] = \App\Support\CompanyScope::unique('employees', $uniqueKey, $ignoreEmployeeId);
            $rules[$uniqueKey] = $tokens;
        }

        return array_merge($rules, $this->employeeDynamicFieldRules());
    }

    private function nextEmployeeId(): string
    {
        return 'EMP-' . ((Employee::latest('id')->value('id') ?? 0) + 1001);
    }

    /**
     * Collect mass-assignable employee attributes from the request (same approach as riders store/update).
     */
    private function employeeAttributesFromRequest(Request $request, ?Employee $employee = null): array
    {
        $fillable = (new Employee())->getFillable();
        $input = array_intersect_key($request->all(), array_flip($fillable));

        foreach ($input as $key => $value) {
            if ($value === '' || $value === null) {
                unset($input[$key]);
            }
        }

        if ($request->has('custom_field_values')) {
            $input['custom_field_values'] = $request->input('custom_field_values', []);
        }

        if ($employee === null) {
            if (empty($input['employee_id'])) {
                $input['employee_id'] = $this->nextEmployeeId();
            }
            if (empty($input['status'])) {
                $input['status'] = 'active';
            }
        }

        return $input;
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

    /**
     * Column list for employees index table and column control panel.
     */
    private function buildEmployeesIndexTableColumns(): array
    {
        $employeeColumns = Schema::getColumnListing('employees');
        $employeeColumnsSet = array_flip($employeeColumns);
        $exclude = array_values(array_unique(array_merge(
            ['id', 'created_at', 'updated_at', 'deleted_at', 'custom_field_values', 'notes'],
            EmployeeCustomField::removedEmployeeColumns(),
        )));
        $excludedSet = array_flip($exclude);

        $assignedFixedColumns = EmployeeFieldCategoryAssignment::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['field_key', 'display_label'])
            ->filter(function ($assignment) use ($employeeColumnsSet, $excludedSet) {
                $key = (string) $assignment->field_key;
                return isset($employeeColumnsSet[$key]) && !isset($excludedSet[$key]);
            })
            ->values();

        $dbColumns = $assignedFixedColumns->pluck('field_key')->all();
        if (Schema::hasColumn('employees', 'status') && !in_array('status', $dbColumns, true)) {
            $dbColumns[] = 'status';
        }

        $assignedCustomFields = EmployeeCustomField::query()
            ->whereNotNull('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'label']);

        $labelMap = $assignedFixedColumns->mapWithKeys(function ($assignment) {
            $label = trim((string) ($assignment->display_label ?? ''));
            return [$assignment->field_key => $label !== '' ? $label : EmployeeCustomField::humanizeFieldKey($assignment->field_key)];
        })->all();

        $preferredOrder = [
            'name',
            'company_contact',
            'branch_id',
            'department_id',
            'designation',
            'doj',
            'status',
        ];

        $columns = [];
        $added = [];
        $makeTitle = function ($key) use ($labelMap) {
            return $labelMap[$key] ?? EmployeeCustomField::humanizeFieldKey($key);
        };

        foreach ($preferredOrder as $key) {
            if (in_array($key, $dbColumns, true)) {
                $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
                $added[$key] = true;
            }
        }

        foreach ($dbColumns as $key) {
            if (empty($added[$key])) {
                $columns[] = ['data' => $key, 'title' => $makeTitle($key)];
                $added[$key] = true;
            }
        }

        foreach ($assignedCustomFields as $cf) {
            $columns[] = [
                'data' => 'custom_field_values.' . $cf->id,
                'title' => trim((string) $cf->label) !== '' ? $cf->label : ('Custom Field #' . $cf->id),
            ];
        }

        $columns[] = ['data' => 'documents_expiry', 'title' => $labelMap['documents_expiry'] ?? 'Documents Expiry'];

        return array_merge($columns, [
            ['data' => 'action', 'title' => 'Actions'],
            ['data' => 'search', 'title' => 'Search'],
            ['data' => 'control', 'title' => 'Control'],
        ]);
    }

    private function applyEmployeeIndexFilters($query, Request $request): void
    {
        if ($request->filled('employee_id')) {
            $query->where('employee_id', 'like', '%' . $request->input('employee_id') . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

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

        $this->applyModuleTopBarFilters($query, $request, 'employees');

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
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = Employee::query()->with('branch', 'department', 'nationality');
        $this->applyEmployeeIndexFilters($query, $request);
        $query->orderBy('name');
        $data = $this->applyPagination($query, $paginationParams);

        return view('employees.index', array_merge([
            'data' => $data,
            'tableColumns' => $this->buildEmployeesIndexTableColumns(),
        ], $this->moduleTopBarListingData($request, 'employees')));
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
        $empId = $this->nextEmployeeId();
        $employeeCategories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();
        $fieldsByCategory = $this->employeeFieldsByCategory();
        return view('employees.create', compact('nationalities', 'branches', 'departments', 'accounts', 'empId', 'employeeCategories', 'fieldsByCategory'));
    }

    /**
     * Ensure core create fields exist before validation (employee_id may be hidden in settings).
     */
    private function prepareEmployeeStoreRequest(Request $request): void
    {
        if (!$request->filled('employee_id')) {
            $request->merge(['employee_id' => $this->nextEmployeeId()]);
        }
        if (!$request->filled('status')) {
            $request->merge(['status' => 'active']);
        }
        if (!$request->filled('account')) {
            $request->merge(['account' => 'new']);
        }
    }

    public function store(Request $request)
    {
        $this->prepareEmployeeStoreRequest($request);
        $request->validate($this->employeeValidationRules());

        try {
            DB::beginTransaction();

            $input = $this->employeeAttributesFromRequest($request);
            $input['created_by'] = auth()->id();

            if ($request->hasFile('profile_image')) {
                $input['profile_image'] = $request->file('profile_image')->store('employees/profile', 'public');
            }

            $employee = Employee::create($input);



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
            if (isset($input['profile_image'])) {
                Storage::disk('public')->delete($input['profile_image']);
            }

            // Log the error
            Log::error('Employee creation failed: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create employee. Please try again. Error: ' . $e->getMessage(),
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
        $result = $employee->toArray();

        $month = request('month', date('Y-m'));
        $monthStart = \Carbon\Carbon::parse($month . '-01');
        $year = (int) $monthStart->format('Y');
        $monthNum = (int) $monthStart->format('m');

        $attendances = collect();
        $summary = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'half_day' => 0,
            'on_leave' => 0,
            'holiday' => 0,
        ];

        if (Schema::hasTable('attendance')) {
            $attendances = Attendance::where('ref_type', 'employee')
                ->where('ref_id', $id)
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            $summary['total'] = $attendances->count();
            foreach ($attendances as $row) {
                $key = str_replace(' ', '_', strtolower((string) $row->status));
                if (array_key_exists($key, $summary)) {
                    $summary[$key]++;
                }
            }
        }

        return view('employees.attendance', compact(
            'employee',
            'nationalities',
            'branches',
            'departments',
            'result',
            'attendances',
            'month',
            'summary'
        ));
    }

    public function leaves($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        return view('employees.leaves', compact('employee', 'nationalities', 'branches', 'departments'));
    }

    public function history($comapny_slug, $id)
    {
        $employee = Employee::findOrFail($id);
        $nationalities = \App\Models\Countries::all();
        $branches = \App\Models\Branch::active()->get();
        $departments = \App\Models\Departments::all();

        $simHistories = null;
        $simHistoryCount = 0;

        if (Schema::hasTable('sim_histories') && Schema::hasColumn('sim_histories', 'employee_id')) {
            $simHistories = SimHistory::with('sim')
                ->where('employee_id', $id)
                ->orderByDesc('note_date')
                ->orderByDesc('id')
                ->paginate(50);
            $simHistoryCount = SimHistory::where('employee_id', $id)->count();
        }

        return view('employees.history', compact(
            'employee',
            'nationalities',
            'branches',
            'departments',
            'simHistories',
            'simHistoryCount'
        ));
    }

    public function payment(Request $request)
    {
        $accountIds = Employee::whereNotNull('account_id')->pluck('account_id')->toArray();

        if (empty($accountIds)) {
            Flash::error('No Employees found');
            return redirect()->back();
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Payment::query()->latest('date_of_payment');
        $query->whereIn('payee_account_id', $accountIds);

        $data = $this->applyPagination($query, $paginationParams);
        return view('employees.payments', compact('data'));
    }

    public function sendEmail($company_slug, $id, Request $request)
    {
        $employee = Employee::findOrFail($id);

        if ($request->isMethod('post')) {
            $user = Auth::user();
            $emailService = app(\App\Services\Email\UserEmailService::class);
            $smtpPrep = $emailService->prepareCompanySmtp($user);
            if (!$smtpPrep['ready']) {
                return response()->json([
                    'success' => false,
                    'message' => $smtpPrep['message'],
                ], $smtpPrep['status'] ?? 422);
            }
            $fromEmail = $smtpPrep['from_email'];
            $fromName = $smtpPrep['from_name'];

            $toEmail = $request->input('email_to');
            if (!is_string($toEmail) || trim($toEmail) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee email address is missing.',
                ], 422);
            }
            $toEmail = trim($toEmail);

            $subject = is_string($request->input('email_subject')) && trim($request->input('email_subject')) !== ''
                ? trim($request->input('email_subject'))
                : '(Employee email)';

            $brandingService = app(\App\Services\Email\CompanyEmailBrandingService::class);
            $data = $brandingService->mergeIntoMailData([
                'html' => $request->input('email_message'),
            ]);

            $ccEmails = $emailService->getCcRecipientEmails($user);

            Mail::send('emails.general', $data, function ($message) use ($toEmail, $subject, $fromEmail, $fromName, $ccEmails) {
                $message->to([$toEmail]);
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                } else {
                    $adminCc = env('ADMIN_CC_EMAIL');
                    if (!empty($adminCc)) {
                        $message->cc($adminCc);
                    }
                }
                $message->from($fromEmail, $fromName);
                $message->replyTo($fromEmail, $fromName);
                $message->subject($subject);
                $message->priority(3);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.',
            ]);
        }

        return view('employees.send_email', compact('employee'));
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
        $result = $employee->toArray();
        return view('employees.edit', compact('employee', 'nationalities', 'branches', 'departments', 'employeeCategories', 'fieldsByCategory', 'result'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $comapny_slug, Employee $employee)
    {
        $request->validate($this->employeeValidationRules((int) $employee->id));

        $input = $this->employeeAttributesFromRequest($request, $employee);

        if ($request->hasFile('profile_image')) {
            if ($employee->profile_image) {
                Storage::disk('public')->delete($employee->profile_image);
            }

            $input['profile_image'] = $request->file('profile_image')->store('employees/profile', 'public');
        }

        $input['updated_by'] = auth()->id();
        $employee->update($input);
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

    /**
     * Update a single employee column from profile sidebar cards.
     */
    public function updateProfileField(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'column' => 'required|string|max:80',
            'value' => 'nullable|string|max:255',
        ]);

        $column = $validated['column'];
        if (!Schema::hasColumn('employees', $column)) {
            return response()->json(['success' => false, 'message' => 'Invalid column.'], 422);
        }

        $allowedColumns = EmployeeTopCategory::whereNotNull('employee_column')
            ->pluck('employee_column')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!in_array($column, $allowedColumns, true)) {
            return response()->json(['success' => false, 'message' => 'Column is not allowed.'], 422);
        }

        try {
            $employee = Employee::findOrFail($validated['employee_id']);
            $employee->{$column} = $validated['value'] ?? null;
            $employee->save();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'column' => $column,
                'value' => $employee->{$column},
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update employee profile field', [
                'employee_id' => $validated['employee_id'],
                'column' => $column,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee: ' . $e->getMessage(),
            ], 500);
        }
    }
}
