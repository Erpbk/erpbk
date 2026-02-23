<?php

namespace App\Http\Controllers;

use App\Models\AccountCustomField;
use Illuminate\Http\Request;

class AccountFieldSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Site Settings > Accounts: list fixed (DB) fields + custom fields; allow add/edit/delete/reorder for custom only.
     */
    public function index()
    {
        $fixedFields = AccountCustomField::fixedAccountFields();
        $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();
        $dataTypes = AccountCustomField::dataTypes();

        return view('settings.account_fields.index', compact('fixedFields', 'customFields', 'dataTypes'));
    }

    /**
     * Store a new custom field.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(AccountCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable', // sent as JSON string from form; normalized to array below
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
        $validated['display_order'] = (int) AccountCustomField::max('display_order') + 1;

        AccountCustomField::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field added successfully.']);
        }
        return redirect()->route('settings-panel.account-fields.index')->with('success', 'Custom field added successfully.');
    }

    /**
     * Update a custom field.
     */
    public function update(Request $request, $id)
    {
        $field = AccountCustomField::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(AccountCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable', // sent as JSON string from form; normalized to array below
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
        return redirect()->route('settings-panel.account-fields.index')->with('success', 'Custom field updated successfully.');
    }

    /**
     * Delete a custom field (only user-created custom fields are deletable).
     */
    public function destroy($id)
    {
        $field = AccountCustomField::findOrFail($id);
        $field->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field deleted.']);
        }
        return redirect()->route('settings-panel.account-fields.index')->with('success', 'Custom field deleted.');
    }

    /**
     * Reorder custom fields (display_order).
     */
    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:account_custom_fields,id']);

        foreach ($request->input('order') as $position => $id) {
            AccountCustomField::where('id', $id)->update(['display_order' => $position]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Return config schema for a data type (for dynamic form).
     */
    public function configSchema($dataType)
    {
        $types = AccountCustomField::dataTypes();
        if (!isset($types[$dataType])) {
            return response()->json(['config' => []], 404);
        }
        return response()->json(['config' => $types[$dataType]['config'] ?? []]);
    }

    /**
     * Return only the custom fields table body HTML (for AJAX refresh after add).
     */
    public function tableBody()
    {
        $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();
        $dataTypes = AccountCustomField::dataTypes();
        return view('settings.account_fields._custom_fields_tbody', compact('customFields', 'dataTypes'));
    }
}
