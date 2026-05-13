<?php

namespace App\Http\Controllers;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeDocumentType;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\BikeTopCategory;
use App\Models\BikeTopOption;
use App\Models\Bikes;
use App\Models\Settings;
use App\Models\UserTableSettings;
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
        'rider_id',
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

        $bikeTopCategories = collect();
        $bikeTopSelectableColumns = [];
        $bikeTopUserVisibleOptionIds = null;
        $bikeTopAllOptionIds = [];
        if (Schema::hasTable('bike_top_categories')) {
            $bikeTopCategories = BikeTopCategory::with('options')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
            $bikeTopSelectableColumns = $this->bikeTopSelectableColumns();
            if (Schema::hasTable('bike_top_options')) {
                $bikeTopAllOptionIds = BikeTopOption::query()
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();
            }
            $userBikeTable = UserTableSettings::getSettings(auth()->id(), 'bikes_table');
            if ($userBikeTable && is_array($userBikeTable->additional_settings)) {
                $bikeTopUserVisibleOptionIds = $userBikeTable->additional_settings['bike_top_visible_option_ids'] ?? null;
            }
        }

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
            'bikeTopCategories',
            'bikeTopSelectableColumns',
            'bikeTopUserVisibleOptionIds',
            'bikeTopAllOptionIds',
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
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'System categories cannot be edited.'], 422);
            }
            return redirect()->back()->with('error', 'System categories cannot be edited.');
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $category->label = $validated['label'];
        $category->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category updated.',
                'category' => [
                    'id' => (int) $category->id,
                    'label' => (string) $category->label,
                ],
            ]);
        }

        return $this->bikeSettingsIndexRedirect()->with('success', 'Category updated.');
    }

    public function destroyCategory(Request $request, string $company_slug, int $id)
    {
        $category = $this->bikeCategoryQuery()->where('id', $id)->firstOrFail();
        if ((bool) $category->is_system) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'System categories cannot be deleted.'], 422);
            }
            return redirect()->back()->with('error', 'System categories cannot be deleted.');
        }

        if (BikeFieldCategoryAssignment::where('category_id', $category->id)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Category has fixed field assignments. Remove/reassign them first.'], 422);
            }
            return redirect()->back()->with('error', 'Category has fixed field assignments. Remove/reassign them first.');
        }

        if (BikeCustomField::where('category_id', $category->id)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Category has custom fields. Remove/reassign them first.'], 422);
            }
            return redirect()->back()->with('error', 'Category has custom fields. Remove/reassign them first.');
        }

        $category->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category deleted.',
                'category_id' => $id,
            ]);
        }

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

        // Keep current category when empty is submitted; otherwise move to selected category.
        if (isset($validated['category_id']) && $validated['category_id'] !== null && $validated['category_id'] !== '') {
            $assignment->category_id = (int) $validated['category_id'];
        }

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
            ->reject(fn($fieldKey) => in_array((string) $fieldKey, $this->hiddenFixedFieldKeys, true))
            ->values()
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

    /**
     * Columns on `bikes` that can back a Vehicle Top category (distinct values become pick options).
     *
     * @return array<string, string>
     */
    protected function bikeTopSelectableColumns(): array
    {
        if (!Schema::hasTable('bikes')) {
            return [];
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
            'bike_top_option_id',
        ];

        $bikeColumns = Schema::getColumnListing('bikes');
        $options = [];
        foreach ($bikeColumns as $fieldKey) {
            if (in_array($fieldKey, $systemColumns, true)) {
                continue;
            }
            if (in_array($fieldKey, $this->hiddenFixedFieldKeys, true)) {
                continue;
            }
            $options[$fieldKey] = BikeCustomField::humanizeFieldKey($fieldKey);
        }

        asort($options);

        return $options;
    }

    public function bikeTopAccordionBody()
    {
        $bikeTopCategories = BikeTopCategory::with('options')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('settings.bike_settings._bike_top_accordion', compact('bikeTopCategories'));
    }

    public function storeBikeTopCategory(Request $request)
    {
        $allowedColumns = array_keys($this->bikeTopSelectableColumns());
        $validated = $request->validate([
            'bike_column' => ['required', 'string', Rule::in($allowedColumns)],
        ]);

        $bikeColumn = $validated['bike_column'];
        $existsQuery = BikeTopCategory::where('bike_column', $bikeColumn);
        if ($existsQuery->exists()) {
            return response()->json(['success' => false, 'message' => 'This vehicle column is already configured as a category.'], 422);
        }

        BikeTopCategory::create([
            'name' => BikeCustomField::humanizeFieldKey($bikeColumn),
            'bike_column' => $bikeColumn,
            'display_order' => ((int) BikeTopCategory::max('display_order')) + 1,
            'is_active' => true,
            'show_in_top_bar' => true,
            'show_in_view_cards' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Vehicle Top category added.']);
    }

    public function updateBikeTopCategoryVisibility(Request $request, string $company_slug, int $id)
    {
        $category = BikeTopCategory::findOrFail($id);
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

    public function updateBikeTopCategory(Request $request, string $company_slug, int $id)
    {
        $category = BikeTopCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->name = trim($validated['name']);
        $category->save();

        return response()->json(['success' => true, 'message' => 'Vehicle Top category updated.']);
    }

    public function destroyBikeTopCategory(string $company_slug, int $id)
    {
        BikeTopCategory::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Vehicle Top category deleted.']);
    }

    public function storeBikeTopOption(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:bike_top_categories,id',
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

        $nextOrder = ((int) BikeTopOption::where('category_id', $categoryId)->max('display_order')) + 1;
        $createdCount = 0;
        foreach ($items as $item) {
            $exists = BikeTopOption::where('category_id', $categoryId)->where('name', $item)->exists();
            if ($exists) {
                continue;
            }
            BikeTopOption::create([
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

        return response()->json(['success' => true, 'message' => 'Vehicle Top option added.']);
    }

    public function bikeTopCategoryFieldValues(string $company_slug, int $id)
    {
        $category = BikeTopCategory::findOrFail($id);
        $column = (string) ($category->bike_column ?? '');
        if ($column === '' || !Schema::hasColumn('bikes', $column)) {
            return response()->json(['success' => false, 'message' => 'Category source column is invalid.', 'values' => []], 422);
        }

        $formChoices = BikeCustomField::fixedFieldSelectChoices($column);

        $configuredValues = collect();
        $assignment = BikeFieldCategoryAssignment::where('field_key', $column)->first();
        if ($formChoices === [] && $assignment && is_array($assignment->input_config) && array_key_exists('options', $assignment->input_config)) {
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

        $tableValues = Bikes::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($formChoices !== []) {
            $choices = collect($formChoices);
            $valueSet = $choices->pluck('value')->map(fn($v) => (string) $v)->all();
            $valueSet = array_flip($valueSet);
            foreach ($tableValues as $v) {
                $sv = trim((string) $v);
                if ($sv === '' || isset($valueSet[$sv])) {
                    continue;
                }
                $choices->push(['value' => $sv, 'label' => $sv]);
                $valueSet[$sv] = true;
            }
            $values = $choices->pluck('value')->values()->all();

            return response()->json([
                'success' => true,
                'column' => $column,
                'values' => $values,
                'choices' => $choices->values()->all(),
            ]);
        }

        $values = $configuredValues
            ->concat($tableValues)
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'column' => $column,
            'values' => $values->all(),
            'choices' => null,
        ]);
    }

    public function updateBikeTopOption(Request $request, string $company_slug, int $id)
    {
        $option = BikeTopOption::findOrFail($id);
        $oldName = trim((string) $option->name);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newName = trim($validated['name']);
        $option->name = $newName;
        $option->save();

        $category = $option->category;
        $column = $category ? trim((string) ($category->bike_column ?? '')) : '';
        if (
            $oldName !== '' && $newName !== '' && strcasecmp($oldName, $newName) !== 0
            && $column !== '' && Schema::hasColumn('bikes', $column)
        ) {
            Bikes::where($column, $oldName)->update([$column => $newName]);
        }

        return response()->json(['success' => true, 'message' => 'Vehicle Top option updated.']);
    }

    public function destroyBikeTopOption(string $company_slug, int $id)
    {
        $option = BikeTopOption::findOrFail($id);
        $oldName = trim((string) $option->name);
        $category = $option->category;
        $column = $category ? trim((string) ($category->bike_column ?? '')) : '';

        Bikes::where('bike_top_option_id', $option->id)->update(['bike_top_option_id' => null]);

        $option->delete();

        if ($oldName !== '' && $column !== '' && Schema::hasColumn('bikes', $column)) {
            Bikes::where($column, $oldName)->update([$column => null]);
        }

        return response()->json(['success' => true, 'message' => 'Vehicle Top option deleted.']);
    }

    public function saveBikeTopUserPreferences(Request $request)
    {
        if (!Schema::hasTable('bike_top_options')) {
            return response()->json(['success' => false, 'message' => 'Vehicle Top is not available.'], 422);
        }

        $validated = $request->validate([
            'visible_option_ids' => 'nullable|array',
            'visible_option_ids.*' => 'integer|exists:bike_top_options,id',
        ]);

        $ids = collect($validated['visible_option_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $existing = UserTableSettings::getSettings(auth()->id(), 'bikes_table');
        $additional = is_array($existing?->additional_settings) ? $existing->additional_settings : [];

        if ($ids === []) {
            unset($additional['bike_top_visible_option_ids']);
        } else {
            $additional['bike_top_visible_option_ids'] = $ids;
        }

        UserTableSettings::saveSettings(
            auth()->id(),
            'bikes_table',
            $existing?->visible_columns,
            $existing?->column_order,
            $additional
        );

        return response()->json(['success' => true, 'message' => 'Your vehicle top bar preferences were saved.']);
    }
}
