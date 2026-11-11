<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Settings;
use App\Models\User;
use App\Models\VoucherSignatureConfig;
use App\Models\VoucherSignatureSlot;
use App\Models\VoucherType;
use App\Services\ActivityLogger;
use App\Services\Email\CompanyEmailBrandingService;
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
            ->with(['slots' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->withCount('activeSlots')
            ->get()
            ->keyBy(fn (VoucherSignatureConfig $c) => strtoupper((string) $c->voucher_type_code));

        $documents = $voucherTypes->map(function (VoucherType $type) use ($configs) {
            $key = strtoupper((string) $type->code);
            $config = $configs->get($key);
            $slots = $config?->slots ?? collect();
            $activeFlow = $slots
                ->where('is_active', true)
                ->values()
                ->map(fn (VoucherSignatureSlot $s) => [
                    'label' => $s->role_label,
                ])
                ->all();

            $moduleLabels = $type->module_labels;
            $moduleLabel = $moduleLabels[0] ?? 'Vouchers';

            return [
                'code' => (string) $type->code,
                'label' => (string) $type->label,
                'kind' => 'v',
                'kind_label' => 'Voucher',
                'module' => $moduleLabel,
                'configured' => $slots->isNotEmpty(),
                'active_count' => (int) ($config->active_slots_count ?? 0),
                'flow' => $activeFlow,
            ];
        })->values();

        $moduleLabel = Settings::getMenuLabel('voucher_signature_assignment')
            ?: config('menu_labels.defaults.voucher_signature_assignment', 'Voucher Signature Assignment');

        return view('settings.voucher_signature_assignment.index', [
            'documents' => $documents,
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

    public function reorder(Request $request, string $company_slug)
    {
        $this->authorizeEdit();

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|string|max:20',
        ]);

        $codes = collect($validated['order'])
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($codes) {
            foreach ($codes as $index => $code) {
                VoucherType::query()
                    ->whereRaw('UPPER(code) = ?', [$code])
                    ->update(['display_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true]);
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

        $copySources = VoucherSignatureConfig::query()
            ->where('voucher_type_code', '!=', strtoupper((string) $voucherType->code))
            ->whereHas('slots')
            ->with(['slots' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->get()
            ->keyBy(fn (VoucherSignatureConfig $c) => strtoupper((string) $c->voucher_type_code));

        $typeLabels = VoucherType::query()
            ->whereIn('code', $copySources->keys()->all())
            ->pluck('label', 'code');

        $copyPayload = $copySources->map(function (VoucherSignatureConfig $cfg) use ($typeLabels) {
            $code = strtoupper((string) $cfg->voucher_type_code);

            return [
                'code' => $code,
                'label' => (string) ($typeLabels[$cfg->voucher_type_code] ?? $code),
                'header' => $cfg->resolvedHeaderSettings(),
                'position' => $cfg->resolvedSignaturePosition(),
                'slots' => $cfg->slots->map(fn (VoucherSignatureSlot $s) => [
                    'role_label' => $s->role_label,
                    'assignee_type' => $s->assignee_type,
                    'department_id' => $s->department_id,
                    'user_id' => $s->user_id,
                    'employee_id' => $s->employee_id,
                    'designation_override' => $s->designation_override,
                    'is_active' => (bool) $s->is_active,
                ])->values()->all(),
            ];
        })->values();

        $moduleLabel = Settings::getMenuLabel('voucher_signature_assignment')
            ?: config('menu_labels.defaults.voucher_signature_assignment', 'Voucher Signature Assignment');

        $branding = app(CompanyEmailBrandingService::class)->resolve(CompanyContext::id());
        $companyPreview = [
            'name' => $branding['name'] ?? config('app.name'),
            'logo_url' => $branding['logo_url'] ?? null,
            'tel' => !empty($branding['phone']) ? ('Tel: ' . $branding['phone']) : '',
            'email' => (string) ($branding['email'] ?? ''),
            'addr' => (string) ($branding['address'] ?? ''),
            'trn' => ($trn = trim((string) (Common::getSetting('vat_number') ?? ''))) !== '' ? ('TRN: ' . $trn) : '',
            'web' => (string) (Common::getSetting('company_website') ?? ''),
            'vat' => 'VAT: [VAT %]',
            'po' => ($po = trim((string) (Common::getSetting('po_box') ?? ''))) !== '' ? ('PO Box: ' . $po) : 'PO Box: [PO Box]',
        ];

        return view('settings.voucher_signature_assignment.configure', [
            'voucherType' => $voucherType,
            'config' => $config,
            'slots' => $slots,
            'headerSettings' => $config
                ? $config->resolvedHeaderSettings()
                : VoucherSignatureConfig::defaultHeaderSettings(),
            'signaturePosition' => $config
                ? $config->resolvedSignaturePosition()
                : VoucherSignatureConfig::POSITION_BOTTOM,
            'departments' => Departments::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'first_name', 'last_name']),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'designation']),
            'moduleLabel' => $moduleLabel,
            'company_slug' => $company_slug,
            'canEdit' => $this->canEdit(),
            'copySources' => $copyPayload,
            'companyPreview' => $companyPreview,
            'detailOptions' => [
                ['key' => 'tel', 'label' => 'Tel'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'addr', 'label' => 'Address'],
                ['key' => 'trn', 'label' => 'TRN'],
                ['key' => 'web', 'label' => 'Website'],
                ['key' => 'vat', 'label' => 'VAT %'],
                ['key' => 'po', 'label' => 'PO Box'],
            ],
            'assigneeTypes' => [
                VoucherSignatureSlot::ASSIGNEE_LABEL_ONLY => 'Blank line',
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
            'signature_position' => ['nullable', Rule::in(VoucherSignatureConfig::signaturePositions())],
            'header' => 'nullable|array',
            'header.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'header.height' => 'nullable|numeric|min:80|max:220',
            'header.logo' => 'nullable|numeric|min:60|max:220',
            'header.name' => 'nullable|numeric|min:14|max:36',
            'header.det' => 'nullable|numeric|min:9|max:16',
            'header.line' => 'nullable|boolean',
            'header.on' => 'nullable|array',
            'apply_header_to_all' => 'nullable|boolean',
            'redirect_to' => 'nullable|string|in:list,configure',
        ]);

        $incoming = array_values($validated['slots'] ?? []);
        $header = VoucherSignatureConfig::normalizeHeaderSettings([
            'align' => $validated['header']['align'] ?? null,
            'height' => $validated['header']['height'] ?? null,
            'logo' => $validated['header']['logo'] ?? null,
            'name' => $validated['header']['name'] ?? null,
            'det' => $validated['header']['det'] ?? null,
            'line' => array_key_exists('line', $validated['header'] ?? [])
                ? filter_var($validated['header']['line'], FILTER_VALIDATE_BOOLEAN)
                : true,
            'on' => $validated['header']['on'] ?? [],
        ]);
        $position = ($validated['signature_position'] ?? VoucherSignatureConfig::POSITION_BOTTOM)
            === VoucherSignatureConfig::POSITION_AFTER
            ? VoucherSignatureConfig::POSITION_AFTER
            : VoucherSignatureConfig::POSITION_BOTTOM;
        $applyHeaderToAll = filter_var($validated['apply_header_to_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $before = $this->snapshotForType($typeCode);

        DB::transaction(function () use ($incoming, $typeCode, $companyId, $header, $position, $applyHeaderToAll) {
            VoucherSignatureConfig::withoutEvents(function () use ($incoming, $typeCode, $companyId, $header, $position, $applyHeaderToAll) {
                VoucherSignatureSlot::withoutEvents(function () use ($incoming, $typeCode, $companyId, $header, $position, $applyHeaderToAll) {
                    $config = VoucherSignatureConfig::query()
                        ->where('voucher_type_code', $typeCode)
                        ->first();

                    if (!$config) {
                        $config = VoucherSignatureConfig::create([
                            'company_id' => $companyId,
                            'voucher_type_code' => $typeCode,
                            'header_settings' => $header,
                            'signature_position' => $position,
                        ]);
                    } else {
                        $config->update([
                            'header_settings' => $header,
                            'signature_position' => $position,
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

                    if ($applyHeaderToAll) {
                        $codes = VoucherType::query()
                            ->where('is_active', true)
                            ->pluck('code')
                            ->map(fn ($c) => strtoupper((string) $c))
                            ->unique()
                            ->values();

                        foreach ($codes as $otherCode) {
                            $other = VoucherSignatureConfig::query()
                                ->where('voucher_type_code', $otherCode)
                                ->first();
                            if ($other) {
                                $other->update(['header_settings' => $header]);
                            } else {
                                VoucherSignatureConfig::create([
                                    'company_id' => $companyId,
                                    'voucher_type_code' => $otherCode,
                                    'header_settings' => $header,
                                    'signature_position' => VoucherSignatureConfig::POSITION_BOTTOM,
                                ]);
                            }
                        }
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
                'apply_header_to_all' => $applyHeaderToAll,
            ]
        );

        Flash::success('Print layout saved for ' . $voucherType->label . '.');

        $redirectTo = ($validated['redirect_to'] ?? 'list') === 'configure' ? 'configure' : 'list';
        if ($redirectTo === 'configure') {
            return redirect()->route('settings-panel.voucher-signature-assignment.configure', [
                'company_slug' => $company_slug,
                'code' => $typeCode,
            ]);
        }

        return redirect()->route('settings-panel.voucher-signature-assignment.index', [
            'company_slug' => $company_slug,
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
     * @return array<string, mixed>
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

        return [
            'header_settings' => $config->resolvedHeaderSettings(),
            'signature_position' => $config->resolvedSignaturePosition(),
            'slots' => $config->slots->map(fn (VoucherSignatureSlot $slot) => [
                'role_label' => $slot->role_label,
                'assignee_type' => $slot->assignee_type,
                'department_id' => $slot->department_id,
                'user_id' => $slot->user_id,
                'employee_id' => $slot->employee_id,
                'designation_override' => $slot->designation_override,
                'sort_order' => $slot->sort_order,
                'is_active' => $slot->is_active,
            ])->values()->all(),
        ];
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
