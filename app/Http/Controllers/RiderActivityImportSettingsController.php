<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\RiderActivityImportSetting;
use App\Services\RiderActivities\RiderActivityImportMappingService;
use App\Support\CompanyContext;
use Flash;
use Illuminate\Http\Request;

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

        return view('settings.rider_activity_import.index', [
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
            'importType' => $importType,
            'importTypeLabels' => RiderActivityImportMappingService::importTypeLabels(),
            'headerRowsToSkip' => $resolved['header_rows_to_skip'],
            'columnMappings' => $resolved['column_mappings'],
            'fieldLabels' => RiderActivityImportMappingService::fieldLabels(),
            'requiredFields' => RiderActivityImportMappingService::requiredFields(),
            'defaultMappings' => RiderActivityImportMappingService::defaultColumnMappings($importType),
            'defaultCustomerId' => RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID,
            'storedSetting' => $stored,
            'configuredCustomerIds' => $configuredCustomerIds,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $importType = RiderActivityImportMappingService::normalizeImportType($request->input('import_type'));

        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'import_type' => 'required|in:rider,live',
            'header_rows_to_skip' => 'required|integer|min:0|max:20',
            'column_mappings' => 'required|array',
            'column_mappings.date' => 'required|integer|min:0',
            'column_mappings.rider_id' => 'required|integer|min:0',
            'column_mappings.payout_type' => 'nullable|integer|min:0',
            'column_mappings.delivery_rating' => 'nullable|integer|min:0',
            'column_mappings.login_hr' => 'nullable|integer|min:0',
            'column_mappings.delivered_orders' => 'nullable|integer|min:0',
            'column_mappings.cancelled_orders' => 'nullable|integer|min:0',
            'column_mappings.rejected_orders' => 'nullable|integer|min:0',
            'column_mappings.ontime_orders_percentage' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $customerId = (int) $validated['customer_id'];
        $columnMappings = $this->mappingService->sanitizeColumnMappings($validated['column_mappings'], $importType);

        RiderActivityImportSetting::updateOrCreate(
            [
                'company_id' => CompanyContext::id(),
                'customer_id' => $customerId,
                'import_type' => $importType,
            ],
            [
                'header_rows_to_skip' => (int) $validated['header_rows_to_skip'],
                'column_mappings' => $columnMappings,
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
}
