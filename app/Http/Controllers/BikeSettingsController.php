<?php

namespace App\Http\Controllers;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeDocumentType;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\Settings;
use App\Support\ModuleFieldSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BikeSettingsController extends Controller
{
    /**
     * Fixed bike fields hidden from Bike Settings and Bike form.
     */
    protected array $hiddenFixedFieldKeys = [
        'company_id',
        'current_km',
        'maintanence_km',
        'maintenance_km',
        'previous_km',
        'customer_id',
        'emirates',
    ];

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

    /**
     * Ensure every real bikes-table column has an assignment row and cannot be "optional" or hidden via settings.
     */
    protected function syncBikeSchemaAssignmentsFromDb(): void
    {
        if (!Schema::hasTable('bikes') || !Schema::hasTable('bike_field_category_assignments') || !Schema::hasTable('bike_categories')) {
            return;
        }

        $systemColumns = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'custom_field_values',
            'created_by',
            'updated_by',
            'deleted_by',
        ];

        $bikeColumns = Schema::getColumnListing('bikes');
        $fieldKeys = array_values(array_filter($bikeColumns, function ($col) use ($systemColumns) {
            return !in_array($col, $systemColumns, true);
        }));

        $slugToCategoryId = DB::table('bike_categories')->pluck('id', 'slug')->all();
        $otherId = (int) ($slugToCategoryId['other'] ?? 0);
        if ($otherId <= 0) {
            return;
        }

        $mapping = [
            'bike_info' => [
                'plate',
                'bike_code',
                'chassis_number',
                'engine',
                'vehicle_type',
                'model',
                'model_type',
                'color',
                'emirates',
                'branch_id',
                'company',
                'rider_id',
                'warehouse',
                'traffic_file_number',
                'registration_date',
                'expiry_date',
                'notes',
                'status',
                'customer_id',
            ],
            'insurance_info' => [
                'insurance_expiry',
                'insurance_co',
                'policy_no',
            ],
            'documents_info' => [
                'contract_number',
            ],
        ];

        $resolvedCategoryForField = [];
        foreach ($fieldKeys as $key) {
            $resolvedCategoryForField[$key] = $otherId;
            foreach ($mapping as $slug => $keys) {
                if (in_array($key, $keys, true)) {
                    $catId = (int) ($slugToCategoryId[$slug] ?? $otherId);
                    $resolvedCategoryForField[$key] = $catId > 0 ? $catId : $otherId;
                    break;
                }
            }
        }

        $fieldKeys = array_values(array_filter($fieldKeys, function ($fieldKey) {
            return !in_array($fieldKey, $this->hiddenFixedFieldKeys, true);
        }));
        sort($fieldKeys);
        foreach ($fieldKeys as $fieldKey) {
            $categoryId = (int) ($resolvedCategoryForField[$fieldKey] ?? $otherId);
            $assignment = BikeFieldCategoryAssignment::where('field_key', $fieldKey)->first();
            if ($assignment) {
                continue;
            }

            $nextOrder = ((int) BikeFieldCategoryAssignment::where('category_id', $categoryId)->max('display_order')) + 1;
            BikeFieldCategoryAssignment::create([
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'display_order' => $nextOrder,
                'display_label' => null,
                'input_type' => null,
                'input_config' => null,
                'is_visible' => true,
                'is_required' => true,
            ]);
        }
    }

    public function index()
    {
        $this->syncBikeSchemaAssignmentsFromDb();

        $categories = $this->bikeCategoryQuery()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $fixedAssignments = BikeFieldCategoryAssignment::with('category')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->reject(function ($assignment) {
                return in_array((string) $assignment->field_key, $this->hiddenFixedFieldKeys, true);
            })
            ->values();

        $fixedAssignmentsByCategory = $fixedAssignments->groupBy('category_id');

        $customFields = BikeCustomField::with('category')
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
        ) + [
            'moduleKey' => 'bike_list',
            'moduleSchemaFieldKeys' => ModuleFieldSource::schemaFieldKeysForModule('bike_list'),
        ]);
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

    public function updateCategory(Request $request, string $company_slug, int $id)
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

    public function destroyCategory(string $company_slug, int $id)
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
            'category_id' => ['nullable', 'integer', 'exists:bike_categories,id'],
            'display_label' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'input_type' => ['nullable', 'string', 'max:50'],
            'input_config_options' => ['nullable', 'string'],
        ]);

        $assignment = BikeFieldCategoryAssignment::where('field_key', $validated['field_key'])->firstOrFail();
        if (in_array((string) $assignment->field_key, $this->hiddenFixedFieldKeys, true)) {
            if (!$request->wantsJson() && !$request->ajax()) {
                return $this->bikeSettingsIndexRedirect()->with('error', 'This field is hidden and cannot be edited.');
            }
            return response()->json([
                'success' => false,
                'message' => 'This field is hidden and cannot be edited.',
            ], 422);
        }

        // Keep existing category; category moving is disabled for simplified assignment flow.
        $assignment->category_id = (int) $assignment->category_id;

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

    public function reorderFieldAssignments(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:bike_categories,id'],
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string', 'max:80'],
        ]);

        $categoryId = (int) $validated['category_id'];
        $order = array_values(array_unique(array_map('strval', $validated['order'])));

        $allowedKeys = BikeFieldCategoryAssignment::query()
            ->where('category_id', $categoryId)
            ->pluck('field_key')
            ->map(fn($v) => (string) $v)
            ->all();

        foreach ($order as $fieldKey) {
            if (!in_array($fieldKey, $allowedKeys, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
            }
        }
        if (count($order) !== count($allowedKeys)) {
            return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
        }

        $this->mergeBikeFixedFieldOrderForCategory($categoryId, $order);

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function reorderAllFieldAssignments(Request $request)
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string', 'max:80'],
        ]);
        $order = array_values(array_map('strval', $validated['order']));
        $existing = BikeFieldCategoryAssignment::query()
            ->pluck('field_key')
            ->map(fn($v) => (string) $v)
            ->sort()
            ->values()
            ->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $existing || count($order) !== count($existing)) {
            return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
        }

        foreach ($order as $pos => $fieldKey) {
            BikeFieldCategoryAssignment::query()
                ->where('field_key', $fieldKey)
                ->update(['display_order' => $pos]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * @param  list<string>  $orderedKeysInCategory
     */
    protected function mergeBikeFixedFieldOrderForCategory(int $categoryId, array $orderedKeysInCategory): void
    {
        $rows = BikeFieldCategoryAssignment::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $globalKeys = $rows->pluck('field_key')->map(fn($v) => (string) $v)->all();
        $newGlobal = [];
        $i = 0;
        $n = count($globalKeys);
        $blockInserted = false;

        while ($i < $n) {
            $k = $globalKeys[$i];
            $row = $rows->firstWhere('field_key', $k);
            if ((int) $row->category_id === $categoryId) {
                if (!$blockInserted) {
                    foreach ($orderedKeysInCategory as $nk) {
                        $newGlobal[] = (string) $nk;
                    }
                    $blockInserted = true;
                }
                $i++;
                while ($i < $n) {
                    $k2 = $globalKeys[$i];
                    $r2 = $rows->firstWhere('field_key', $k2);
                    if ((int) $r2->category_id === $categoryId) {
                        $i++;
                    } else {
                        break;
                    }
                }
            } else {
                $newGlobal[] = $k;
                $i++;
            }
        }

        foreach ($newGlobal as $pos => $fieldKey) {
            BikeFieldCategoryAssignment::query()
                ->where('field_key', $fieldKey)
                ->update(['display_order' => $pos]);
        }
    }

    public function reorderFields(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:bike_categories,id'],
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:bike_custom_fields,id'],
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $order = array_values(array_unique(array_map('intval', $validated['order'])));

        $query = BikeCustomField::query()->whereIn('id', $order);
        if ($categoryId === null) {
            $query->whereNull('category_id');
        } else {
            $query->where('category_id', (int) $categoryId);
        }
        $allowedIds = $query->pluck('id')->map(fn($v) => (int) $v)->sort()->values()->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $allowedIds || count($order) !== count($allowedIds)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        $this->mergeBikeCustomFieldOrderForCategory($categoryId, $order);

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function reorderAllCustomFields(Request $request)
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:bike_custom_fields,id'],
        ]);
        $order = array_values(array_map('intval', $validated['order']));
        $existing = BikeCustomField::query()
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->sort()
            ->values()
            ->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $existing || count($order) !== count($existing)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $pos => $id) {
            BikeCustomField::query()->where('id', $id)->update(['display_order' => $pos]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * @param  list<int>  $orderedIdsInCategory
     */
    protected function mergeBikeCustomFieldOrderForCategory(?int $categoryId, array $orderedIdsInCategory): void
    {
        $rows = BikeCustomField::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $inCategory = function ($row) use ($categoryId) {
            if ($categoryId === null) {
                return $row->category_id === null;
            }

            return (int) $row->category_id === (int) $categoryId;
        };

        $globalIds = $rows->pluck('id')->map(fn($v) => (int) $v)->all();
        $newGlobal = [];
        $i = 0;
        $n = count($globalIds);
        $blockInserted = false;

        while ($i < $n) {
            $id = $globalIds[$i];
            $row = $rows->firstWhere('id', $id);
            if ($inCategory($row)) {
                if (!$blockInserted) {
                    foreach ($orderedIdsInCategory as $nid) {
                        $newGlobal[] = (int) $nid;
                    }
                    $blockInserted = true;
                }
                $i++;
                while ($i < $n) {
                    $id2 = $globalIds[$i];
                    $r2 = $rows->firstWhere('id', $id2);
                    if ($inCategory($r2)) {
                        $i++;
                    } else {
                        break;
                    }
                }
            } else {
                $newGlobal[] = $id;
                $i++;
            }
        }

        foreach ($newGlobal as $pos => $fid) {
            BikeCustomField::query()->where('id', $fid)->update(['display_order' => $pos]);
        }
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

    public function updateField(Request $request, string $company_slug, int $id)
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

    public function destroyField(string $company_slug, int $id)
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
    public function assignCustomFieldCategory(Request $request, string $company_slug, int $id)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:bike_categories,id'],
        ]);

        $field = BikeCustomField::where('id', $id)->firstOrFail();
        $field->category_id = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        $field->save();

        $activeCategoryId = $field->category_id !== null ? (int) $field->category_id : 0;
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

    public function updateDocumentType(Request $request, string $company_slug, int $id)
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

    public function destroyDocumentType(string $company_slug, int $id)
    {
        $field = BikeDocumentType::where('id', $id)->firstOrFail();
        $field->delete();

        return $this->bikeSettingsIndexRedirect()->with('success', 'Document type deleted.');
    }
}
