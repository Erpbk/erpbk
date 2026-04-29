<?php

namespace App\Http\Controllers;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeDocumentType;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BikeSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function bikeSettingsIndexRedirect(?int $activeCategoryId = null)
    {
        $companySlug = request()->route('company_slug') ?? session('company_slug');
        $url = route('settings-panel.module-settings.index', [
            'company_slug' => $companySlug,
            'module' => 'bike_list',
        ]);

        if ($activeCategoryId !== null) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'active_category_id=' . (int) $activeCategoryId;
        }

        return redirect()->to($url);
    }

    protected function bikeCategoryCompanyScoped(): bool
    {
        return Schema::hasColumn('bike_categories', 'company_id');
    }

    protected function bikeCategoryCompanyId(): ?int
    {
        $user = auth()->user();
        return $user && $user->company_id ? (int) $user->company_id : null;
    }

    protected function bikeCategoryQuery()
    {
        $query = BikeCategory::query();

        if ($this->bikeCategoryCompanyScoped()) {
            $companyId = $this->bikeCategoryCompanyId();
            if ($companyId !== null) {
                $query->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }
        }

        return $query;
    }

    public function index()
    {
        $categories = $this->bikeCategoryQuery()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $fixedAssignments = BikeFieldCategoryAssignment::with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $fixedAssignmentsByCategory = $fixedAssignments->groupBy('category_id');

        $customFields = BikeCustomField::with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $customFieldsByCategory = $customFields->groupBy('category_id');

        $unassignedCustomFields = BikeCustomField::whereNull('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $dataTypes = BikeCustomField::dataTypes();
        $moduleLabel = Settings::getMenuLabel('bike_settings');
        $documentTypes = BikeDocumentType::orderedForAdmin()->get();

        return view('settings.bike_settings.index', compact(
            'categories',
            'fixedAssignments',
            'fixedAssignmentsByCategory',
            'customFields',
            'customFieldsByCategory',
            'unassignedCustomFields',
            'dataTypes',
            'moduleLabel',
            'documentTypes',
        ));
    }

    public function storeModuleLabel(Request $request)
    {
        $request->validate(['module_label' => 'required|string|max:100']);
        $value = trim((string) $request->input('module_label'));

        Settings::updateOrCreate(
            ['name' => 'menu_label_bike_settings'],
            ['value' => $value]
        );

        Settings::clearMenuLabelsCache();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Module name updated.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $validated['display_order'] = (int) $this->bikeCategoryQuery()->max('display_order') + 1;
        $validated['is_system'] = false;
        $validated['slug'] = null;

        if ($this->bikeCategoryCompanyScoped()) {
            $validated['company_id'] = $this->bikeCategoryCompanyId();
        }

        BikeCategory::create($validated);

        return $this->bikeSettingsIndexRedirect()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = $this->bikeCategoryQuery()->where('id', $id)->firstOrFail();
        if ((bool) $category->is_system) {
            return redirect()->back()->with('error', 'System categories cannot be edited.');
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $category->label = $validated['label'];
        $category->save();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Category updated.');
    }

    public function destroyCategory(int $id)
    {
        $category = $this->bikeCategoryQuery()->where('id', $id)->firstOrFail();
        if ((bool) $category->is_system) {
            return redirect()->back()->with('error', 'System categories cannot be deleted.');
        }

        if (BikeFieldCategoryAssignment::where('category_id', $category->id)->exists()) {
            return redirect()->back()->with('error', 'Category has fixed field assignments. Remove/reassign them first.');
        }

        if (BikeCustomField::where('category_id', $category->id)->exists()) {
            return redirect()->back()->with('error', 'Category has custom fields. Remove/reassign them first.');
        }

        $category->delete();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Category deleted.');
    }

    public function updateFieldAssignment(Request $request)
    {
        $validated = $request->validate([
            'field_key' => ['required', 'string', 'max:80', 'exists:bike_field_category_assignments,field_key'],
            'category_id' => ['required', 'integer', 'exists:bike_categories,id'],
            'display_label' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'input_type' => ['nullable', 'string', 'max:50'],
            'input_config_options' => ['nullable', 'string'],
        ]);

        $assignment = BikeFieldCategoryAssignment::where('field_key', $validated['field_key'])->firstOrFail();

        $assignment->category_id = (int) $validated['category_id'];

        $displayLabel = $validated['display_label'] !== null ? trim((string) $validated['display_label']) : null;
        $assignment->display_label = ($displayLabel === '' ? null : $displayLabel);

        $assignment->is_visible = filter_var((string) ($validated['is_visible'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $assignment->is_required = filter_var((string) ($validated['is_required'] ?? false), FILTER_VALIDATE_BOOLEAN);

        $inputType = $validated['input_type'] !== null ? trim((string) $validated['input_type']) : null;
        $assignment->input_type = ($inputType === '' ? null : $inputType);

        if (!empty($validated['input_config_options'])) {
            // Used by fixed-field dropdown renderer (stored as newline-separated list).
            $assignment->input_config = ['options' => $validated['input_config_options']];
        } else {
            $assignment->input_config = null;
        }

        $assignment->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Field assignment updated.',
                'field_key' => $assignment->field_key,
                'is_visible' => (bool) $assignment->is_visible,
                'is_required' => (bool) $assignment->is_required,
            ]);
        }

        return $this->bikeSettingsIndexRedirect((int) $assignment->category_id)->with('success', 'Field assignment updated.');
    }

    public function storeField(Request $request)
    {
        $allowedTypes = array_keys(BikeCustomField::dataTypes());

        if ($request->input('category_id') === '') {
            $request->merge(['category_id' => null]);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => ['required', 'string', Rule::in($allowedTypes)],
            'is_mandatory' => ['nullable', 'boolean'],
            'default_value' => ['nullable', 'string', 'max:500'],
            'input_format' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:bike_categories,id'],
            'config_options' => ['nullable', 'string'],
        ]);

        $categoryId = $validated['category_id'] ?? null;

        $displayOrder = $categoryId
            ? ((int) BikeCustomField::where('category_id', $categoryId)->max('display_order')) + 1
            : ((int) BikeCustomField::whereNull('category_id')->max('display_order')) + 1;

        $config = null;
        if (!empty($validated['config_options']) && $validated['data_type'] === 'dropdown') {
            $config = ['options' => $validated['config_options']];
        }

        BikeCustomField::create([
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'data_privacy' => null,
            'prevent_duplicate_values' => false,
            'default_value' => $validated['default_value'] ?? null,
            'input_format' => $validated['input_format'] ?? null,
            'data_type' => $validated['data_type'],
            'is_mandatory' => filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'config' => $config,
            'category_id' => $categoryId,
            'display_order' => $displayOrder,
        ]);

        return $this->bikeSettingsIndexRedirect($categoryId !== null ? (int) $categoryId : 0)
            ->with('success', 'Custom field added.');
    }

    public function updateField(Request $request, int $id)
    {
        $field = BikeCustomField::where('id', $id)->firstOrFail();

        $allowedTypes = array_keys(BikeCustomField::dataTypes());

        if ($request->input('category_id') === '') {
            $request->merge(['category_id' => null]);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => ['required', 'string', Rule::in($allowedTypes)],
            'is_mandatory' => ['nullable', 'boolean'],
            'default_value' => ['nullable', 'string', 'max:500'],
            'input_format' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:bike_categories,id'],
            'config_options' => ['nullable', 'string'],
        ]);

        $field->label = $validated['label'];
        $field->help_text = $validated['help_text'] ?? null;
        $field->data_type = $validated['data_type'];
        $field->is_mandatory = filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $field->default_value = $validated['default_value'] ?? null;
        $field->input_format = $validated['input_format'] ?? null;
        $field->category_id = $validated['category_id'] ?? null;

        $config = null;
        if (!empty($validated['config_options']) && $validated['data_type'] === 'dropdown') {
            $config = ['options' => $validated['config_options']];
        }
        $field->config = $config;
        $field->save();

        return $this->bikeSettingsIndexRedirect($field->category_id !== null ? (int) $field->category_id : 0)
            ->with('success', 'Custom field updated.');
    }

    public function destroyField(int $id)
    {
        $field = BikeCustomField::where('id', $id)->firstOrFail();
        $activeCategoryId = $field->category_id !== null ? (int) $field->category_id : 0;
        $field->delete();

        return $this->bikeSettingsIndexRedirect($activeCategoryId)->with('success', 'Custom field deleted.');
    }

    /**
     * Assign a bike custom field to a category (button-only in UI).
     * Supports moving to "Unassigned" by sending empty `category_id`.
     */
    public function assignCustomFieldCategory(Request $request, int $id)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:bike_categories,id'],
        ]);

        $field = BikeCustomField::where('id', $id)->firstOrFail();
        $field->category_id = (int) $validated['category_id'];
        $field->save();

        $activeCategoryId = (int) $field->category_id;
        return $this->bikeSettingsIndexRedirect($activeCategoryId)->with('success', 'Custom field moved.');
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:80|unique:bike_document_types,key',
            'label' => 'nullable|string|max:255',
            'type' => ['required', 'string', Rule::in(['single', 'dual'])],
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['display_order'] = $validated['display_order'] ?? ((int) BikeDocumentType::max('display_order')) + 1;

        BikeDocumentType::create([
            'key' => trim((string) $validated['key']),
            'label' => $validated['label'] ?? null,
            'type' => $validated['type'],
            'front_label' => $validated['front_label'] ?? null,
            'back_label' => $validated['back_label'] ?? null,
            'display_order' => (int) $validated['display_order'],
            'is_active' => true,
        ]);

        return $this->bikeSettingsIndexRedirect()->with('success', 'Document type added.');
    }

    public function updateDocumentType(Request $request, int $id)
    {
        $field = BikeDocumentType::where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'key' => 'required|string|max:80|unique:bike_document_types,key,' . $id,
            'label' => 'nullable|string|max:255',
            'type' => ['required', 'string', Rule::in(['single', 'dual'])],
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $field->key = trim((string) $validated['key']);
        $field->label = $validated['label'] ?? null;
        $field->type = $validated['type'];
        $field->front_label = $validated['front_label'] ?? null;
        $field->back_label = $validated['back_label'] ?? null;
        $field->display_order = $validated['display_order'] ?? $field->display_order;
        $field->is_active = true;
        $field->save();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Document type updated.');
    }

    public function destroyDocumentType(int $id)
    {
        $field = BikeDocumentType::where('id', $id)->firstOrFail();
        $field->delete();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Document type deleted.');
    }
}

