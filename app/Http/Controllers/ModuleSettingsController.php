<?php

namespace App\Http\Controllers;

use App\Models\ModuleCustomField;
use App\Models\ModuleDocumentType;
use App\Models\ModuleFieldCategoryAssignment;
use App\Models\ModuleSettingCategory;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ModuleSettingsController extends Controller
{
    protected array $defaultExcludedDbFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function normalizeModuleKey(string $module): string
    {
        return str_replace('-', '_', strtolower(trim($module)));
    }

    protected function normalizeCompanyScopedQuery($query, ?int $companyId)
    {
        return $query->where(function ($sub) use ($companyId) {
            $sub->whereNull('company_id');
            if ($companyId) {
                $sub->orWhere('company_id', $companyId);
            }
        });
    }

    protected function categoryBelongsToModuleRule(string $module): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($module) {
            if ($value === null || $value === '') {
                return;
            }

            $exists = ModuleSettingCategory::query()
                ->where('id', (int) $value)
                ->where('module_key', $module)
                ->exists();

            if (!$exists) {
                $fail('Selected category is invalid for this module.');
            }
        };
    }

    protected function sourceTableMap(): array
    {
        return [
            'bike_list' => 'bikes',
            'cash_banks' => 'banks',
            'riders_list' => 'riders',
            'employees' => 'employees',
            'customers' => 'customers',
            'vendors' => 'vendors',
            'recruiters' => 'recruiters',
            'sims' => 'sims',
            'fuel_cards' => 'fuel_cards',
            'rta_fines' => 'rta_fines',
            'rta_saliks' => 'salik_transactions',
            'garages' => 'garages',
            'suppliers' => 'suppliers',
            'leasing_companies' => 'leasing_companies',
            'expenses' => 'expenses',
            'items_list' => 'items',
            'garage_items' => 'garage_items',
            'vouchers' => 'vouchers',
            'accounts' => 'accounts',
        ];
    }

    protected function resolveModuleSourceTable(string $module): ?string
    {
        $map = $this->sourceTableMap();
        if (isset($map[$module]) && Schema::hasTable($map[$module])) {
            return $map[$module];
        }

        $normalized = str_replace('-', '_', $module);
        $base = preg_replace('/(_list|_settings|_overview|_report|_reports)$/', '', $normalized) ?: $normalized;
        $candidates = array_values(array_unique([
            $normalized,
            $base,
            Str::snake(Str::pluralStudly(Str::studly($base))),
            Str::snake(Str::pluralStudly(Str::studly($normalized))),
            Str::plural($base),
            Str::singular($base),
        ]));

        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function syncModuleFixedAssignmentsFromDb(string $module): ?string
    {
        $table = $this->resolveModuleSourceTable($module);
        if (!$table) {
            return null;
        }

        $columns = array_values(array_filter(
            Schema::getColumnListing($table),
            fn ($col) => !in_array($col, $this->defaultExcludedDbFields, true)
        ));

        foreach ($columns as $index => $column) {
            $assignment = ModuleFieldCategoryAssignment::firstOrCreate(
                ['module_key' => $module, 'field_key' => $column],
                [
                    'field_label' => ucwords(str_replace('_', ' ', $column)),
                    'display_label' => null,
                    'is_visible' => true,
                    'is_required' => false,
                    'display_order' => $index + 1,
                ]
            );

            if (!$assignment->wasRecentlyCreated && empty($assignment->field_label)) {
                $assignment->field_label = ucwords(str_replace('_', ' ', $column));
                $assignment->save();
            }
        }

        return $table;
    }

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show module settings (General tab only for now).
     */
    public function index(string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);

        if ($module === 'bike_list') {
            return app(\App\Http\Controllers\BikeSettingsController::class)->index();
        }

        $defaultLabels = config('menu_labels.defaults', []);
        $defaultLabel = $defaultLabels[$module] ?? ucwords(str_replace('_', ' ', $module));
        $moduleLabel = Settings::getMenuLabel($module);
        $pageTitle = $moduleLabel . ' – Settings';
        $companyId = optional(auth()->user())->company_id;
        $moduleSourceTable = $this->syncModuleFixedAssignmentsFromDb($module);

        $categories = ModuleSettingCategory::query()
            ->where('module_key', $module)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id');
                if ($companyId) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $fixedAssignments = ModuleFieldCategoryAssignment::with('category')
            ->where('module_key', $module)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $customFields = ModuleCustomField::with('category')
            ->where('module_key', $module)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id');
                if ($companyId) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $documentTypes = ModuleDocumentType::query()
            ->where('module_key', $module)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id');
                if ($companyId) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return view('settings.bike_settings.index', [
            'moduleKey' => $module,
            'moduleLabel' => $moduleLabel,
            'defaultLabel' => $defaultLabel,
            'pageTitle' => $pageTitle,
            'categories' => $categories,
            'fixedAssignments' => $fixedAssignments,
            'fixedAssignmentsByCategory' => $fixedAssignments->groupBy('category_id'),
            'customFields' => $customFields,
            'customFieldsByCategory' => $customFields->groupBy('category_id'),
            'unassignedCustomFields' => $customFields->whereNull('category_id')->values(),
            'dataTypes' => ModuleCustomField::dataTypes(),
            'documentTypes' => $documentTypes,
            'settingsRoutePrefix' => 'settings-panel.module-settings',
            'settingsRouteParams' => ['company_slug' => $company_slug, 'module' => $module],
            'settingsHeading' => $moduleLabel . ' Settings',
            'settingsFieldsTabLabel' => 'Module Fields',
            'settingsEntityName' => strtolower($moduleLabel),
            'fixedFieldSourceTable' => $moduleSourceTable ?: 'module_field_category_assignments',
            'customFieldSourceTable' => 'module_custom_fields',
        ]);
    }

    /**
     * Save the module display name (menu label).
     * This value is used by the main app sidebar (resources/views/layouts/menu.blade.php)
     * via Settings::getMenuLabels(), so the menu updates on the next page load.
     */
    public function storeModuleLabel(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $allowedLabels = config('menu_labels.defaults', []);
        if (!isset($allowedLabels[$module])) {
            return back()->with('error', __('Invalid module key.'));
        }
        $request->validate(['module_label' => 'required|string|max:100']);
        Settings::updateOrCreate(
            ['name' => 'menu_label_' . $module],
            ['value' => trim($request->input('module_label'))]
        );
        Settings::clearMenuLabelsCache();

        return redirect()
            ->route('settings-panel.module-settings.index', [
                'company_slug' => $company_slug,
                'module' => $module,
            ])
            ->with('success', 'Module name updated.');
    }

    public function storeCategory(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate(['label' => 'required|string|max:255']);

        ModuleSettingCategory::create([
            'module_key' => $module,
            'company_id' => optional(auth()->user())->company_id,
            'label' => $validated['label'],
            'display_order' => ((int) ModuleSettingCategory::where('module_key', $module)->max('display_order')) + 1,
            'is_system' => false,
        ]);

        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        $category = ModuleSettingCategory::where('module_key', $module)->where('id', $id)->firstOrFail();
        $validated = $request->validate(['label' => 'required|string|max:255']);
        $category->update(['label' => $validated['label']]);

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        $category = ModuleSettingCategory::where('module_key', $module)->where('id', $id)->firstOrFail();
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function storeFieldAssignment(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'field_key' => 'required|string|max:120',
            'field_label' => 'nullable|string|max:255',
            'category_id' => ['nullable', 'integer', $this->categoryBelongsToModuleRule($module)],
            'display_label' => 'nullable|string|max:255',
            'is_visible' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
        ]);

        $assignment = ModuleFieldCategoryAssignment::updateOrCreate(
            ['module_key' => $module, 'field_key' => trim((string) $validated['field_key'])],
            [
                'field_label' => $validated['field_label'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'display_label' => $validated['display_label'] ?? null,
                'is_visible' => filter_var((string) ($validated['is_visible'] ?? false), FILTER_VALIDATE_BOOLEAN),
                'is_required' => filter_var((string) ($validated['is_required'] ?? false), FILTER_VALIDATE_BOOLEAN),
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Field assignment saved.',
                'field_key' => $assignment->field_key,
                'is_visible' => (bool) $assignment->is_visible,
                'is_required' => (bool) $assignment->is_required,
            ]);
        }

        return back()->with('success', 'Field assignment saved.');
    }

    public function reorderFieldAssignments(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'category_id' => ['required', 'integer', $this->categoryBelongsToModuleRule($module)],
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string', 'max:120'],
        ]);

        $categoryId = (int) $validated['category_id'];
        $order = array_values(array_unique(array_map('strval', $validated['order'])));

        $allowedKeys = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $module)
            ->where('category_id', $categoryId)
            ->pluck('field_key')
            ->map(fn ($v) => (string) $v)
            ->all();

        $position = 0;
        foreach ($order as $fieldKey) {
            if (!in_array($fieldKey, $allowedKeys, true)) {
                continue;
            }
            ModuleFieldCategoryAssignment::where('module_key', $module)
                ->where('category_id', $categoryId)
                ->where('field_key', $fieldKey)
                ->update(['display_order' => $position++]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function storeField(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $allowedTypes = array_keys(ModuleCustomField::dataTypes());
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => ['required', Rule::in($allowedTypes)],
            'is_mandatory' => 'nullable|boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'category_id' => ['nullable', 'integer', $this->categoryBelongsToModuleRule($module)],
            'config_options' => 'nullable|string',
        ]);

        $config = null;
        if (!empty($validated['config_options']) && $validated['data_type'] === 'dropdown') {
            $config = ['options' => $validated['config_options']];
        }

        ModuleCustomField::create([
            'module_key' => $module,
            'company_id' => optional(auth()->user())->company_id,
            'category_id' => $validated['category_id'] ?? null,
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'data_type' => $validated['data_type'],
            'is_mandatory' => filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'default_value' => $validated['default_value'] ?? null,
            'input_format' => $validated['input_format'] ?? null,
            'config' => $config,
            'display_order' => ((int) ModuleCustomField::where('module_key', $module)->max('display_order')) + 1,
        ]);

        return back()->with('success', 'Custom field added.');
    }

    public function updateField(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        $field = ModuleCustomField::where('module_key', $module)->where('id', $id)->firstOrFail();
        $allowedTypes = array_keys(ModuleCustomField::dataTypes());
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => ['required', Rule::in($allowedTypes)],
            'is_mandatory' => 'nullable|boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'category_id' => ['nullable', 'integer', $this->categoryBelongsToModuleRule($module)],
            'config_options' => 'nullable|string',
        ]);

        $config = null;
        if (!empty($validated['config_options']) && $validated['data_type'] === 'dropdown') {
            $config = ['options' => $validated['config_options']];
        }

        $field->update([
            'category_id' => $validated['category_id'] ?? null,
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'data_type' => $validated['data_type'],
            'is_mandatory' => filter_var((string) ($validated['is_mandatory'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'default_value' => $validated['default_value'] ?? null,
            'input_format' => $validated['input_format'] ?? null,
            'config' => $config,
        ]);

        return back()->with('success', 'Custom field updated.');
    }

    public function assignCustomFieldCategory(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'category_id' => ['required', 'integer', $this->categoryBelongsToModuleRule($module)],
        ]);

        $field = ModuleCustomField::where('module_key', $module)->where('id', $id)->firstOrFail();
        $field->category_id = (int) $validated['category_id'];
        $field->save();

        return back()->with('success', 'Custom field moved.');
    }

    public function destroyField(string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        ModuleCustomField::where('module_key', $module)->where('id', $id)->delete();

        return back()->with('success', 'Custom field deleted.');
    }

    public function reorderFields(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', $this->categoryBelongsToModuleRule($module)],
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:module_custom_fields,id'],
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $order = array_values(array_unique(array_map('intval', $validated['order'])));

        $query = ModuleCustomField::query()
            ->where('module_key', $module)
            ->whereIn('id', $order);
        if ($categoryId === null) {
            $query->whereNull('category_id');
        } else {
            $query->where('category_id', (int) $categoryId);
        }
        $allowedIds = $query->pluck('id')->map(fn ($v) => (int) $v)->all();

        $position = 0;
        foreach ($order as $id) {
            if (!in_array($id, $allowedIds, true)) {
                continue;
            }
            ModuleCustomField::where('module_key', $module)
                ->where('id', $id)
                ->update(['display_order' => $position++]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function storeDocumentType(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:80', Rule::unique('module_document_types', 'key')->where(fn ($q) => $q->where('module_key', $module))],
            'label' => 'nullable|string|max:255',
            'type' => ['required', Rule::in(['single', 'dual'])],
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $documentType = ModuleDocumentType::create([
            'module_key' => $module,
            'company_id' => optional(auth()->user())->company_id,
            'key' => trim((string) $validated['key']),
            'label' => $validated['label'] ?? null,
            'type' => $validated['type'],
            'front_label' => $validated['front_label'] ?? null,
            'back_label' => $validated['back_label'] ?? null,
            'display_order' => ((int) ModuleDocumentType::where('module_key', $module)->max('display_order')) + 1,
            'is_active' => filter_var((string) ($validated['is_active'] ?? true), FILTER_VALIDATE_BOOLEAN),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document type added.',
                'id' => $documentType->id,
            ]);
        }

        return back()->with('success', 'Document type added.');
    }

    public function updateDocumentType(Request $request, string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        $document = ModuleDocumentType::where('module_key', $module)->where('id', $id)->firstOrFail();
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:80', Rule::unique('module_document_types', 'key')->ignore($id)->where(fn ($q) => $q->where('module_key', $module))],
            'label' => 'nullable|string|max:255',
            'type' => ['required', Rule::in(['single', 'dual'])],
            'front_label' => 'nullable|string|max:255',
            'back_label' => 'nullable|string|max:255',
        ]);

        $document->update([
            'key' => trim((string) $validated['key']),
            'label' => $validated['label'] ?? null,
            'type' => $validated['type'],
            'front_label' => $validated['front_label'] ?? null,
            'back_label' => $validated['back_label'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Document type updated.');
    }

    public function destroyDocumentType(string $company_slug, string $module, int $id)
    {
        $module = $this->normalizeModuleKey($module);
        ModuleDocumentType::where('module_key', $module)->where('id', $id)->delete();

        return back()->with('success', 'Document type deleted.');
    }
}
