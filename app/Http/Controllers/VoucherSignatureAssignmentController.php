<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Employee;
use App\Models\Settings;
use App\Models\User;
use App\Models\VoucherSignatureConfig;
use App\Models\VoucherSignatureSlot;
use App\Models\VoucherType;
use App\Services\ActivityLogger;
use App\Services\Module\ModuleLabelService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;

class VoucherSignatureAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(string $company_slug)
    {
        $this->authorizeView();

        $voucherTypes = VoucherType::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'code', 'label', 'display_order']);

        $configs = VoucherSignatureConfig::query()
            ->withCount('activeSlots')
            ->get()
            ->keyBy(fn (VoucherSignatureConfig $c) => strtoupper((string) $c->voucher_type_code));

        $moduleLabel = Settings::getMenuLabel('voucher_signature_assignment')
            ?: config('menu_labels.defaults.voucher_signature_assignment', 'Voucher Signature Assignment');

        return view('settings.voucher_signature_assignment.index', [
            'voucherTypes' => $voucherTypes,
            'configs' => $configs,
            'moduleLabel' => $moduleLabel,
            'company_slug' => $company_slug,
            'canEdit' => $this->canEdit(),
        ]);
    }

    public function storeModuleLabel(Request $request, string $company_slug)
    {
        $this->authorizeEdit();

        $request->validate(['module_label' => 'required|string|max:100']);
        app(ModuleLabelService::class)->saveLabel(
            'voucher_signature_assignment',
            trim((string) $request->input('module_label'))
        );

        return redirect()
            ->route('settings-panel.voucher-signature-assignment.index', ['company_slug' => $company_slug])
            ->with('success', 'Module name updated.');
    }

    public function configure(string $company_slug, string $code)
    {
        $this->authorizeView();

        $voucherType = $this->findVoucherType($code);
        if (!$voucherType) {
            Flash::error('Voucher type not found.');

            return redirect()->route('settings-panel.voucher-signature-assignment.index', [
                'company_slug' => $company_slug,
            ]);
        }

        $config = VoucherSignatureConfig::query()
            ->where('voucher_type_code', strtoupper($voucherType->code))
            ->with(['slots.department', 'slots.user', 'slots.employee'])
            ->first();

        $slots = $config?->slots ?? collect();

        $moduleLabel = Settings::getMenuLabel('voucher_signature_assignment')
            ?: config('menu_labels.defaults.voucher_signature_assignment', 'Voucher Signature Assignment');

        return view('settings.voucher_signature_assignment.configure', [
            'voucherType' => $voucherType,
            'config' => $config,
            'slots' => $slots,
            'departments' => Departments::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'first_name', 'last_name']),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'designation']),
            'moduleLabel' => $moduleLabel,
            'company_slug' => $company_slug,
            'canEdit' => $this->canEdit(),
            'assigneeTypes' => [
                VoucherSignatureSlot::ASSIGNEE_LABEL_ONLY => 'Label only (blank line)',
                VoucherSignatureSlot::ASSIGNEE_DEPARTMENT => 'Department',
                VoucherSignatureSlot::ASSIGNEE_USER => 'User',
                VoucherSignatureSlot::ASSIGNEE_EMPLOYEE => 'Employee',
            ],
        ]);
    }

    public function save(Request $request, string $company_slug, string $code)
    {
        $this->authorizeEdit();

        $voucherType = $this->findVoucherType($code);
        if (!$voucherType) {
            Flash::error('Voucher type not found.');

            return redirect()->route('settings-panel.voucher-signature-assignment.index', [
                'company_slug' => $company_slug,
            ]);
        }

        $typeCode = strtoupper((string) $voucherType->code);
        $companyId = CompanyContext::id();

        $validated = $request->validate([
            'slots' => 'nullable|array',
            'slots.*.role_label' => 'required|string|max:100',
            'slots.*.assignee_type' => ['required', Rule::in(VoucherSignatureSlot::assigneeTypes())],
            'slots.*.department_id' => 'nullable|integer',
            'slots.*.user_id' => 'nullable|integer',
            'slots.*.employee_id' => 'nullable|integer',
            'slots.*.designation_override' => 'nullable|string|max:255',
            'slots.*.is_active' => 'nullable|boolean',
        ]);

        $incoming = array_values($validated['slots'] ?? []);
        $before = $this->snapshotForType($typeCode);

        DB::transaction(function () use ($incoming, $typeCode, $companyId) {
            // Bulk replace uses ActivityLogger::custom below; skip per-row model events.
            VoucherSignatureConfig::withoutEvents(function () use ($incoming, $typeCode, $companyId) {
                VoucherSignatureSlot::withoutEvents(function () use ($incoming, $typeCode, $companyId) {
                    $config = VoucherSignatureConfig::query()
                        ->where('voucher_type_code', $typeCode)
                        ->first();

                    if (!$config) {
                        $config = VoucherSignatureConfig::create([
                            'company_id' => $companyId,
                            'voucher_type_code' => $typeCode,
                        ]);
                    }

                    $config->slots()->delete();

                    foreach ($incoming as $index => $row) {
                        $assigneeType = $row['assignee_type'];
                        $departmentId = null;
                        $userId = null;
                        $employeeId = null;

                        if ($assigneeType === VoucherSignatureSlot::ASSIGNEE_DEPARTMENT) {
                            $departmentId = $this->validatedDepartmentId($row['department_id'] ?? null);
                        } elseif ($assigneeType === VoucherSignatureSlot::ASSIGNEE_USER) {
                            $userId = $this->validatedUserId($row['user_id'] ?? null);
                        } elseif ($assigneeType === VoucherSignatureSlot::ASSIGNEE_EMPLOYEE) {
                            $employeeId = $this->validatedEmployeeId($row['employee_id'] ?? null);
                        }

                        VoucherSignatureSlot::create([
                            'company_id' => $companyId,
                            'voucher_signature_config_id' => $config->id,
                            'role_label' => trim((string) $row['role_label']),
                            'assignee_type' => $assigneeType,
                            'department_id' => $departmentId,
                            'user_id' => $userId,
                            'employee_id' => $employeeId,
                            'designation_override' => trim((string) ($row['designation_override'] ?? '')) ?: null,
                            'sort_order' => $index + 1,
                            'is_active' => array_key_exists('is_active', $row)
                                ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN)
                                : true,
                        ]);
                    }
                });
            });
        });

        $after = $this->snapshotForType($typeCode);
        ActivityLogger::custom(
            'updated signature workflow',
            'Voucher Signature Assignment',
            null,
            [
                'voucher_type_code' => $typeCode,
                'before' => $before,
                'after' => $after,
            ]
        );

        Flash::success('Signature assignment saved for ' . $voucherType->label . '.');

        return redirect()->route('settings-panel.voucher-signature-assignment.configure', [
            'company_slug' => $company_slug,
            'code' => $typeCode,
        ]);
    }

    public function clear(Request $request, string $company_slug, string $code)
    {
        $this->authorizeEdit();

        $voucherType = $this->findVoucherType($code);
        if (!$voucherType) {
            Flash::error('Voucher type not found.');

            return redirect()->route('settings-panel.voucher-signature-assignment.index', [
                'company_slug' => $company_slug,
            ]);
        }

        $typeCode = strtoupper((string) $voucherType->code);
        $before = $this->snapshotForType($typeCode);

        $config = VoucherSignatureConfig::query()
            ->where('voucher_type_code', $typeCode)
            ->first();

        if ($config) {
            VoucherSignatureConfig::withoutEvents(function () use ($config) {
                VoucherSignatureSlot::withoutEvents(function () use ($config) {
                    $config->slots()->delete();
                    $config->delete();
                });
            });
        }

        ActivityLogger::custom(
            'cleared signature workflow',
            'Voucher Signature Assignment',
            null,
            [
                'voucher_type_code' => $typeCode,
                'before' => $before,
                'after' => [],
            ]
        );

        Flash::success('Signature assignment cleared for ' . $voucherType->label . '. Legacy signature layout will be used.');

        return redirect()->route('settings-panel.voucher-signature-assignment.index', [
            'company_slug' => $company_slug,
        ]);
    }

    protected function findVoucherType(string $code): ?VoucherType
    {
        return VoucherType::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function snapshotForType(string $typeCode): array
    {
        $config = VoucherSignatureConfig::query()
            ->where('voucher_type_code', $typeCode)
            ->with('slots')
            ->first();

        if (!$config) {
            return [];
        }

        return $config->slots->map(fn (VoucherSignatureSlot $slot) => [
            'role_label' => $slot->role_label,
            'assignee_type' => $slot->assignee_type,
            'department_id' => $slot->department_id,
            'user_id' => $slot->user_id,
            'employee_id' => $slot->employee_id,
            'designation_override' => $slot->designation_override,
            'sort_order' => $slot->sort_order,
            'is_active' => $slot->is_active,
        ])->values()->all();
    }

    protected function validatedDepartmentId(mixed $id): ?int
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        return Departments::query()->whereKey($id)->exists() ? $id : null;
    }

    protected function validatedUserId(mixed $id): ?int
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        return User::query()->whereKey($id)->exists() ? $id : null;
    }

    protected function validatedEmployeeId(mixed $id): ?int
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        return Employee::query()->whereKey($id)->exists() ? $id : null;
    }

    protected function authorizeView(): void
    {
        abort_unless(
            user_can('voucher_signature_assignment_view')
                || user_can('voucher_signature_assignment_edit')
                || user_can('vouchers_view')
                || user_can('vouchers_edit'),
            403
        );
    }

    protected function authorizeEdit(): void
    {
        abort_unless($this->canEdit(), 403);
    }

    protected function canEdit(): bool
    {
        return user_can('voucher_signature_assignment_edit')
            || user_can('voucher_signature_assignment_create')
            || user_can('vouchers_edit');
    }
}
