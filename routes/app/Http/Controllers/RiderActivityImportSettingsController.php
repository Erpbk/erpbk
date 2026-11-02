<?php

namespace App\Http\Controllers;

use App\Exports\RiderActivityImportTemplateExport;
use App\Models\Customers;
use App\Models\RiderActivityImportSetting;
use App\Services\RiderActivities\RiderActivityImportMappingService;
use App\Support\CompanyContext;
use Flash;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RiderActivityImportSettingsController extends Controller
{
    public function __construct(
        private readonly RiderActivityImportMappingService $mappingService
    ) {
        $this->middleware('auth');
    }

    protected function authorizeManage(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->can('gn_settings') && !$user->can('rider_view'))) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeManage();

        $importType = RiderActivityImportMappingService::normalizeImportType($request->get('import_type'));
        $customers = Customers::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedCustomerId = (int) ($request->get('customer_id') ?: RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID);
        if ($customers->isNotEmpty() && !$customers->contains('id', $selectedCustomerId)) {
            $selectedCustomerId = (int) $customers->first()->id;
        }

        $resolved = $this->mappingService->resolve($selectedCustomerId, $importType);
        $stored = RiderActivityImportSetting::query()
            ->where('customer_id', $selectedCustomerId)
            ->where('import_type', $importType)
            ->first();

        $configuredCustomerIds = RiderActivityImportSetting::query()
            ->where('import_type', $importType)
            ->where('is_active', true)
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $maxMappedIndex = 25;
        foreach ($resolved['column_mappings'] as $index) {
            $maxMappedIndex = max($maxMappedIndex, (int) $index);
        }

        $staticRequired = RiderActivityImportMappingService::requiredFields();
        $storedRequired = $stored?->required_fields;
        $requiredFields = is_array($storedRequired) && count($storedRequired)
            ? array_merge(array_fill_keys(array_keys($staticRequired), false), $storedRequired)
            : $staticRequired;

        $fieldLabels = RiderActivityImportMappingService::fieldLabels();
        $orderedFieldKeys = $resolved['ordered_field_keys']
            ?? array_keys($resolved['column_mappings']);
        $orderedFieldKeys = array_values(array_filter(
            $orderedFieldKeys,
            static fn ($key) => array_key_exists($key, $fieldLabels)
        ));
        if ($orderedFieldKeys === []) {
            $orderedFieldKeys = array_keys($fieldLabels);
        }

        return view('settings.rider_activity_import.index', [
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
            'importType' => $importType,
            'importTypeLabels' => RiderActivityImportMappingService::importTypeLabels(),
            'headerRowsToSkip' => $resolved['header_rows_to_skip'],
            'columnMappings' => $resolved['column_mappings'],
            'orderedFieldKeys' => $orderedFieldKeys,
            'fieldLabels' => $fieldLabels,
            'requiredFields' => $requiredFields,
            'defaultMappings' => RiderActivityImportMappingService::defaultColumnMappings($importType),
            'defaultCustomerId' => RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID,
            'storedSetting' => $stored,
            'configuredCustomerIds' => $configuredCustomerIds,
            'excelColumnChoices' => RiderActivityImportMappingService::excelColumnChoices($maxMappedIndex),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $importType = RiderActivityImportMappingService::normalizeImportType($request->input('import_type'));

        $allowedFields = array_keys(RiderActivityImportMappingService::fieldLabels());
        $rules = [
            'customer_id' => 'required|integer|exists:customers,id',
            'import_type' => 'required|in:rider,live',
            'header_rows_to_skip' => 'required|integer|min:0|max:20',
            'column_mappings' => 'required|array|min:2',
            'field_order' => 'sometimes|array',
            'field_order.*' => 'string',
            'required_fields' => 'sometimes|array',
            'required_fields.*' => 'string',
            'is_active' => 'sometimes|boolean',
        ];

        foreach ($allowedFields as $fieldKey) {
            $isAlwaysRequired = in_array(
                $fieldKey,
                RiderActivityImportMappingService::alwaysRequiredFieldKeys(),
                true
            );
            $rules["column_mappings.{$fieldKey}"] = $isAlwaysRequired
                ? 'required|integer|min:0'
                : 'nullable|integer|min:0';
        }

        $validated = $request->validate($rules);

        $customerId = (int) $validated['customer_id'];

        // Prefer explicit field_order (from drag-sort) so mapping key order is preserved.
        $orderedInput = [];
        $rawMappings = (array) ($validated['column_mappings'] ?? []);
        $fieldOrder = array_values(array_unique(array_filter(
            (array) ($validated['field_order'] ?? array_keys($rawMappings)),
            static fn ($key) => is_string($key) && $key !== ''
        )));

        foreach ($fieldOrder as $fieldKey) {
            if (! array_key_exists($fieldKey, $rawMappings)) {
                continue;
            }
            $orderedInput[$fieldKey] = $rawMappings[$fieldKey];
        }
        foreach ($rawMappings as $fieldKey => $value) {
            if (! array_key_exists($fieldKey, $orderedInput)) {
                $orderedInput[$fieldKey] = $value;
            }
        }

        $columnMappings = $this->mappingService->sanitizeColumnMappings(
            $orderedInput,
            $importType,
            false,
            $fieldOrder
        );

        // Required map excludes meta keys and removed fields.
        $alwaysRequired = RiderActivityImportMappingService::alwaysRequiredFieldKeys();
        $submittedRequired = array_flip((array) ($validated['required_fields'] ?? []));
        $mappingKeys = array_keys(array_filter(
            $columnMappings,
            static fn ($value, $key) => $key !== RiderActivityImportMappingService::ORDER_META_KEY,
            ARRAY_FILTER_USE_BOTH
        ));
        $requiredFieldsMap = [];
        foreach ($allowedFields as $fieldKey) {
            if (in_array($fieldKey, $alwaysRequired, true)) {
                $requiredFieldsMap[$fieldKey] = true;
            } elseif (! in_array($fieldKey, $mappingKeys, true)) {
                $requiredFieldsMap[$fieldKey] = false;
            } else {
                $requiredFieldsMap[$fieldKey] = isset($submittedRequired[$fieldKey]);
            }
        }

        RiderActivityImportSetting::updateOrCreate(
            [
                'company_id' => CompanyContext::id(),
                'customer_id' => $customerId,
                'import_type' => $importType,
            ],
            [
                'header_rows_to_skip' => (int) $validated['header_rows_to_skip'],
                'column_mappings' => $columnMappings,
                'required_fields' => $requiredFieldsMap,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        $typeLabel = RiderActivityImportMappingService::importTypeLabels()[$importType] ?? 'Activity';
        Flash::success($typeLabel . ' import settings saved for the selected project.');

        return redirect()->route('settings-panel.rider-activity-import-settings.index', [
            'company_slug' => $request->route('company_slug'),
            'customer_id' => $customerId,
            'import_type' => $importType,
        ]);
    }

    public function preview(Request $request)
    {
        $this->authorizeManage();

        $request->validate([
            'file' => 'required|file|max:10240',
            'header_rows_to_skip' => 'nullable|integer|min:0|max:20',
        ]);

        try {
            $preview = $this->mappingService->previewUploadedFile($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not read the Excel file. Please upload a valid .xlsx, .xls, or .csv file.',
            ], 422);
        }

        return response()->json(array_merge($preview, [
            'header_rows_to_skip' => (int) $request->input('header_rows_to_skip', RiderActivityImportMappingService::defaultHeaderRowsToSkip()),
        ]));
    }

    public function exportTemplate(Request $request)
    {
        $this->authorizeManage();

        $importType = RiderActivityImportMappingService::normalizeImportType($request->input('import_type', $request->get('import_type')));
        $customerId = (int) ($request->input('customer_id', $request->get('customer_id')) ?: RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID);

        if ($request->filled('column_mappings')) {
            $normalized = $this->mappingService->normalizeStoredMappings(
                (array) $request->input('column_mappings'),
                $importType
            );
            $columnMappings = $normalized['column_mappings'];
            $headerRowsToSkip = max(0, (int) $request->input('header_rows_to_skip', RiderActivityImportMappingService::defaultHeaderRowsToSkip()));
        } else {
            $resolved = $this->mappingService->resolve($customerId, $importType);
            $columnMappings = $resolved['column_mappings'];
            $headerRowsToSkip = $resolved['header_rows_to_skip'];
        }

        $typeKey = $importType === RiderActivityImportMappingService::TYPE_LIVE ? 'live' : 'rider';
        $filename = $typeKey . '-activity-import-template.xlsx';

        return Excel::download(
            new RiderActivityImportTemplateExport(
                $headerRowsToSkip,
                $columnMappings,
                RiderActivityImportMappingService::fieldLabels(),
                $importType
            ),
            $filename
        );
    }
}
