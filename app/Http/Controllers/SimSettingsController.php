<?php

namespace App\Http\Controllers;

use App\Models\ModuleCustomField;
use App\Models\SimAssignFieldAssignment;
use App\Support\SimAssignFields;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SimSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function simSettingsRedirect(?string $tab = null)
    {
        $companySlug = request()->route('company_slug') ?? session('company_slug');
        $url = route('settings-panel.module-settings.index', [
            'company_slug' => $companySlug,
            'module' => 'sims',
        ]);

        if ($tab !== null && $tab !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'tab=' . urlencode($tab);
        }

        return redirect()->to($url);
    }

    public function updateAssignFieldAssignment(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:sim_assign_field_assignments,id'],
            'display_label' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'show_on_active' => ['nullable', 'boolean'],
            'show_on_change' => ['nullable', 'boolean'],
        ]);

        $assignment = SimAssignFieldAssignment::findOrFail($validated['id']);

        if (array_key_exists('display_label', $validated)) {
            $label = trim((string) ($validated['display_label'] ?? ''));
            $assignment->display_label = $label === '' ? null : $label;
        }
        foreach (['is_visible', 'is_required', 'show_on_active', 'show_on_change'] as $flag) {
            if (array_key_exists($flag, $validated)) {
                $assignment->{$flag} = filter_var((string) $validated[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $assignment->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Assign field updated.']);
        }

        return $this->simSettingsRedirect('assign-fields')->with('success', 'Assign field updated.');
    }

    public function reorderAssignFieldAssignments(Request $request)
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:sim_assign_field_assignments,id'],
        ]);

        foreach ($validated['ordered_ids'] as $pos => $id) {
            SimAssignFieldAssignment::where('id', $id)->update(['display_order' => (int) $pos]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Assign fields reordered.']);
        }

        return $this->simSettingsRedirect('assign-fields')->with('success', 'Assign fields reordered.');
    }

    public function storeAssignField(Request $request)
    {
        $allowedTypes = array_keys(ModuleCustomField::dataTypes());
        $companyId = optional(auth()->user())->company_id;

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => ['required', 'string', Rule::in($allowedTypes)],
            'is_mandatory' => ['nullable', 'boolean'],
            'default_value' => ['nullable', 'string', 'max:500'],
            'input_format' => ['nullable', 'string', 'max:100'],
            'config_options' => ['nullable', 'string'],
            'show_on_active' => ['nullable', 'boolean'],
            'show_on_change' => ['nullable', 'boolean'],
        ]);

        $config = null;
        if (!empty($validated['config_options']) && $validated['data_type'] === 'dropdown') {
            $config = ['options' => $validated['config_options']];
        }

        $displayOrder = ((int) SimAssignFieldAssignment::max('display_order')) + 1;

        $customField = ModuleCustomField::create([
            'module_key' => 'sims',
            'company_id' => $companyId,
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'data_privacy' => null,
            'prevent_duplicate_values' => false,
            'default_value' => $validated['default_value'] ?? null,
            'input_format' => $validated['input_format'] ?? null,
            'data_type' => $validated['data_type'],
            'is_mandatory' => filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'config' => $config,
            'category_id' => null,
            'display_order' => $displayOrder,
        ]);

        SimAssignFieldAssignment::create([
            'field_key' => null,
            'custom_field_id' => $customField->id,
            'kind' => 'custom',
            'display_label' => $customField->label,
            'input_type' => $validated['data_type'],
            'display_order' => $displayOrder,
            'is_visible' => true,
            'is_required' => $customField->is_mandatory,
            'show_on_active' => filter_var((string) ($validated['show_on_active'] ?? true), FILTER_VALIDATE_BOOLEAN),
            'show_on_change' => filter_var((string) ($validated['show_on_change'] ?? false), FILTER_VALIDATE_BOOLEAN),
        ]);

        return $this->simSettingsRedirect('assign-fields')->with('success', 'Assign custom field added.');
    }

    public function updateAssignField(Request $request, string $company_slug, int $id)
    {
        $assignment = SimAssignFieldAssignment::with('customField')->findOrFail($id);
        $isCustom = $assignment->kind === 'custom' && $assignment->custom_field_id;

        if ($isCustom) {
            $allowedTypes = array_keys(ModuleCustomField::dataTypes());
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'help_text' => 'nullable|string|max:1000',
                'data_type' => ['required', 'string', Rule::in($allowedTypes)],
                'is_mandatory' => ['nullable', 'boolean'],
                'default_value' => 'nullable|string|max:500',
                'input_format' => 'nullable|string|max:100',
                'config_options' => 'nullable|string',
                'is_visible' => ['nullable', 'boolean'],
                'is_required' => ['nullable', 'boolean'],
                'show_on_active' => ['nullable', 'boolean'],
                'show_on_change' => ['nullable', 'boolean'],
            ]);

            $field = ModuleCustomField::where('id', $assignment->custom_field_id)
                ->where('module_key', 'sims')
                ->firstOrFail();

            $config = $field->config;
            if ($validated['data_type'] === 'dropdown' && !empty($validated['config_options'])) {
                $config = ['options' => $validated['config_options']];
            }

            $field->label = $validated['label'];
            $field->help_text = $validated['help_text'] ?? null;
            $field->data_type = $validated['data_type'];
            $field->is_mandatory = filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN);
            $field->default_value = $validated['default_value'] ?? null;
            $field->input_format = $validated['input_format'] ?? null;
            $field->config = $config;
            $field->save();

            $assignment->display_label = $field->label;
            $assignment->input_type = $validated['data_type'];
            $assignment->is_visible = filter_var((string) ($validated['is_visible'] ?? true), FILTER_VALIDATE_BOOLEAN);
            $assignment->is_required = filter_var((string) ($validated['is_required'] ?? $field->is_mandatory), FILTER_VALIDATE_BOOLEAN);
            $assignment->show_on_active = filter_var((string) ($validated['show_on_active'] ?? true), FILTER_VALIDATE_BOOLEAN);
            $assignment->show_on_change = filter_var((string) ($validated['show_on_change'] ?? false), FILTER_VALIDATE_BOOLEAN);
            $assignment->save();
        } else {
            $validated = $request->validate([
                'display_label' => 'nullable|string|max:255',
                'is_visible' => ['nullable', 'boolean'],
                'is_required' => ['nullable', 'boolean'],
                'show_on_active' => ['nullable', 'boolean'],
                'show_on_change' => ['nullable', 'boolean'],
                'input_type' => ['nullable', 'string', 'max:50'],
                'input_config_options' => ['nullable', 'string'],
            ]);

            $label = trim((string) ($validated['display_label'] ?? ''));
            $assignment->display_label = $label === '' ? null : $label;
            $assignment->is_visible = filter_var((string) ($validated['is_visible'] ?? false), FILTER_VALIDATE_BOOLEAN);
            $assignment->is_required = filter_var((string) ($validated['is_required'] ?? false), FILTER_VALIDATE_BOOLEAN);
            $assignment->show_on_active = filter_var((string) ($validated['show_on_active'] ?? false), FILTER_VALIDATE_BOOLEAN);
            $assignment->show_on_change = filter_var((string) ($validated['show_on_change'] ?? false), FILTER_VALIDATE_BOOLEAN);

            $inputType = $validated['input_type'] !== null ? trim((string) $validated['input_type']) : null;
            $assignment->input_type = ($inputType === '' ? null : $inputType);

            $catalog = collect(SimAssignFields::defaultAssignFieldCatalog())
                ->firstWhere('field_key', $assignment->field_key);
            $catalogConfig = is_array($catalog['input_config'] ?? null) ? $catalog['input_config'] : [];
            $mergedConfig = array_merge(
                $catalogConfig,
                is_array($assignment->input_config) ? $assignment->input_config : []
            );

            if (!empty($validated['input_config_options'])) {
                $mergedConfig['options'] = $validated['input_config_options'];
            } elseif (array_key_exists('input_config_options', $validated)) {
                unset($mergedConfig['options']);
            }

            $assignment->input_config = $mergedConfig === [] ? null : $mergedConfig;
            $assignment->save();
        }

        return $this->simSettingsRedirect('assign-fields')->with('success', 'Assign field updated.');
    }

    public function destroyAssignField(string $company_slug, int $id)
    {
        $assignment = SimAssignFieldAssignment::findOrFail($id);

        if ($assignment->kind !== 'custom' || !$assignment->custom_field_id) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Built-in assign fields cannot be deleted.',
                ], 422);
            }

            return $this->simSettingsRedirect('assign-fields')->with('error', 'Built-in assign fields cannot be deleted.');
        }

        $customFieldId = (int) $assignment->custom_field_id;
        $assignment->delete();
        ModuleCustomField::where('id', $customFieldId)->where('module_key', 'sims')->delete();

        return $this->simSettingsRedirect('assign-fields')->with('success', 'Assign custom field removed.');
    }
}
