<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesModuleDisplayLabel;
use App\Models\EmployeeCategory;
use App\Models\EmployeeCustomField;
use App\Models\EmployeeDocumentType;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\EmployeeTopCategory;
use App\Models\EmployeeTopOption;
use App\Models\Employee;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EmployeeSettingsController extends Controller
{
    use SavesModuleDisplayLabel;

    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function employeeCategoryCompanyScoped(): bool
    {
        return Schema::hasColumn('employee_categories', 'company_id');
    }

    protected function employeeCategoryCompanyId(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        return $user->company_id ? (int) $user->company_id : null;
    }

    protected function employeeCategoryQuery()
    {
        return EmployeeCategory::query();
    }

    protected function findScopedEmployeeCategory(int $id): EmployeeCategory
    {
        return $this->employeeCategoryQuery()->where('id', $id)->firstOrFail();
    }

    protected function scopedCategoryIds(): array
    {
        return $this->employeeCategoryQuery()->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    /**
     * Employee Settings: categories, fixed employee fields + employee custom fields, organized by category.
     */
    public function index()
    {
        $categories = $this->employeeCategoryQuery()->orderBy('display_order')->orderBy('id')->get();
        $fixedFieldsByCategory = EmployeeCustomField::fixedEmployeeFieldsByCategory();
        $customFields = EmployeeCustomField::with('category')->orderBy('display_order')->orderBy('id')->get();
        $customFieldsByCategory = $customFields->groupBy('category_id');
        $dataTypes = EmployeeCustomField::dataTypes();
        $moduleLabel = Settings::getMenuLabel('employee_settings');
        $fieldAssignments = $this->buildFieldAssignmentsList($categories);
        $fieldsByCategory = $this->buildFieldsByCategory($categories);
        $allFixedFieldsForStatic = $this->buildAllFixedFieldsForStatic($categories);
        $unassignedFixedFields = $this->buildUnassignedFixedFields();
        $unassignedCustomFields = EmployeeCustomField::whereNull('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $documentTypes = EmployeeDocumentType::orderedForAdmin()->get();
        $employeeTopCategories = EmployeeTopCategory::with('options')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $employeeTopSelectableColumns = $this->employeeTopSelectableColumns();
        $employeeStatusCategory = $this->employeeStatusTopCategory();
        $employeeStatusOptions = EmployeeTopOption::where('category_id', $employeeStatusCategory->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('settings.employee_settings.index', compact(
            'categories',
            'fixedFieldsByCategory',
            'customFields',
            'customFieldsByCategory',
            'dataTypes',
            'moduleLabel',
            'fieldAssignments',
            'fieldsByCategory',
            'allFixedFieldsForStatic',
            'unassignedFixedFields',
            'unassignedCustomFields',
            'documentTypes',
            'employeeTopCategories',
            'employeeTopSelectableColumns',
            'employeeStatusCategory',
            'employeeStatusOptions'
        ));
    }

    protected function employeeTopSelectableColumns(): array
    {
        $employeeColumns = Schema::getColumnListing('employees');
        $options = [];

        foreach ($employeeColumns as $fieldKey) {
            if (in_array($fieldKey, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }
            $options[$fieldKey] = EmployeeCustomField::humanizeFieldKey($fieldKey);
        }

        asort($options);
        return $options;
    }

    protected function employeeStatusTopCategory(): EmployeeTopCategory
    {
        $category = EmployeeTopCategory::where('employee_column', 'status')->first();
        if ($category) {
            if (trim((string) $category->name) === '') {
                $category->name = 'Employee Status';
                $category->save();
            }
            return $category;
        }

        return EmployeeTopCategory::create([
            'name' => 'Employee Status',
            'employee_column' => 'status',
            'display_order' => ((int) EmployeeTopCategory::max('display_order')) + 1,
            'is_active' => true,
            'show_in_top_bar' => true,
            'show_in_view_cards' => true,
        ]);
    }

    /**
     * Build fields grouped by category for Employee Fields sub-tabs (with display_order).
     */
    protected function buildFieldsByCategory($categories)
    {
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));
        $assignments = EmployeeFieldCategoryAssignment::with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $grouped = $assignments->groupBy('category_id');

        $result = [];
        foreach ($categories as $cat) {
            $fixedSpecs = EmployeeCustomField::fixedFieldInputSpecs();
            $items = $grouped->get($cat->id, collect())->map(function ($a) use ($fixedSpecs, $employeeColumns) {
                if (!isset($employeeColumns[$a->field_key])) {
                    return null;
                }
                $rawVisible = $a->getRawOriginal('is_visible');
                $isVisible = $rawVisible === null ? true : (bool) (int) $rawVisible;
                $rawRequired = $a->getRawOriginal('is_required');
                $isRequired = $rawRequired === null ? false : (bool) (int) $rawRequired;
                $defaultType = $fixedSpecs[$a->field_key]['type'] ?? 'text';
                if ($defaultType === 'select') {
                    $defaultType = 'dropdown';
                }
                return (object) [
                    'field_key' => $a->field_key,
                    'label' => $a->display_label !== null && trim((string) $a->display_label) !== ''
                        ? trim($a->display_label)
                        : EmployeeCustomField::humanizeFieldKey($a->field_key),
                    'display_order' => $a->display_order,
                    'is_visible' => $isVisible,
                    'is_required' => $isRequired,
                    'input_type' => $a->input_type ?: $defaultType,
                    'input_config' => is_array($a->input_config) ? $a->input_config : [],
                ];
            })->filter()->values()->all();

            usort($items, function ($a, $b) {
                $ao = (int) ($a->display_order ?? 9999);
                $bo = (int) ($b->display_order ?? 9999);
                if ($ao === $bo) {
                    return strcmp((string) ($a->field_key ?? ''), (string) ($b->field_key ?? ''));
                }
                return $ao <=> $bo;
            });

            $result[] = (object) [
                'category' => $cat,
                'fields' => $items,
            ];
        }
        return $result;
    }

    protected function buildUnassignedFixedFields()
    {
        $keys = $this->validFixedAssignableFieldKeys();
        $assignedFieldKeys = EmployeeFieldCategoryAssignment::pluck('field_key')->all();
        $assignedSet = array_flip($assignedFieldKeys);
        $specs = EmployeeCustomField::fixedFieldInputSpecs();
        $rows = [];
        foreach ($keys as $fieldKey) {
            if (isset($assignedSet[$fieldKey])) {
                continue;
            }
            $defaultType = $specs[$fieldKey]['type'] ?? 'text';
            if ($defaultType === 'select') {
                $defaultType = 'dropdown';
            }
            $rows[] = (object) [
                'field_key' => $fieldKey,
                'label' => EmployeeCustomField::humanizeFieldKey($fieldKey),
                'is_visible' => true,
                'is_required' => false,
                'input_type' => $defaultType,
                'input_config' => [],
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp((string) $a->field_key, (string) $b->field_key);
        });

        return $rows;
    }

    protected function buildAllFixedFieldsForStatic($categories)
    {
        $keys = $this->validFixedAssignableFieldKeys();
        $assignments = EmployeeFieldCategoryAssignment::all()->keyBy('field_key');
        $specs = EmployeeCustomField::fixedFieldInputSpecs();
        $categoriesById = $categories->keyBy('id');
        $rows = [];

        foreach ($keys as $fieldKey) {
            $assignment = $assignments->get($fieldKey);
            $categoryId = $assignment ? (int) $assignment->category_id : null;
            $categoryLabel = $categoryId && isset($categoriesById[$categoryId]) ? $categoriesById[$categoryId]->label : null;
            $rawVisible = $assignment ? $assignment->getRawOriginal('is_visible') : null;
            $rawRequired = $assignment ? $assignment->getRawOriginal('is_required') : null;
            $defaultType = $specs[$fieldKey]['type'] ?? 'text';
            if ($defaultType === 'select') {
                $defaultType = 'dropdown';
            }

            $rows[] = (object) [
                'field_key' => $fieldKey,
                'label' => $assignment && $assignment->display_label !== null && trim((string) $assignment->display_label) !== ''
                    ? trim((string) $assignment->display_label)
                    : EmployeeCustomField::humanizeFieldKey($fieldKey),
                'category_id' => $categoryId,
                'category_label' => $categoryLabel,
                'is_assigned' => (bool) $assignment,
                'is_visible' => $rawVisible === null ? true : (bool) (int) $rawVisible,
                'is_required' => $rawRequired === null ? false : (bool) (int) $rawRequired,
                'input_type' => $assignment && !empty($assignment->input_type) ? $assignment->input_type : $defaultType,
                'input_config' => $assignment && is_array($assignment->input_config) ? $assignment->input_config : [],
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp((string) $a->field_key, (string) $b->field_key);
        });

        return $rows;
    }

    /**
     * Build list of all fixed employee fields with their current category assignment (for Employee Fields tab).
     */
    protected function buildFieldAssignmentsList($categories)
    {
        $keys = $this->validFixedAssignableFieldKeys();
        $assignments = EmployeeFieldCategoryAssignment::all()->keyBy('field_key');
        $slugToId = EmployeeCategory::whereNotNull('slug')->pluck('id', 'slug')->all();
        $map = EmployeeCustomField::fixedFieldsSlugMap();
        $list = [];
        foreach ($keys as $fieldKey) {
            $a = $assignments->get($fieldKey);
            $defaultSlug = 'other';
            foreach ($map as $slug => $slugKeys) {
                if (in_array($fieldKey, $slugKeys, true)) {
                    $defaultSlug = $slug;
                    break;
                }
            }
            $defaultCategoryId = $slugToId[$defaultSlug] ?? $categories->first()?->id;
            $list[] = (object) [
                'field_key' => $fieldKey,
                'label' => EmployeeCustomField::humanizeFieldKey($fieldKey),
                'category_id' => $a ? $a->category_id : $defaultCategoryId,
            ];
        }
        return $list;
    }

    protected function validFixedAssignableFieldKeys(): array
    {
        $keys = EmployeeCustomField::allFixedFieldKeys();
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));

        return array_values(array_filter($keys, function ($fieldKey) use ($employeeColumns) {
            return isset($employeeColumns[$fieldKey]);
        }));
    }

    /**
     * Update which category a fixed employee field is assigned to.
     */
    public function updateFieldAssignment(Request $request)
    {
        $validated = $request->validate([
            'field_key' => 'required|string|max:80',
            'category_id' => ['required', 'integer', Rule::in($this->scopedCategoryIds())],
            'display_label' => 'nullable|string|max:255',
            'input_type' => 'nullable|string|max:50',
            'config' => 'nullable|array',
        ]);
        $keys = $this->validFixedAssignableFieldKeys();
        if (!in_array($validated['field_key'], $keys, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field.'], 422);
        }
        $assignment = EmployeeFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
        if (!$assignment) {
            $assignment = new EmployeeFieldCategoryAssignment();
            $assignment->field_key = $validated['field_key'];
        }
        $newCategoryId = (int) $validated['category_id'];
        $assignment->category_id = $newCategoryId;
        if (!$assignment->exists || (int) $assignment->getOriginal('category_id') !== $newCategoryId) {
            $assignment->display_order = (int) EmployeeFieldCategoryAssignment::where('category_id', $newCategoryId)->max('display_order') + 1;
        }
        if (array_key_exists('display_label', $validated)) {
            $assignment->display_label = $validated['display_label'] ? trim($validated['display_label']) : null;
        }
        if (array_key_exists('input_type', $validated)) {
            $allowedInputTypes = array_keys(EmployeeCustomField::dataTypes());
            if (!in_array($validated['input_type'], $allowedInputTypes, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field type.'], 422);
            }
            $assignment->input_type = $validated['input_type'];
            $assignment->input_config = $this->sanitizeInputTypeConfig($validated['input_type'], (array) ($validated['config'] ?? []));
        }
        $assignment->save();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated.']);
        }
        return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-fields'])->with('success', 'Category updated.');
    }

    protected function sanitizeInputTypeConfig(string $inputType, array $config): array
    {
        $typeMeta = EmployeeCustomField::dataTypes()[$inputType] ?? null;
        if (!$typeMeta || empty($typeMeta['config']) || !is_array($typeMeta['config'])) {
            return [];
        }

        $sanitized = [];
        foreach ($typeMeta['config'] as $cfg) {
            $key = $cfg['key'] ?? null;
            if (!$key || !array_key_exists($key, $config)) {
                continue;
            }
            $value = $config[$key];
            if (is_string($value)) {
                $value = trim($value);
            }
            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Update only the display label for a fixed employee field assignment.
     */
    public function updateFieldAssignmentLabel(Request $request)
    {
        $validated = $request->validate([
            'field_key' => 'required|string|max:80',
            'display_label' => 'nullable|string|max:255',
        ]);
        $keys = $this->validFixedAssignableFieldKeys();
        if (!in_array($validated['field_key'], $keys, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field.'], 422);
        }
        $assignment = EmployeeFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        $assignment->display_label = isset($validated['display_label']) && trim((string) $validated['display_label']) !== ''
            ? trim($validated['display_label'])
            : null;
        $assignment->save();
        return response()->json(['success' => true, 'message' => 'Label updated.', 'label' => $assignment->display_label ?? EmployeeCustomField::humanizeFieldKey($assignment->field_key)]);
    }

    /**
     * Toggle visibility of a fixed field in the Employee module (Add/Edit/View).
     */
    public function updateFieldAssignmentVisibility(Request $request)
    {
        try {
            $payload = $request->isJson() ? $request->json()->all() : $request->all();

            $validated = validator($payload, [
                'field_key' => 'required|string|max:80',
                'is_visible' => 'required',
            ], [
                'is_visible.required' => 'The visible flag is required.',
            ])->validate();

            $isVisible = filter_var($validated['is_visible'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isVisible === null) {
                $isVisible = !empty($validated['is_visible']) && $validated['is_visible'] !== 'false' && $validated['is_visible'] !== '0';
            }
            $isVisible = (bool) $isVisible;

            $keys = $this->validFixedAssignableFieldKeys();
            if (!in_array($validated['field_key'], $keys, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field key: ' . $validated['field_key']], 422);
            }

            $table = (new EmployeeFieldCategoryAssignment)->getTable();
            if (!Schema::hasColumn($table, 'is_visible')) {
                return response()->json(['success' => false, 'message' => 'Database migration required. Run: php artisan migrate'], 500);
            }

            $assignment = EmployeeFieldCategoryAssignment::query()
                ->where('field_key', $validated['field_key'])
                ->first();
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found for field: ' . $validated['field_key']], 404);
            }

            $value = $isVisible ? 1 : 0;
            $assignment->is_visible = $value;
            $assignment->save();

            return response()->json([
                'success' => true,
                'message' => $isVisible ? 'Field will show in Employee module.' : 'Field hidden from Employee module.',
                'is_visible' => $isVisible,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating field visibility: ' . $e->getMessage(), [
                'field_key' => $validated['field_key'] ?? 'unknown',
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle required flag of a fixed field in the Employee module (Add/Edit).
     */
    public function updateFieldAssignmentRequired(Request $request)
    {
        try {
            $payload = $request->isJson() ? $request->json()->all() : $request->all();

            $validated = validator($payload, [
                'field_key' => 'required|string|max:80',
                'is_required' => 'required',
            ], [
                'is_required.required' => 'The required flag is required.',
            ])->validate();

            $isRequired = filter_var($validated['is_required'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isRequired === null) {
                $isRequired = !empty($validated['is_required']) && $validated['is_required'] !== 'false' && $validated['is_required'] !== '0';
            }
            $isRequired = (bool) $isRequired;

            $keys = $this->validFixedAssignableFieldKeys();
            if (!in_array($validated['field_key'], $keys, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field key: ' . $validated['field_key']], 422);
            }

            $table = (new EmployeeFieldCategoryAssignment)->getTable();
            if (!Schema::hasColumn($table, 'is_required')) {
                return response()->json(['success' => false, 'message' => 'Database migration required. Run: php artisan migrate'], 500);
            }

            $assignment = EmployeeFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found for field: ' . $validated['field_key']], 404);
            }

            $assignment->is_required = $isRequired ? 1 : 0;
            $assignment->save();

            return response()->json([
                'success' => true,
                'message' => $isRequired ? 'Field marked as required.' : 'Field marked as optional.',
                'is_required' => $isRequired,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating field required flag: ' . $e->getMessage(), [
                'field_key' => $validated['field_key'] ?? 'unknown',
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder fields within a category (drag-and-drop).
     */
    public function reorderFieldAssignments(Request $request)
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $validated = validator($payload, [
            'category_id' => ['required', 'integer', Rule::in($this->scopedCategoryIds())],
            'order' => 'required|array',
            'order.*' => 'string|max:80',
        ])->validate();
        $categoryId = (int) $validated['category_id'];
        foreach ($validated['order'] as $position => $fieldKey) {
            EmployeeFieldCategoryAssignment::where('field_key', $fieldKey)->where('category_id', $categoryId)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Save the display name for Employee Settings only.
     */
    public function storeModuleLabel(Request $request)
    {
        $this->saveModuleDisplayLabel($request, 'employees');

        return redirect()->route('settings-panel.employee-settings.index', [
            'company_slug' => $request->route('company_slug') ?? session('company_slug'),
        ])->with('success', 'Module name updated.');
    }

    // ---------- Employee Categories ----------

    public function storeCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first() ?: 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $validated['display_order'] = (int) $this->employeeCategoryQuery()->max('display_order') + 1;
        $validated['is_system'] = false;
        $validated['slug'] = null; // User-created categories have no slug
        if ($this->employeeCategoryCompanyScoped()) {
            $validated['company_id'] = $this->employeeCategoryCompanyId();
        }

        EmployeeCategory::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category added successfully.']);
        }
        return redirect()->route('settings-panel.employee-settings.index')->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, $company_slug, $id)
    {
        $category = $this->findScopedEmployeeCategory((int) $id);
        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);
        if ($this->employeeCategoryCompanyScoped() && empty($category->company_id)) {
            $category->company_id = $this->employeeCategoryCompanyId();
        }
        $category->label = $validated['label'];
        $category->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }
        return redirect()->route('settings-panel.employee-settings.index')->with('success', 'Category updated successfully.');
    }

    public function destroyCategory($company_slug, $id)
    {
        $category = $this->findScopedEmployeeCategory((int) $id);
        $request = request();

        $hasCustomFields = $category->customFields()->exists();
        $hasFixedFields = EmployeeFieldCategoryAssignment::where('category_id', $category->id)->exists();
        if ($hasCustomFields || $hasFixedFields) {
            $message = 'Cannot delete a category that has fields. Move or delete all fixed/custom fields first.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('settings-panel.employee-settings.index')
                ->with('error', $message);
        }
        $category->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category deleted.']);
        }
        return redirect()->route('settings-panel.employee-settings.index')->with('success', 'Category deleted.');
    }

    public function reorderCategories(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:employee_categories,id',
        ]);
        $orderIds = array_map('intval', (array) $request->input('order', []));
        $allowedIds = $this->employeeCategoryQuery()
            ->whereIn('id', $orderIds)
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();

        foreach ($orderIds as $position => $id) {
            if (!in_array($id, $allowedIds, true)) {
                continue;
            }
            EmployeeCategory::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function categoriesTableBody()
    {
        $categories = $this->employeeCategoryQuery()->orderBy('display_order')->orderBy('id')->get();
        return view('settings.employee_settings._categories_tbody', compact('categories'));
    }

    /**
     * Store a new employee custom field under a specific category.
     */
    public function storeField(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(EmployeeCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'is_visible' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
            'category_id' => ['nullable', 'integer', Rule::in($this->scopedCategoryIds())],
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['prevent_duplicate_values'] = $request->boolean('prevent_duplicate_values');
        $validated['help_text'] = $request->input('help_text');
        $validated['default_value'] = $request->input('default_value');
        $validated['input_format'] = $request->input('input_format');
        // New custom fields must start as unassigned and only appear in Employee module
        // after explicit category assignment from Employee Fields settings.
        $validated['category_id'] = null;
        $validated['data_privacy'] = [
            'pii' => $request->boolean('data_privacy_pii'),
            'ephi' => $request->boolean('data_privacy_ephi'),
        ];
        $config = $request->input('config');
        $validated['config'] = is_string($config) ? (json_decode($config, true) ?? []) : (is_array($config) ? $config : []);
        $validated['display_order'] = (int) EmployeeCustomField::max('display_order') + 1;

        EmployeeCustomField::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field added successfully.']);
        }

        return redirect()
            ->route('settings-panel.employee-settings.index')
            ->with('success', 'Custom field added successfully.');
    }

    public function assignCustomFieldCategory(Request $request, $company_slug, $id)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::in($this->scopedCategoryIds())],
        ]);

        $field = EmployeeCustomField::findOrFail($id);
        $field->category_id = (int) $validated['category_id'];
        $field->display_order = (int) EmployeeCustomField::where('category_id', $field->category_id)->max('display_order') + 1;
        $field->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field assigned to category.']);
        }

        return redirect()
            ->route('settings-panel.employee-settings.index')
            ->with('success', 'Custom field assigned to category.');
    }

    /**
     * Update an existing employee custom field.
     */
    public function updateField(Request $request, $company_slug, $id)
    {
        $field = EmployeeCustomField::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(EmployeeCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'is_visible' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
            'category_id' => ['required', 'integer', Rule::in($this->scopedCategoryIds())],
        ]);

        $field->label = $validated['label'];
        $field->help_text = $request->input('help_text');
        $field->data_type = $validated['data_type'];
        $field->is_mandatory = $request->boolean('is_mandatory');
        $field->is_visible = $request->boolean('is_visible', true);
        $field->prevent_duplicate_values = $request->boolean('prevent_duplicate_values');
        $field->default_value = $request->input('default_value');
        $field->input_format = $request->input('input_format');
        $field->category_id = (int) $request->input('category_id');
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

        return redirect()
            ->route('settings-panel.employee-settings.index')
            ->with('success', 'Custom field updated successfully.');
    }

    /**
     * Delete a employee custom field.
     */
    public function destroyField($company_slug, $id)
    {
        $field = EmployeeCustomField::findOrFail($id);
        $field->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field deleted.']);
        }

        return redirect()
            ->route('settings-panel.employee-settings.index')
            ->with('success', 'Custom field deleted.');
    }

    public function updateCustomFieldFlags(Request $request, $company_slug, $id)
    {
        $validated = $request->validate([
            'is_mandatory' => 'required|boolean',
            'is_visible' => 'required|boolean',
        ]);

        $field = EmployeeCustomField::findOrFail((int) $id);
        $field->is_mandatory = filter_var($validated['is_mandatory'], FILTER_VALIDATE_BOOLEAN);
        $field->is_visible = filter_var($validated['is_visible'], FILTER_VALIDATE_BOOLEAN);
        $field->save();

        return response()->json([
            'success' => true,
            'message' => 'Custom field settings updated.',
            'id' => (int) $field->id,
            'is_mandatory' => (bool) $field->is_mandatory,
            'is_visible' => (bool) $field->is_visible,
        ]);
    }

    /**
     * Reorder employee custom fields (display_order) within a category.
     */
    public function reorderFields(Request $request)
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $validated = validator($payload, [
            'order' => 'required|array',
            'order.*' => 'integer|exists:employee_custom_fields,id',
            'category_id' => ['nullable', 'integer', Rule::in($this->scopedCategoryIds())],
        ])->validate();

        $order = $validated['order'];
        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId) {
            $ids = EmployeeCustomField::where('category_id', $categoryId)->pluck('id')->all();
            $order = array_values(array_intersect($order, $ids));
        }

        foreach ($order as $position => $id) {
            EmployeeCustomField::where('id', $id)->update(['display_order' => $position]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Return config schema for a data type (for dynamic form).
     */
    public function fieldConfigSchema($company_slug, $dataType)
    {
        $types = EmployeeCustomField::dataTypes();
        if (!isset($types[$dataType])) {
            return response()->json(['config' => []], 404);
        }

        return response()->json(['config' => $types[$dataType]['config'] ?? []]);
    }

    /**
     * Return only the custom fields table body HTML (for AJAX refresh after add/edit/delete).
     */
    public function tableBody()
    {
        $customFields = EmployeeCustomField::with('category')->orderBy('display_order')->orderBy('id')->get();
        $dataTypes = EmployeeCustomField::dataTypes();
        $categories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();

        return view('settings.employee_settings._custom_fields_tbody', compact('customFields', 'dataTypes', 'categories'));
    }

    /**
     * Return custom fields rows for one category (for AJAX refresh inside a category tab).
     */
    public function tableBodyCategory($company_slug, $categoryId)
    {
        $category = EmployeeCategory::findOrFail($categoryId);
        $customFields = EmployeeCustomField::where('category_id', $categoryId)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $dataTypes = EmployeeCustomField::dataTypes();
        $categories = EmployeeCategory::orderBy('display_order')->orderBy('id')->get();

        return view('settings.employee_settings._custom_fields_rows_category', compact('customFields', 'dataTypes', 'categories'));
    }

    // ---------- Employee Top ----------

    public function employeeTopAccordionBody()
    {
        $employeeTopCategories = EmployeeTopCategory::with('options')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('settings.partials.top_bar.accordion', [
            'topBarCategories' => $employeeTopCategories,
            'topBarEmptyMessage' => 'No Employee Top categories yet. Add your first category to begin.',
        ]);
    }

    public function storeEmployeeTopCategory(Request $request)
    {
        $allowedColumns = array_keys($this->employeeTopSelectableColumns());
        $validated = $request->validate([
            'employee_column' => ['required', 'string', Rule::in($allowedColumns)],
        ]);

        $employeeColumn = $validated['employee_column'];
        $companyId = auth()->user()->company_id ?? null;
        $existsQuery = EmployeeTopCategory::where('employee_column', $employeeColumn);
        if ($companyId) {
            $existsQuery->where('company_id', $companyId);
        }
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'message' => 'This employee column is already configured as a category.'], 422);
        }

        $category = EmployeeTopCategory::create([
            'name' => EmployeeCustomField::humanizeFieldKey($employeeColumn),
            'employee_column' => $employeeColumn,
            'display_order' => ((int) EmployeeTopCategory::max('display_order')) + 1,
            'is_active' => true,
        ]);

        $seeded = app(\App\Services\Module\ModuleTopBarSettingsService::class)
            ->seedOptionsFromFieldSettings('employees', $category);

        return response()->json([
            'success' => true,
            'message' => $seeded > 0
                ? 'Employee Top category added with ' . $seeded . ' option(s) from field settings.'
                : 'Employee Top category added.',
        ]);
    }

    public function updateEmployeeTopCategoryVisibility(Request $request, $company_slug, $id)
    {
        $category = EmployeeTopCategory::findOrFail($id);
        $validated = $request->validate([
            'show_in_top_bar' => 'nullable|boolean',
            'show_in_view_cards' => 'nullable|boolean',
        ]);

        $category->show_in_top_bar = array_key_exists('show_in_top_bar', $validated)
            ? (bool) $validated['show_in_top_bar']
            : $request->boolean('show_in_top_bar');
        $category->show_in_view_cards = array_key_exists('show_in_view_cards', $validated)
            ? (bool) $validated['show_in_view_cards']
            : $request->boolean('show_in_view_cards');
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Display options updated.',
            'show_in_top_bar' => (bool) $category->show_in_top_bar,
            'show_in_view_cards' => (bool) $category->show_in_view_cards,
        ]);
    }

    public function updateEmployeeTopCategory(Request $request, $company_slug, $id)
    {
        $category = EmployeeTopCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->name = trim($validated['name']);
        $category->save();

        return response()->json(['success' => true, 'message' => 'Employee Top category updated.']);
    }

    public function destroyEmployeeTopCategory($company_slug, $id)
    {
        EmployeeTopCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Employee Top category deleted.']);
    }

    public function storeEmployeeTopOption(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:employee_top_categories,id',
            'name' => 'nullable|string|max:255',
            'selected_values' => 'nullable|array',
            'selected_values.*' => 'nullable|string|max:255',
        ]);

        $categoryId = (int) $validated['category_id'];
        $items = collect($validated['selected_values'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($items->isEmpty()) {
            $single = trim((string) ($validated['name'] ?? ''));
            if ($single !== '') {
                $items = collect([$single]);
            }
        }

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Please select at least one value.'], 422);
        }

        $nextOrder = ((int) EmployeeTopOption::where('category_id', $categoryId)->max('display_order')) + 1;
        $createdCount = 0;
        foreach ($items as $item) {
            $exists = EmployeeTopOption::where('category_id', $categoryId)->where('name', $item)->exists();
            if ($exists) {
                continue;
            }
            EmployeeTopOption::create([
                'category_id' => $categoryId,
                'name' => $item,
                'display_order' => $nextOrder++,
                'is_active' => true,
            ]);
            $createdCount++;
        }

        if ($createdCount === 0) {
            return response()->json(['success' => false, 'message' => 'Selected values already exist as options.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Employee Top option added.']);
    }

    public function employeeTopCategoryFieldValues($company_slug, $id)
    {
        $category = EmployeeTopCategory::findOrFail($id);
        $column = (string) ($category->employee_column ?? '');
        if ($column === '' || !Schema::hasColumn('employees', $column)) {
            return response()->json(['success' => false, 'message' => 'Category source column is invalid.', 'values' => []], 422);
        }

        // First prefer configured dropdown options from Employee field settings (input_config.options).
        $configuredValues = collect();
        $assignment = EmployeeFieldCategoryAssignment::where('field_key', $column)->first();
        if ($assignment && is_array($assignment->input_config) && array_key_exists('options', $assignment->input_config)) {
            $rawOptions = $assignment->input_config['options'];
            if (is_array($rawOptions)) {
                $configuredValues = collect($rawOptions);
            } else {
                $configuredValues = collect(preg_split("/\r\n|\n|\r/", (string) $rawOptions));
            }
            $configuredValues = $configuredValues
                ->map(fn($v) => trim((string) $v))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values();
        }

        $tableValues = Employee::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        // Keep configured values first, then append any extra values present in table data.
        $values = $configuredValues
            ->concat($tableValues)
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'column' => $column,
            'values' => $values,
        ]);
    }

    public function updateEmployeeTopOption(Request $request, $company_slug, $id)
    {
        $option = EmployeeTopOption::findOrFail($id);
        $oldName = trim((string) $option->name);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newName = trim($validated['name']);
        $option->name = $newName;
        $option->save();
        if ($oldName !== '' && $newName !== '' && strcasecmp($oldName, $newName) !== 0) {
            Employee::where('status', $oldName)->update(['status' => $newName]);
        }

        return response()->json(['success' => true, 'message' => 'Employee Top option updated.']);
    }

    public function destroyEmployeeTopOption($company_slug, $id)
    {
        $option = EmployeeTopOption::findOrFail($id);
        $oldName = trim((string) $option->name);
        $option->delete();
        if ($oldName !== '') {
            Employee::where('status', $oldName)->update(['status' => null]);
        }
        return response()->json(['success' => true, 'message' => 'Employee Top option deleted.']);
    }

    public function storeEmployeeStatus(Request $request)
    {
        $category = $this->employeeStatusTopCategory();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'show_in_top_bar' => 'nullable|boolean',
            'show_in_view_cards' => 'nullable|boolean',
        ]);

        $name = trim((string) $validated['name']);
        $exists = EmployeeTopOption::where('category_id', $category->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
                ->with('error', 'This status already exists.');
        }

        EmployeeTopOption::create([
            'category_id' => $category->id,
            'name' => $name,
            'display_order' => ((int) EmployeeTopOption::where('category_id', $category->id)->max('display_order')) + 1,
            'is_active' => true,
            'show_in_top_bar' => $request->boolean('show_in_top_bar', true),
            'show_in_view_cards' => $request->boolean('show_in_view_cards', true),
        ]);

        return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
            ->with('success', 'Employee status added.');
    }

    public function updateEmployeeStatus(Request $request, $company_slug, $id)
    {
        $option = EmployeeTopOption::findOrFail($id);
        $category = $this->employeeStatusTopCategory();
        if ((int) $option->category_id !== (int) $category->id) {
            return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
                ->with('error', 'Invalid status option.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'show_in_top_bar' => 'nullable|boolean',
            'show_in_view_cards' => 'nullable|boolean',
        ]);

        $oldName = trim((string) $option->name);
        $newName = trim((string) $validated['name']);
        $dupExists = EmployeeTopOption::where('category_id', $category->id)
            ->where('id', '!=', $option->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])
            ->exists();
        if ($dupExists) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Another status with this name already exists.'], 422);
            }
            return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
                ->with('error', 'Another status with this name already exists.');
        }

        $option->name = $newName;
        $option->show_in_top_bar = $request->boolean('show_in_top_bar');
        $option->show_in_view_cards = $request->boolean('show_in_view_cards');
        $option->save();

        if ($oldName !== '' && $newName !== '' && strcasecmp($oldName, $newName) !== 0) {
            Employee::where('status', $oldName)->update(['status' => $newName]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee status updated.',
                'name' => $option->name,
                'show_in_top_bar' => (bool) $option->show_in_top_bar,
                'show_in_view_cards' => (bool) $option->show_in_view_cards,
            ]);
        }

        return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
            ->with('success', 'Employee status updated.');
    }

    public function destroyEmployeeStatus($company_slug, $id)
    {
        $option = EmployeeTopOption::findOrFail($id);
        $category = $this->employeeStatusTopCategory();
        if ((int) $option->category_id !== (int) $category->id) {
            return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
                ->with('error', 'Invalid status option.');
        }

        $statusName = trim((string) $option->name);
        $option->delete();
        if ($statusName !== '') {
            Employee::where('status', $statusName)->update(['status' => null]);
        }

        return redirect()->route('settings-panel.employee-settings.index', ['tab' => 'employee-status'])
            ->with('success', 'Employee status deleted and unassigned from employees.');
    }

    // ---------- Employee Documents ----------

    /**
     * Return document types table body (for AJAX refresh).
     */
    public function documentTypesTableBody()
    {
        $documentTypes = EmployeeDocumentType::orderedForAdmin()->get();
        return view('settings.employee_settings._document_types_tbody', compact('documentTypes'));
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:80|regex:/^[a-z0-9_]+$/|unique:employee_document_types,key',
            'type' => 'required|in:single,dual',
            'label' => 'nullable|string|max:255',
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validated['type'] === 'single' && empty(trim($validated['label'] ?? ''))) {
            return response()->json(['message' => 'Label is required for single document type.'], 422);
        }
        if ($validated['type'] === 'dual' && (empty(trim($validated['front_label'] ?? '')) || empty(trim($validated['back_label'] ?? '')))) {
            return response()->json(['message' => 'Front and back labels are required for dual document type.'], 422);
        }
        $maxOrder = (int) EmployeeDocumentType::max('display_order');
        EmployeeDocumentType::create([
            'key' => $validated['key'],
            'type' => $validated['type'],
            'label' => $validated['type'] === 'single' ? trim($validated['label']) : null,
            'front_label' => $validated['type'] === 'dual' ? trim($validated['front_label']) : null,
            'back_label' => $validated['type'] === 'dual' ? trim($validated['back_label']) : null,
            'display_order' => $maxOrder + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return response()->json(['success' => true, 'message' => 'Document type added.']);
    }

    public function updateDocumentType(Request $request, $company_slug, $id)
    {
        $docType = EmployeeDocumentType::findOrFail($id);
        $validated = $request->validate([
            'key' => 'required|string|max:80|regex:/^[a-z0-9_]+$/|unique:employee_document_types,key,' . $id,
            'type' => 'required|in:single,dual',
            'label' => 'nullable|string|max:255',
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validated['type'] === 'single' && empty(trim($validated['label'] ?? ''))) {
            return response()->json(['message' => 'Label is required for single document type.'], 422);
        }
        if ($validated['type'] === 'dual' && (empty(trim($validated['front_label'] ?? '')) || empty(trim($validated['back_label'] ?? '')))) {
            return response()->json(['message' => 'Front and back labels are required for dual document type.'], 422);
        }
        $docType->update([
            'key' => $validated['key'],
            'type' => $validated['type'],
            'label' => $validated['type'] === 'single' ? trim($validated['label']) : null,
            'front_label' => $validated['type'] === 'dual' ? trim($validated['front_label']) : null,
            'back_label' => $validated['type'] === 'dual' ? trim($validated['back_label']) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return response()->json(['success' => true, 'message' => 'Document type updated.']);
    }

    public function destroyDocumentType($company_slug, $id)
    {
        EmployeeDocumentType::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Document type deleted.']);
    }

    public function reorderDocumentTypes(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:employee_document_types,id']);
        foreach ($request->input('order') as $position => $id) {
            EmployeeDocumentType::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true]);
    }
}
