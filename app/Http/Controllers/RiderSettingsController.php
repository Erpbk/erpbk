<?php

namespace App\Http\Controllers;

use App\Models\RiderCategory;
use App\Models\RiderCustomField;
use App\Models\RiderDocumentType;
use App\Models\RiderFieldCategoryAssignment;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RiderSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Rider Settings: categories, fixed rider fields + rider custom fields, organized by category.
     */
    public function index()
    {
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
        $fixedFieldsByCategory = RiderCustomField::fixedRiderFieldsByCategory();
        $customFields = RiderCustomField::with('category')->orderBy('display_order')->orderBy('id')->get();
        $customFieldsByCategory = $customFields->groupBy('category_id');
        $dataTypes = RiderCustomField::dataTypes();
        $moduleLabel = Settings::getMenuLabel('rider_settings');
        $fieldAssignments = $this->buildFieldAssignmentsList($categories);
        $fieldsByCategory = $this->buildFieldsByCategory($categories);
        $documentTypes = RiderDocumentType::orderedForAdmin()->get();

        return view('settings.rider_settings.index', compact(
            'categories',
            'fixedFieldsByCategory',
            'customFields',
            'customFieldsByCategory',
            'dataTypes',
            'moduleLabel',
            'fieldAssignments',
            'fieldsByCategory',
            'documentTypes'
        ));
    }

    /**
     * Build fields grouped by category for Rider Fields sub-tabs (with display_order).
     */
    protected function buildFieldsByCategory($categories)
    {
        $assignments = RiderFieldCategoryAssignment::with('category')
            ->orderBy('category_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $grouped = $assignments->groupBy('category_id');
        $result = [];
        foreach ($categories as $cat) {
            $items = $grouped->get($cat->id, collect())->map(function ($a) {
                $rawVisible = $a->getRawOriginal('is_visible');
                $isVisible = $rawVisible === null ? true : (bool) (int) $rawVisible;
                return (object) [
                    'field_key' => $a->field_key,
                    'label' => $a->display_label !== null && trim((string) $a->display_label) !== ''
                        ? trim($a->display_label)
                        : RiderCustomField::humanizeFieldKey($a->field_key),
                    'display_order' => $a->display_order,
                    'is_visible' => $isVisible,
                ];
            })->values()->all();
            $result[] = (object) [
                'category' => $cat,
                'fields' => $items,
            ];
        }
        return $result;
    }

    /**
     * Build list of all fixed rider fields with their current category assignment (for Rider Fields tab).
     */
    protected function buildFieldAssignmentsList($categories)
    {
        $keys = RiderCustomField::allFixedFieldKeys();
        $assignments = RiderFieldCategoryAssignment::all()->keyBy('field_key');
        $slugToId = RiderCategory::whereNotNull('slug')->pluck('id', 'slug')->all();
        $map = RiderCustomField::fixedFieldsSlugMap();
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
                'label' => RiderCustomField::humanizeFieldKey($fieldKey),
                'category_id' => $a ? $a->category_id : $defaultCategoryId,
            ];
        }
        return $list;
    }

    /**
     * Update which category a fixed rider field is assigned to.
     */
    public function updateFieldAssignment(Request $request)
    {
        $validated = $request->validate([
            'field_key' => 'required|string|max:80',
            'category_id' => 'required|integer|exists:rider_categories,id',
            'display_label' => 'nullable|string|max:255',
        ]);
        $keys = RiderCustomField::allFixedFieldKeys();
        if (!in_array($validated['field_key'], $keys, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field.'], 422);
        }
        $assignment = RiderFieldCategoryAssignment::firstOrNew(['field_key' => $validated['field_key']]);
        $newCategoryId = (int) $validated['category_id'];
        $assignment->category_id = $newCategoryId;
        if (!$assignment->exists || (int) $assignment->getOriginal('category_id') !== $newCategoryId) {
            $assignment->display_order = (int) RiderFieldCategoryAssignment::where('category_id', $newCategoryId)->max('display_order') + 1;
        }
        if (array_key_exists('display_label', $validated)) {
            $assignment->display_label = $validated['display_label'] ? trim($validated['display_label']) : null;
        }
        $assignment->save();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated.']);
        }
        return redirect()->route('settings-panel.rider-settings.index', ['tab' => 'rider-fields'])->with('success', 'Category updated.');
    }

    /**
     * Update only the display label for a fixed rider field assignment.
     */
    public function updateFieldAssignmentLabel(Request $request)
    {
        $validated = $request->validate([
            'field_key' => 'required|string|max:80',
            'display_label' => 'nullable|string|max:255',
        ]);
        $keys = RiderCustomField::allFixedFieldKeys();
        if (!in_array($validated['field_key'], $keys, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid field.'], 422);
        }
        $assignment = RiderFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        $assignment->display_label = isset($validated['display_label']) && trim((string) $validated['display_label']) !== ''
            ? trim($validated['display_label'])
            : null;
        $assignment->save();
        return response()->json(['success' => true, 'message' => 'Label updated.', 'label' => $assignment->display_label ?? RiderCustomField::humanizeFieldKey($assignment->field_key)]);
    }

    /**
     * Toggle visibility of a fixed field in the Rider module (Add/Edit/View).
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
            
            $keys = RiderCustomField::allFixedFieldKeys();
            if (!in_array($validated['field_key'], $keys, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid field key: ' . $validated['field_key']], 422);
            }
            
            $table = (new RiderFieldCategoryAssignment)->getTable();
            if (!Schema::hasColumn($table, 'is_visible')) {
                return response()->json(['success' => false, 'message' => 'Database migration required. Run: php artisan migrate'], 500);
            }
            
            $assignment = RiderFieldCategoryAssignment::where('field_key', $validated['field_key'])->first();
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found for field: ' . $validated['field_key']], 404);
            }
            
            $value = $isVisible ? 1 : 0;
            $assignment->is_visible = $value;
            $assignment->save();
            
            return response()->json([
                'success' => true,
                'message' => $isVisible ? 'Field will show in Rider module.' : 'Field hidden from Rider module.',
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
     * Reorder fields within a category (drag-and-drop).
     */
    public function reorderFieldAssignments(Request $request)
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $validated = validator($payload, [
            'category_id' => 'required|integer|exists:rider_categories,id',
            'order' => 'required|array',
            'order.*' => 'string|max:80',
        ])->validate();
        $categoryId = (int) $validated['category_id'];
        foreach ($validated['order'] as $position => $fieldKey) {
            RiderFieldCategoryAssignment::where('field_key', $fieldKey)->where('category_id', $categoryId)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Save the display name for this module (settings panel + main app menu use key 'riders').
     */
    public function storeModuleLabel(Request $request)
    {
        $request->validate(['module_label' => 'required|string|max:100']);
        $value = trim($request->input('module_label'));
        Settings::updateOrCreate(['name' => 'menu_label_rider_settings'], ['value' => $value]);
        Settings::updateOrCreate(['name' => 'menu_label_riders'], ['value' => $value]);
        Settings::clearMenuLabelsCache();
        return redirect()->route('settings-panel.rider-settings.index')->with('success', 'Module name updated.');
    }

    // ---------- Rider Categories ----------

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

        $validated['display_order'] = (int) RiderCategory::max('display_order') + 1;
        $validated['is_system'] = false;
        $validated['slug'] = null; // User-created categories have no slug

        RiderCategory::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category added successfully.']);
        }
        return redirect()->route('settings-panel.rider-settings.index')->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = RiderCategory::findOrFail($id);
        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);
        $category->label = $validated['label'];
        $category->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }
        return redirect()->route('settings-panel.rider-settings.index')->with('success', 'Category updated successfully.');
    }

    public function destroyCategory($id)
    {
        $category = RiderCategory::findOrFail($id);
        if ($category->is_system) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'System categories cannot be deleted.'], 422);
            }
            return redirect()->route('settings-panel.rider-settings.index')
                ->with('error', 'System categories cannot be deleted.');
        }
        if ($category->customFields()->exists()) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a category that has custom fields. Move or delete the fields first.'], 422);
            }
            return redirect()->route('settings-panel.rider-settings.index')
                ->with('error', 'Cannot delete a category that has custom fields. Move or delete the fields first.');
        }
        $category->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Category deleted.']);
        }
        return redirect()->route('settings-panel.rider-settings.index')->with('success', 'Category deleted.');
    }

    public function reorderCategories(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:rider_categories,id',
        ]);
        foreach ($request->input('order') as $position => $id) {
            RiderCategory::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function categoriesTableBody()
    {
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();
        return view('settings.rider_settings._categories_tbody', compact('categories'));
    }

    /**
     * Store a new rider custom field under a specific category.
     */
    public function storeField(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(RiderCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
            'category_id' => 'required|integer|exists:rider_categories,id',
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['prevent_duplicate_values'] = $request->boolean('prevent_duplicate_values');
        $validated['help_text'] = $request->input('help_text');
        $validated['default_value'] = $request->input('default_value');
        $validated['input_format'] = $request->input('input_format');
        $validated['category_id'] = (int) $request->input('category_id');
        $validated['data_privacy'] = [
            'pii' => $request->boolean('data_privacy_pii'),
            'ephi' => $request->boolean('data_privacy_ephi'),
        ];
        $config = $request->input('config');
        $validated['config'] = is_string($config) ? (json_decode($config, true) ?? []) : (is_array($config) ? $config : []);
        $validated['display_order'] = (int) RiderCustomField::where('category_id', $validated['category_id'])->max('display_order') + 1;

        RiderCustomField::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field added successfully.']);
        }

        return redirect()
            ->route('settings-panel.rider-settings.index')
            ->with('success', 'Custom field added successfully.');
    }

    /**
     * Update an existing rider custom field.
     */
    public function updateField(Request $request, $id)
    {
        $field = RiderCustomField::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'help_text' => 'nullable|string|max:1000',
            'data_type' => 'required|string|in:' . implode(',', array_keys(RiderCustomField::dataTypes())),
            'is_mandatory' => 'boolean',
            'prevent_duplicate_values' => 'boolean',
            'default_value' => 'nullable|string|max:500',
            'input_format' => 'nullable|string|max:100',
            'config' => 'nullable',
            'category_id' => 'required|integer|exists:rider_categories,id',
        ]);

        $field->label = $validated['label'];
        $field->help_text = $request->input('help_text');
        $field->data_type = $validated['data_type'];
        $field->is_mandatory = $request->boolean('is_mandatory');
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
            ->route('settings-panel.rider-settings.index')
            ->with('success', 'Custom field updated successfully.');
    }

    /**
     * Delete a rider custom field.
     */
    public function destroyField($id)
    {
        $field = RiderCustomField::findOrFail($id);
        $field->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Custom field deleted.']);
        }

        return redirect()
            ->route('settings-panel.rider-settings.index')
            ->with('success', 'Custom field deleted.');
    }

    /**
     * Reorder rider custom fields (display_order) within a category.
     */
    public function reorderFields(Request $request)
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $validated = validator($payload, [
            'order' => 'required|array',
            'order.*' => 'integer|exists:rider_custom_fields,id',
            'category_id' => 'nullable|integer|exists:rider_categories,id',
        ])->validate();

        $order = $validated['order'];
        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId) {
            $ids = RiderCustomField::where('category_id', $categoryId)->pluck('id')->all();
            $order = array_values(array_intersect($order, $ids));
        }

        foreach ($order as $position => $id) {
            RiderCustomField::where('id', $id)->update(['display_order' => $position]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /**
     * Return config schema for a data type (for dynamic form).
     */
    public function fieldConfigSchema($dataType)
    {
        $types = RiderCustomField::dataTypes();
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
        $customFields = RiderCustomField::with('category')->orderBy('display_order')->orderBy('id')->get();
        $dataTypes = RiderCustomField::dataTypes();
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();

        return view('settings.rider_settings._custom_fields_tbody', compact('customFields', 'dataTypes', 'categories'));
    }

    /**
     * Return custom fields rows for one category (for AJAX refresh inside a category tab).
     */
    public function tableBodyCategory($categoryId)
    {
        $category = RiderCategory::findOrFail($categoryId);
        $customFields = RiderCustomField::where('category_id', $categoryId)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $dataTypes = RiderCustomField::dataTypes();
        $categories = RiderCategory::orderBy('display_order')->orderBy('id')->get();

        return view('settings.rider_settings._custom_fields_rows_category', compact('customFields', 'dataTypes', 'categories'));
    }

    // ---------- Rider Documents ----------

    /**
     * Return document types table body (for AJAX refresh).
     */
    public function documentTypesTableBody()
    {
        $documentTypes = RiderDocumentType::orderedForAdmin()->get();
        return view('settings.rider_settings._document_types_tbody', compact('documentTypes'));
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:80|regex:/^[a-z0-9_]+$/|unique:rider_document_types,key',
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
        $maxOrder = (int) RiderDocumentType::max('display_order');
        RiderDocumentType::create([
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

    public function updateDocumentType(Request $request, $id)
    {
        $docType = RiderDocumentType::findOrFail($id);
        $validated = $request->validate([
            'key' => 'required|string|max:80|regex:/^[a-z0-9_]+$/|unique:rider_document_types,key,' . $id,
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

    public function destroyDocumentType($id)
    {
        RiderDocumentType::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Document type deleted.']);
    }

    public function reorderDocumentTypes(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:rider_document_types,id']);
        foreach ($request->input('order') as $position => $id) {
            RiderDocumentType::where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true]);
    }
}
