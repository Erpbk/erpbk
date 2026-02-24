<?php

namespace App\Http\Controllers;

use App\Models\VoucherType;
use App\Models\VoucherCustomField;
use Illuminate\Http\Request;

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
        $voucherTypes = VoucherType::orderBy('display_order')->orderBy('id')->get();
        $customFields = VoucherCustomField::orderBy('display_order')->orderBy('id')->get();
        $dataTypes = VoucherCustomField::dataTypes();

        return view('settings.voucher_settings.index', compact('voucherTypes', 'customFields', 'dataTypes'));
    }

    // ---------- Voucher Types ----------

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:voucher_types,code',
            'label' => 'required|string|max:255',
        ]);
        $validated['display_order'] = (int) VoucherType::max('display_order') + 1;
        $validated['is_active'] = true;
        VoucherType::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Voucher type added successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Voucher type added successfully.');
    }

    public function updateType(Request $request, $id)
    {
        $type = VoucherType::findOrFail($id);
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:voucher_types,code,' . $id,
            'label' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);
        $type->code = $validated['code'];
        $type->label = $validated['label'];
        $type->is_active = $request->boolean('is_active');
        $type->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Voucher type updated successfully.']);
        }
        return redirect()->route('settings-panel.voucher-settings.index')->with('success', 'Voucher type updated successfully.');
    }

    public function destroyType($id)
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
        $voucherTypes = VoucherType::orderBy('display_order')->orderBy('id')->get();
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

    public function updateField(Request $request, $id)
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

    public function destroyField($id)
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
