<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Models\VoucherType;
use App\Models\VoucherCustomField;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Voucher Settings: voucher types + voucher custom fields (same structure as Account Settings).
     */
    public function index()
    {
        $voucherTypes = VoucherType::withoutGlobalScope('company')
            ->with(['moduleAssignmentsAllCompanies' => function ($query) {
                $query->withoutGlobalScope('company');
            }])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $voucherTypes->each(function (VoucherType $type): void {
            $type->setRelation('moduleAssignments', $type->getRelation('moduleAssignmentsAllCompanies'));
        });
        $customFields = VoucherCustomField::orderBy('display_order')->orderBy('id')->get();
        $dataTypes = VoucherCustomField::dataTypes();
        $moduleLabel = Settings::getMenuLabel('voucher_settings');
        $voucherModules = VoucherType::availableModules();

        return view('settings.voucher_settings.index', compact('voucherTypes', 'customFields', 'dataTypes', 'moduleLabel', 'voucherModules'));
    }

    /**
     * Save the display name for this module (settings panel + main app menu use key 'vouchers').
     */
    public function storeModuleLabel(Request $request)
    {
        $request->validate(['module_label' => 'required|string|max:100']);
        $value = trim($request->input('module_label'));
        Settings::updateOrCreate(['name' => 'menu_label_voucher_settings'], ['value' => $value]);
        Settings::updateOrCreate(['name' => 'menu_label_vouchers'], ['value' => $value]);
        Settings::clearMenuLabelsCache();
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Module name updated.');
    }

    // ---------- Voucher Types ----------

    public function storeType(Request $request)
    {
        $allowedModules = array_keys(VoucherType::availableModules());
        $companyId = CompanyContext::id();
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('voucher_types', 'code')->where(function ($query) use ($companyId) {
                    return $companyId === null
                        ? $query->whereNull('company_id')
                        : $query->where('company_id', $companyId);
                }),
            ],
            'label' => 'required|string|max:255',
            'module_assignments' => 'required|array|min:1',
            'module_assignments.*' => 'array',
            'module_assignments.*.assigned' => 'nullable|boolean',
            'allow_edit_in_voucher_module' => 'nullable|boolean',
            'allow_delete_in_voucher_module' => 'nullable|boolean',
        ]);
        $assignments = $request->input('module_assignments', []);
        $moduleKeys = [];
        foreach ($allowedModules as $key) {
            $a = $assignments[$key] ?? [];
            if (!empty($a['assigned'])) {
                $moduleKeys[] = $key;
            }
        }
        if (empty($moduleKeys)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Select at least one module.'], 422);
            }
            return redirect()->back()->withInput()->withErrors(['module_assignments' => 'Select at least one module.']);
        }
        $canEditByModule = array_fill_keys($moduleKeys, true);
        $canDeleteByModule = array_fill_keys($moduleKeys, true);
        if (in_array('vouchers', $moduleKeys, true)) {
            $canEditByModule['vouchers'] = $request->boolean('allow_edit_in_voucher_module');
            $canDeleteByModule['vouchers'] = $request->boolean('allow_delete_in_voucher_module');
        }
        $voucherType = VoucherType::create([
            'code' => $validated['code'],
            'label' => $validated['label'],
            'display_order' => (int) VoucherType::max('display_order') + 1,
            'is_active' => true,
        ]);
        $voucherType->syncModules($moduleKeys, $canEditByModule, $canDeleteByModule);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Voucher type added successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Voucher type added successfully.');
    }

    public function updateType(Request $request, string $company_slug, $id)
    {
        $type = VoucherType::findOrFail($id);
        $allowedModules = array_keys(VoucherType::availableModules());
        $companyId = CompanyContext::id();
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('voucher_types', 'code')
                    ->ignore($id)
                    ->where(function ($query) use ($companyId) {
                        return $companyId === null
                            ? $query->whereNull('company_id')
                            : $query->where('company_id', $companyId);
                    }),
            ],
            'label' => 'required|string|max:255',
            'is_active' => 'boolean',
            'module_assignments' => 'required|array|min:1',
            'module_assignments.*' => 'array',
            'module_assignments.*.assigned' => 'nullable|boolean',
            'allow_edit_in_voucher_module' => 'nullable|boolean',
            'allow_delete_in_voucher_module' => 'nullable|boolean',
        ]);
        $assignments = $request->input('module_assignments', []);
        $moduleKeys = [];
        foreach ($allowedModules as $key) {
            $a = $assignments[$key] ?? [];
            if (!empty($a['assigned'])) {
                $moduleKeys[] = $key;
            }
        }
        if (empty($moduleKeys)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Select at least one module.'], 422);
            }
            return redirect()->back()->withInput()->withErrors(['module_assignments' => 'Select at least one module.']);
        }
        $canEditByModule = array_fill_keys($moduleKeys, true);
        $canDeleteByModule = array_fill_keys($moduleKeys, true);
        if (in_array('vouchers', $moduleKeys, true)) {
            $canEditByModule['vouchers'] = $request->boolean('allow_edit_in_voucher_module');
            $canDeleteByModule['vouchers'] = $request->boolean('allow_delete_in_voucher_module');
        }
        $type->code = $validated['code'];
        $type->label = $validated['label'];
        $type->is_active = $request->boolean('is_active');
        $type->save();
        $type->syncModules($moduleKeys, $canEditByModule, $canDeleteByModule);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Voucher type updated successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Voucher type updated successfully.');
    }

    public function destroyType(string $company_slug, $id)
    {
        $type = VoucherType::findOrFail($id);
        $type->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Voucher type deleted.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Voucher type deleted.');
    }

    public function reorderTypes(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:voucher_types,id']);
        foreach ($request->input('order') as $position => $id) {
            VoucherType::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function typesTableBody()
    {
        $voucherTypes = VoucherType::withoutGlobalScope('company')
            ->with(['moduleAssignmentsAllCompanies' => function ($query) {
                $query->withoutGlobalScope('company');
            }])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $voucherTypes->each(function (VoucherType $type): void {
            $type->setRelation('moduleAssignments', $type->getRelation('moduleAssignmentsAllCompanies'));
        });
        return view('settings.voucher_settings._voucher_types_tbody', compact('voucherTypes'));
    }

    // ---------- Voucher Custom Fields ----------

    public function storeField(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(VoucherCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['prevent_duplicate_values'] = $request->boolean('prevent_duplicate_values');
        $validated['help_text'] = $request->input('help_text');
        $validated['default_value'] = $request->input('default_value');
        $validated['input_format'] = $request->input('input_format');
        $validated['data_privacy'] = [
            'pii' => $request->boolean('data_privacy_pii'),
            'ephi' => $request->boolean('data_privacy_ephi'),
        ];
        $config = $request->input('config');
        $validated['config'] = is_string($config) ? (json_decode($config, true) ?? []) : (is_array($config) ? $config : []);
        $validated['display_order'] = (int) VoucherCustomField::max('display_order') + 1;

        VoucherCustomField::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field added successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Custom field added successfully.');
    }

    public function updateField(Request $request, string $company_slug, $id)
    {
        $field = VoucherCustomField::findOrFail($id);
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(VoucherCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
        ]);

        $field->label = $validated['label'];
        $field->help_text = $request->input('help_text');
        $field->data_type = $validated['data_type'];
        $field->is_mandatory = $request->boolean('is_mandatory');
        $field->prevent_duplicate_values = $request->boolean('prevent_duplicate_values');
        $field->default_value = $request->input('default_value');
        $field->input_format = $request->input('input_format');
        $field->data_privacy = [
            'pii' => $request->boolean('data_privacy_pii'),
            'ephi' => $request->boolean('data_privacy_ephi'),
        ];
        $config = $request->input('config');
        $field->config = is_string($config) ? (json_decode($config, true) ?? []) : (is_array($config) ? $config : []);
        $field->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field updated successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Custom field updated successfully.');
    }

    public function destroyField(string $company_slug, $id)
    {
        $field = VoucherCustomField::findOrFail($id);
        $field->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field deleted.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Custom field deleted.');
    }

    public function reorderFields(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:voucher_custom_fields,id']);
        foreach ($request->input('order') as $position => $id) {
            VoucherCustomField::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function fieldConfigSchema($dataType)
    {
        $types = VoucherCustomField::dataTypes();
        if (!isset($types[$dataType])) {
            return response()->json(['config' => []], 404);
        }
        return response()->json(['config' => $types[$dataType]['config'] ?? []]);
    }

    public function fieldsTableBody()
    {
        $customFields = VoucherCustomField::orderBy('display_order')->orderBy('id')->get();
        $dataTypes = VoucherCustomField::dataTypes();
        return view('settings.voucher_settings._voucher_custom_fields_tbody', compact('customFields', 'dataTypes'));
    }
}
