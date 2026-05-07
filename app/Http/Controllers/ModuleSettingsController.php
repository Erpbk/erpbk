<?php

namespace App\Http\Controllers;

use App\Models\Accounts;
use App\Models\ModuleCustomField;
use App\Models\ModuleDocumentType;
use App\Models\ModuleFieldCategoryAssignment;
use App\Models\ModuleSettingCategory;
use App\Models\RiderInvoiceAccountAssignment;
use App\Models\Settings;
use App\Models\VisaStatus;
use App\Support\ModuleFieldSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ModuleSettingsController extends Controller
{
    /**
     * Invoice-like modules that require account assigning setup.
     *
     * @return list<string>
     */
    protected function accountAssignableInvoiceModules(): array
    {
        return ['invoices', 'customer_invoices'];
    }

    protected function isAccountAssignableInvoiceModule(string $module): bool
    {
        return in_array($module, $this->accountAssignableInvoiceModules(), true);
    }

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

    protected function resolveModuleSourceTable(string $module): ?string
    {
        return ModuleFieldSource::resolveSourceTable($module);
    }

    protected function syncModuleFixedAssignmentsFromDb(string $module): ?string
    {
        $table = $this->resolveModuleSourceTable($module);
        if (!$table) {
            return null;
        }

        $excluded = ModuleFieldSource::defaultExcludedFieldsForModule($module);
        $columns = array_values(array_filter(
            Schema::getColumnListing($table),
            fn ($col) => !in_array($col, $excluded, true)
        ));

        foreach ($columns as $index => $column) {
            $assignment = ModuleFieldCategoryAssignment::firstOrCreate(
                ['module_key' => $module, 'field_key' => $column],
                [
                    'field_label' => ucwords(str_replace('_', ' ', $column)),
                    'display_label' => null,
                    'is_visible' => true,
                    'is_required' => true,
                    'display_order' => $index + 1,
                ]
            );

            $dirty = false;
            if (!$assignment->wasRecentlyCreated && empty($assignment->field_label)) {
                $assignment->field_label = ucwords(str_replace('_', ' ', $column));
                $dirty = true;
            }
            if ($dirty) {
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
        $hiddenFieldKeys = array_flip(ModuleFieldSource::defaultExcludedFieldsForModule($module));
        $fixedAssignments = $fixedAssignments
            ->filter(fn ($row) => !isset($hiddenFieldKeys[(string) $row->field_key]))
            ->values();

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

        $riderInvoiceAccountTree = [];
        $riderInvoiceAssignments = ['debit' => [], 'credit' => []];
        if ($this->isAccountAssignableInvoiceModule($module)) {
            // Eligible parents: shared chart heads (roots), company-created accounts, and globally fixed accounts.
            $riderInvoiceParentAccounts = Accounts::query()
                ->where(function ($query) use ($companyId): void {
                    $query->where(function ($q): void {
                        $q->whereNull('parent_id')->orWhere('parent_id', 0);
                    });
                    if ($companyId) {
                        $query->orWhere(function ($q) use ($companyId): void {
                            $q->where('company_id', $companyId)
                                ->whereNotNull('parent_id')
                                ->where('parent_id', '!=', 0);
                        });
                    }
                    $query->orWhere('is_fixed', true);
                })
                ->orderBy('account_code')
                ->get(['id', 'name', 'account_code', 'parent_id']);

            $parentIds = $riderInvoiceParentAccounts->pluck('id')->all();
            if ($parentIds !== []) {
                $childrenByParent = Accounts::query()
                    ->whereIn('parent_id', $parentIds)
                    ->orderBy('account_code')
                    ->get(['id', 'name', 'account_code', 'parent_id'])
                    ->groupBy('parent_id');

                foreach ($riderInvoiceParentAccounts as $parentAccount) {
                    $kids = $childrenByParent->get($parentAccount->id, collect());
                    $riderInvoiceAccountTree[] = [
                        'parent_id' => (int) $parentAccount->id,
                        'label' => $this->formatAccountPickerLabel($parentAccount),
                        'children' => $kids->map(fn ($row) => [
                            'id' => (int) $row->id,
                            'text' => trim((string) (($row->account_code ? $row->account_code . ' — ' : '') . $row->name)),
                        ])->values()->all(),
                    ];
                }
                usort($riderInvoiceAccountTree, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));
            }

            $assignments = RiderInvoiceAccountAssignment::query()
                ->where('company_id', (int) $companyId)
                ->where('module_key', $module)
                ->orderBy('side')
                ->orderBy('parent_account_id')
                ->orderBy('child_account_id')
                ->get();

            $riderInvoiceAssignments = [
                'debit' => $assignments->where('side', 'debit')
                    ->groupBy('parent_account_id')
                    ->map(fn ($rows) => $rows->pluck('child_account_id')->map(fn ($id) => (int) $id)->values()->all())
                    ->toArray(),
                'credit' => $assignments->where('side', 'credit')
                    ->groupBy('parent_account_id')
                    ->map(fn ($rows) => $rows->pluck('child_account_id')->map(fn ($id) => (int) $id)->values()->all())
                    ->toArray(),
            ];
        }
        $visaStatuses = collect();
        if ($module === 'visa_expense') {
            $visaStatuses = VisaStatus::query()
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();
        }

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
            'riderInvoiceAccountTree' => $riderInvoiceAccountTree,
            'riderInvoiceAssignments' => $riderInvoiceAssignments,
            'moduleSchemaFieldKeys' => ModuleFieldSource::schemaFieldKeysForModule($module),
            'visaStatuses' => $visaStatuses,
        ]);
    }

    protected function ensureCompanyAdmin(): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isCompanyAdmin = $user
            && (
                $user->hasRole('admin')
                || $user->hasRole('Administrator')
                || $user->hasRole('Super Admin')
            );
        abort_unless(
            $isCompanyAdmin,
            403,
            'Only company admin can manage account assigning.'
        );
    }

    protected function parseAssignmentsPayload(mixed $payload): array
    {
        $decoded = $payload;
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $parentId => $childIds) {
            $parentIdInt = (int) $parentId;
            if ($parentIdInt <= 0 || !is_array($childIds)) {
                continue;
            }

            $children = collect($childIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (!empty($children)) {
                $normalized[$parentIdInt] = $children;
            }
        }

        return $normalized;
    }

    public function riderInvoiceAccountChildren(Request $request, string $company_slug, string $module): JsonResponse
    {
        $module = $this->normalizeModuleKey($module);
        abort_unless($this->isAccountAssignableInvoiceModule($module), 404);
        $this->ensureCompanyAdmin();

        $validated = $request->validate([
            'parent_id' => ['required', 'integer', 'min:1'],
        ]);

        $parentId = (int) $validated['parent_id'];
        $parentAccount = Accounts::query()->find($parentId);

        if (!$parentAccount) {
            return response()->json(['success' => false, 'message' => 'Invalid parent account.'], 422);
        }

        $parentBucketLine = __('Belongs under') . ': ' . $this->formatAccountPickerLabel($parentAccount);

        $children = Accounts::query()
            ->where('parent_id', $parentId)
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'text' => trim((string) ($row->account_code ? $row->account_code . ' — ' : '') . $row->name),
                'parent_context' => $parentBucketLine,
            ])
            ->values();

        return response()->json(['success' => true, 'children' => $children]);
    }

    public function storeRiderInvoiceAccountAssigning(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        abort_unless($this->isAccountAssignableInvoiceModule($module), 404);
        $this->ensureCompanyAdmin();

        $companyId = (int) (optional(auth()->user())->company_id ?? 0);
        abort_if($companyId <= 0, 422, 'Company context is required.');

        $debit = $this->parseAssignmentsPayload($request->input('debit_assignments'));
        $credit = $this->parseAssignmentsPayload($request->input('credit_assignments'));

        if (empty($debit) || empty($credit)) {
            return back()->with('error', 'Select at least one debit and one credit account assignment.');
        }

        $validateSide = function (array $sideAssignments, string $sideLabel): ?string {
            $childToParent = [];
            foreach ($sideAssignments as $parentId => $childIds) {
                $parent = Accounts::query()->where('id', (int) $parentId)->first();

                if (!$parent) {
                    return "Invalid {$sideLabel} parent account selected.";
                }

                foreach ($childIds as $childId) {
                    $childId = (int) $childId;
                    $parentIdInt = (int) $parentId;
                    $isDirectChild = Accounts::query()
                        ->where('id', $childId)
                        ->where('parent_id', $parentIdInt)
                        ->exists();
                    $isParentSelf = $childId === $parentIdInt;

                    if (!$isDirectChild && !$isParentSelf) {
                        return "Invalid {$sideLabel} child account selected.";
                    }
                    if (isset($childToParent[$childId])) {
                        return "Duplicate {$sideLabel} child account selection is not allowed.";
                    }
                    $childToParent[$childId] = (int) $parentId;
                }
            }

            return null;
        };

        if ($error = $validateSide($debit, 'debit')) {
            return back()->with('error', $error);
        }
        if ($error = $validateSide($credit, 'credit')) {
            return back()->with('error', $error);
        }

        $rows = [];
        foreach (['debit' => $debit, 'credit' => $credit] as $side => $sideAssignments) {
            foreach ($sideAssignments as $parentId => $childIds) {
                foreach ($childIds as $childId) {
                    $rows[] = [
                        'company_id' => $companyId,
                        'module_key' => $module,
                        'side' => $side,
                        'parent_account_id' => (int) $parentId,
                        'child_account_id' => (int) $childId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        RiderInvoiceAccountAssignment::query()
            ->where('company_id', $companyId)
            ->where('module_key', $module)
            ->delete();

        RiderInvoiceAccountAssignment::query()->insert($rows);

        return back()->with('success', 'Invoice account assigning updated successfully.');
    }

    private function formatAccountPickerLabel(Accounts $account): string
    {
        return trim((string) (($account->account_code ? $account->account_code . ' — ' : '') . $account->name));
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

        $fieldKey = trim((string) $validated['field_key']);
        $isVisible = filter_var((string) ($validated['is_visible'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $isRequired = filter_var((string) ($validated['is_required'] ?? false), FILTER_VALIDATE_BOOLEAN);

        $assignment = ModuleFieldCategoryAssignment::updateOrCreate(
            ['module_key' => $module, 'field_key' => $fieldKey],
            [
                'field_label' => $validated['field_label'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'display_label' => $validated['display_label'] ?? null,
                'is_visible' => $isVisible,
                'is_required' => $isRequired,
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

        foreach ($order as $fieldKey) {
            if (!in_array($fieldKey, $allowedKeys, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
            }
        }
        if (count($order) !== count($allowedKeys)) {
            return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
        }

        $this->mergeModuleFixedFieldOrderForCategory($module, $categoryId, $order);

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Full drag order for the "All Fields" tab (global display_order across all fixed assignments).
     */
    public function reorderAllFieldAssignments(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string', 'max:120'],
        ]);
        $order = array_values(array_map('strval', $validated['order']));
        $existing = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $module)
            ->pluck('field_key')
            ->map(fn ($v) => (string) $v)
            ->sort()
            ->values()
            ->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $existing || count($order) !== count($existing)) {
            return response()->json(['success' => false, 'message' => 'Invalid field order.'], 422);
        }

        foreach ($order as $pos => $fieldKey) {
            ModuleFieldCategoryAssignment::query()
                ->where('module_key', $module)
                ->where('field_key', $fieldKey)
                ->update(['display_order' => $pos]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Replace one category's keys in the global order with a new permutation; renumber display_order for all rows.
     *
     * @param  list<string>  $orderedKeysInCategory
     */
    protected function mergeModuleFixedFieldOrderForCategory(string $module, int $categoryId, array $orderedKeysInCategory): void
    {
        $rows = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $module)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $globalKeys = $rows->pluck('field_key')->map(fn ($v) => (string) $v)->all();

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
            ModuleFieldCategoryAssignment::query()
                ->where('module_key', $module)
                ->where('field_key', $fieldKey)
                ->update(['display_order' => $pos]);
        }
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
        $allowedIds = $query->pluck('id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $allowedIds || count($order) !== count($allowedIds)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        $this->mergeModuleCustomFieldOrderForCategory($module, $categoryId, $order);

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Full drag order for custom fields on the "All Fields" tab.
     */
    public function reorderAllCustomFields(Request $request, string $company_slug, string $module)
    {
        $module = $this->normalizeModuleKey($module);
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:module_custom_fields,id'],
        ]);
        $order = array_values(array_map('intval', $validated['order']));
        $existing = ModuleCustomField::query()
            ->where('module_key', $module)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values()
            ->all();
        $sortedOrder = $order;
        sort($sortedOrder);
        if ($sortedOrder !== $existing || count($order) !== count($existing)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $pos => $id) {
            ModuleCustomField::query()
                ->where('module_key', $module)
                ->where('id', $id)
                ->update(['display_order' => $pos]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * @param  list<int>  $orderedIdsInCategory
     */
    protected function mergeModuleCustomFieldOrderForCategory(string $module, ?int $categoryId, array $orderedIdsInCategory): void
    {
        $rows = ModuleCustomField::query()
            ->where('module_key', $module)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $inCategory = function ($row) use ($categoryId) {
            if ($categoryId === null) {
                return $row->category_id === null;
            }

            return (int) $row->category_id === (int) $categoryId;
        };

        $globalIds = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
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
            ModuleCustomField::query()
                ->where('module_key', $module)
                ->where('id', $fid)
                ->update(['display_order' => $pos]);
        }
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
