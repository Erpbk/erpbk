<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Banks;
use App\Models\Accounts;
use App\Models\Customers;
use App\Models\Vendors;
use App\Models\Supplier;
use App\Models\LeasingCompanies;
use App\Models\LeasingCompanyInvoice;
use App\Models\Garages;
use App\Models\Recruiters;
use App\Models\Riders;
use App\Models\Bikes;
use App\Models\Sims;
use App\Models\SimCompany;
use App\Models\BikeRentCompany;
use App\Models\FuelCompany;
use App\Models\FuelData;
use App\Models\Items;
use App\Models\salik;
use App\Models\RiderInventoryAssignment;
use App\Models\RiderInvoices;
use App\Models\DeletionCascade;
use App\Traits\TracksCascadingDeletions;
use Laracasts\Flash\Flash;
use App\Models\RtaFines;
use App\Models\Vouchers;
use App\Models\Transactions;
use App\Services\ActivityLogger;
use App\Services\DeleteRequestService;
use App\Services\FuelMonthlyLedgerService;
use App\Services\SalikPaymentReversalService;
use App\Models\SupplierInvoices;
use App\Models\SimInvoice;
use App\Models\SimInvoiceItem;
use App\Models\Loan;
use App\Models\visa_expenses;
use App\Models\license_expenses;
use App\Models\visa_installment_plan;
use App\Models\license_installment_plan;
use App\Models\ExpenseAccount;
use Illuminate\Support\Facades\Storage;
use App\Support\CompanyContext;
use App\Support\TrashedRecordQuery;
use Illuminate\Database\Eloquent\Builder;

class TrashController extends Controller
{
    use TracksCascadingDeletions;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:settings_recycle_bin_view')->only('index', 'show');
        $this->middleware('permission:settings_recycle_bin_edit')->only('restore');
        $this->middleware('permission:settings_recycle_bin_delete')->only('forceDestroy');
    }

    /**
     * Soft-deleted records for the recycle bin: only rows owned by the current company.
     * Uses strict company_id (excludes shared fixed accounts and NULL company_id orphans).
     */
    private function trashedQuery(string $modelClass): Builder
    {
        return TrashedRecordQuery::for($modelClass);
    }

    private function findTrashedRecord(string $modelClass, $id)
    {
        return TrashedRecordQuery::find($modelClass, $id);
    }

    private function applyTrashModuleConstraints(Builder $query, array $config): Builder
    {
        if (! empty($config['where']) && is_array($config['where'])) {
            $query->where($config['where']);
        }

        return $query;
    }

    private function forgetDeletionCascades(string $modelClass, $id): void
    {
        \App\Support\CompanyQuery::table('deletion_cascades')
            ->where('primary_model', $modelClass)
            ->where('primary_id', $id)
            ->delete();

        \App\Support\CompanyQuery::table('deletion_cascades')
            ->where('related_model', $modelClass)
            ->where('related_id', $id)
            ->delete();
    }
    /**
     * List of models that support soft deletes
     */
    private $softDeleteModels = [
        'banks' => [
            'model' => Banks::class,
            'name' => 'Banks',
            'icon' => 'fa-bank',
            'display_columns' => ['name', 'account_no', 'branch'],
        ],
        'accounts' => [
            'model' => Accounts::class,
            'name' => 'Accounts',
            'icon' => 'fa-book',
            'display_columns' => ['account_code', 'name', 'account_type'],
        ],
        'customers' => [
            'model' => Customers::class,
            'name' => 'Customers',
            'icon' => 'fa-users',
            'display_columns' => ['name', 'company_name', 'contact_number'],
        ],
        'vendors' => [
            'model' => Vendors::class,
            'name' => 'Vendors',
            'icon' => 'fa-truck',
            'display_columns' => ['name', 'email', 'contact_number'],
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'name' => 'Suppliers',
            'icon' => 'fa-industry',
            'display_columns' => ['name', 'email', 'contact_number'],
        ],
        'leasing_companies' => [
            'model' => LeasingCompanies::class,
            'name' => 'Leasing Companies',
            'icon' => 'fa-building',
            'display_columns' => ['name', 'contact_person', 'contact_number'],
        ],
        'garages' => [
            'model' => Garages::class,
            'name' => 'Garages',
            'icon' => 'fa-wrench',
            'display_columns' => ['name', 'contact_person', 'contact_number'],
        ],
        'recruiters' => [
            'model' => Recruiters::class,
            'name' => 'Recruiters',
            'icon' => 'fa-user-plus',
            'display_columns' => ['name', 'email', 'contact_number'],
        ],
        'riders' => [
            'model' => Riders::class,
            'name' => 'Riders',
            'icon' => 'fa-motorcycle',
            'display_columns' => ['rider_id', 'name', 'personal_contact'],
        ],
        'bikes' => [
            'model' => Bikes::class,
            'name' => 'Bikes',
            'icon' => 'fa-motorcycle',
            'display_columns' => ['plate', 'model', 'chassis_number'],
        ],
        'sims' => [
            'model' => Sims::class,
            'name' => 'SIM Cards',
            'icon' => 'fa-sim-card',
            'display_columns' => ['number', 'company', 'status'],
        ],
        'sim_companies' => [
            'model' => SimCompany::class,
            'name' => 'SIM Companies',
            'icon' => 'fa-building',
            'display_columns' => ['name', 'email', 'company_contact'],
        ],
        'bike_rent_companies' => [
            'model' => BikeRentCompany::class,
            'name' => 'Bike on rent — Customers',
            'icon' => 'fa-building',
            'display_columns' => ['name', 'email', 'company_contact'],
            'where' => ['customer_type' => 'bike_rental'],
        ],
        'garage_customers' => [
            'model' => BikeRentCompany::class,
            'name' => 'Garage — Customers',
            'icon' => 'fa-wrench',
            'display_columns' => ['name', 'email', 'company_contact'],
            'where' => ['customer_type' => 'garage'],
        ],
        'fuel_companies' => [
            'model' => FuelCompany::class,
            'name' => 'Fuel Companies',
            'icon' => 'fa-gas-pump',
            'display_columns' => ['name', 'email', 'company_contact'],
        ],
        'fuel_data' => [
            'model' => FuelData::class,
            'name' => 'Fuel Transactions',
            'icon' => 'fa-gas-pump',
            'display_columns' => ['trans_no', 'billing_month', 'rider_id', 'bike_no', 'card_no', 'total'],
        ],
        'items' => [
            'model' => Items::class,
            'name' => 'Items',
            'icon' => 'fa-box',
            'display_columns' => ['name', 'price', 'cost'],
        ],
        'rider_inventory_assignments' => [
            'model' => RiderInventoryAssignment::class,
            'name' => 'Rider Inventory Assignments',
            'icon' => 'fa-box-open',
            'display_columns' => ['id', 'rider_id', 'status', 'amount'],
        ],
        'rider_invoices' => [
            'model' => RiderInvoices::class,
            'name' => 'Rider Invoices',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'total_amount', 'status'],
        ],
        'rta_account' => [
            'model' => Accounts::class,
            'name' => 'RTA Account',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'name', 'account_code', 'account_type'],
        ],
        'rta_fines' => [
            'model' => RtaFines::class,
            'name' => 'RTA Fines',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'ticket_no', 'amount', 'status'],
        ],
        'salik' => [
            'model' => salik::class,
            'name' => 'Salik',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'ticket_no', 'amount', 'status'],
        ],
        'salik_accounts' => [
            'model' => Accounts::class,
            'name' => 'Salik Account',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'name', 'account_code', 'account_type'],
        ],
        'vouchers' => [
            'model' => Vouchers::class,
            'name' => 'Vouchers',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'trans_code', 'trans_date', 'billing_month', 'amount', 'status'],
        ],
        'leasing_company_invoices' => [
            'model' => LeasingCompanyInvoice::class,
            'name' => 'Leasing Company Invoices',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'invoice_number', 'billing_month', 'total_amount', 'status'],
        ],
        'sim_invoices' => [
            'model' => SimInvoice::class,
            'name' => 'SIM Invoices',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'invoice_number', 'billing_month', 'total_amount', 'status'],
        ],
        'supplier_invoices' => [
            'model' => SupplierInvoices::class,
            'name' => 'Supplier Invoices',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'inv_id', 'billing_month', 'total_amount', 'status'],
        ],
        'loans' => [
            'model' => Loan::class,
            'name' => 'Bank Loans',
            'icon' => 'fa-university',
            'display_columns' => ['loan_number', 'bank_name', 'agreement_ref', 'status'],
        ],
        'visa_expenses' => [
            'model' => visa_expenses::class,
            'name' => 'Visa Expenses',
            'icon' => 'fa-id-card',
            'display_columns' => ['id', 'visa_status', 'billing_month', 'amount', 'payment_status'],
        ],
        'license_expenses' => [
            'model' => license_expenses::class,
            'name' => 'License Expenses',
            'icon' => 'fa-id-badge',
            'display_columns' => ['id', 'license_status', 'billing_month', 'amount', 'payment_status'],
        ],
        'visa_installment_plans' => [
            'model' => visa_installment_plan::class,
            'name' => 'Visa Installment Plans',
            'icon' => 'fa-calendar-check',
            'display_columns' => ['id', 'billing_month', 'amount', 'status', 'reference_number'],
        ],
        'license_installment_plans' => [
            'model' => license_installment_plan::class,
            'name' => 'License Installment Plans',
            'icon' => 'fa-calendar-check',
            'display_columns' => ['id', 'billing_month', 'amount', 'status', 'reference_number'],
        ],
    ];

    /**
     * Display centralized trash bin
     */
    public function index(Request $request)
    {

        $moduleFilter = $request->get('module', 'all');
        $searchQuery = $request->get('search', '');

        $trashedRecords = [];
        $totalCount = 0;

        foreach ($this->softDeleteModels as $key => $config) {
            // Check if user has either trash_view or module-specific permission
            $hasPermission = auth()->user()->can('settings_recycle_bin_view');

            if (!$hasPermission) {
                continue;
            }

            // Skip if filtering by specific module
            if ($moduleFilter !== 'all' && $moduleFilter !== $key) {
                continue;
            }

            $model = $config['model'];

            // Use Eloquent model with onlyTrashed() to get soft-deleted records
            try {
                $query = $this->trashedQuery($model);
                $this->applyTrashModuleConstraints($query, $config);

                // Apply search if provided
                if ($searchQuery) {
                    $query->where(function ($q) use ($config, $searchQuery) {
                        foreach ($config['display_columns'] as $column) {
                            $q->orWhere($column, 'like', '%' . $searchQuery . '%');
                        }
                    });
                }

                $records = $query->orderBy('deleted_at', 'desc')
                    ->limit(100)
                    ->get();

                foreach ($records as $record) {
                    // Check restore permission
                    $canRestore = auth()->user()->can('settings_recycle_bin_edit');

                    // Check force delete permission
                    $canForceDelete = auth()->user()->can('settings_recycle_bin_delete');

                    // Get cascade information - check if this was deleted as a cascade
                    $causedBy = DeletionCascade::where('related_model', $config['model'])
                        ->where('related_id', $record->id)
                        ->with('deletedByUser')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // Get what this deletion cascaded to
                    $cascadedTo = DeletionCascade::where('primary_model', $config['model'])
                        ->where('primary_id', $record->id)
                        ->with('deletedByUser')
                        ->orderBy('created_at', 'desc')
                        ->get();

                    // Get the user who deleted this record
                    $deletedByUser = null;
                    if ($causedBy && $causedBy->deletedByUser) {
                        $deletedByUser = $causedBy->deletedByUser;
                    } elseif (isset($record->deleted_by) && $record->deleted_by) {
                        $deletedByUser = \App\Models\User::find($record->deleted_by);
                    }

                    $trashedRecords[] = [
                        'id' => $record->id,
                        'module' => $key,
                        'module_name' => $config['name'],
                        'icon' => $config['icon'],
                        'record' => $record, // Now this is an Eloquent model instance
                        'display_columns' => $config['display_columns'],
                        'deleted_at' => $record->deleted_at,
                        'can_restore' => $canRestore,
                        'can_force_delete' => $canForceDelete,
                        'caused_by' => $causedBy,
                        'cascaded_to' => $cascadedTo,
                        'deleted_by_user' => $deletedByUser,
                    ];
                    $totalCount++;
                }
            } catch (\Exception $e) {
                // Log the error but continue processing other modules
                Log::error("Error fetching trash for {$key}: " . $e->getMessage());
                continue;
            }
        }

        // Sort by deletion date (newest first)
        usort($trashedRecords, function ($a, $b) {
            return strtotime($b['deleted_at']) <=> strtotime($a['deleted_at']);
        });

        // Paginate manually
        $perPageRequest = $request->get('per_page', 20);
        $perPageDisplay = $perPageRequest; // Keep original for display

        // Handle 'all' option
        $isShowingAll = false;
        if ($perPageRequest === 'all' || $perPageRequest === -1 || $perPageRequest === '-1') {
            $isShowingAll = true;
            $perPageDisplay = 'all';
            $perPageNumeric = $totalCount; // Show all records
        } else {
            $perPageNumeric = is_numeric($perPageRequest) ? (int) $perPageRequest : 20;
            $perPageNumeric = $perPageNumeric > 0 ? $perPageNumeric : 20;
            // Set reasonable limits
            $perPageNumeric = min($perPageNumeric, 1000); // Maximum 1000 records per page
            $perPageDisplay = $perPageNumeric; // Use numeric value for display
        }

        $currentPage = $request->get('page', 1);

        if ($isShowingAll) {
            $paginatedRecords = $trashedRecords;
        } else {
            $offset = ($currentPage - 1) * $perPageNumeric;
            $paginatedRecords = array_slice($trashedRecords, $offset, $perPageNumeric);
        }

        // Fetch cascade history directly from database
        $cascadeHistory = \App\Support\CompanyQuery::table('deletion_cascades')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('trash.index', [
            'trashedRecords' => $paginatedRecords,
            'modules' => $this->softDeleteModels,
            'currentModule' => $moduleFilter,
            'searchQuery' => $searchQuery,
            'totalCount' => $totalCount,
            'currentPage' => $currentPage,
            'perPage' => $perPageDisplay,
            'totalPages' => $isShowingAll ? 1 : ceil($totalCount / $perPageNumeric),
            'cascadeHistory' => $cascadeHistory,
        ]);
    }

    /**
     * Restore a deleted record
     */
    public function restore(Request $request, $company_slug, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            Flash::error('Invalid module specified.');
            return redirect()->route('settings-panel.trash.index');
        }

        $config = $this->softDeleteModels[$module];

        // Check permission (either global trash_restore or module-specific)
        $hasPermission = auth()->user()->can('settings_recycle_bin_edit');

        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        $model = $config['model'];

        // Use Eloquent to find the trashed record
        $record = $this->findTrashedRecord($model, $id);

        if (!$record) {
            Flash::error('Record not found in trash.');
            return redirect()->route('settings-panel.trash.index');
        }

        if (DeleteRequestService::hasPending($record)) {
            Flash::error('This record has a pending delete request. Approve or reject it from Delete Requests instead of restoring from the Recycle Bin.');
            return redirect()->route('settings-panel.delete-requests.index');
        }

        DB::beginTransaction();
        try {
            if ($module === 'vouchers' && $record instanceof Vouchers && ($record->voucher_type ?? '') === 'SV') {
                SalikPaymentReversalService::assertCanRestore($record);
            }

            // Restore the primary record using Eloquent
            $record->restore();

            if (\Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'deleted_by') && $record->deleted_by) {
                $record->deleted_by = null;
                $record->save();
            }

            DeleteRequestService::markRestoredFromBin($record, auth()->user());

            $restoredItems = [];

            // DATABASE-DRIVEN: Fetch cascaded deletions from deletion_cascades table
            $cascadedDeletions = \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('primary_model', $config['model'])
                ->where('primary_id', $id)
                ->where('deletion_type', 'soft')
                ->get();

            // Restore each cascaded record based on database data
            foreach ($cascadedDeletions as $cascade) {
                // Find the related model class
                $relatedModelClass = $cascade->related_model;

                if ($module === 'vouchers' && $relatedModelClass === salik::class) {
                    continue;
                }

                if (class_exists($relatedModelClass)) {
                    try {
                        // Use Eloquent to restore the related record
                        $relatedRecord = $this->findTrashedRecord($relatedModelClass, $cascade->related_id);

                        if ($relatedRecord) {
                            $relatedRecord->restore();
                            $restoredItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";
                            if ($relatedRecord instanceof SupplierInvoices) {
                                $restoredItems = array_merge($restoredItems, $relatedRecord->restoreRelatedRecords());
                            }

                            // Log the restoration
                            ActivityLogger::custom(
                                'restored (cascaded from primary record)',
                                'Trash',
                                null,
                                [
                                    'restored_with' => $config['model'],
                                    'primary_id' => $id,
                                    'cascade_type' => 'automatic',
                                    'model' => $relatedModelClass,
                                    'record_id' => $cascade->related_id,
                                ]
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error("Error restoring cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            if ($module === 'vouchers' && $record instanceof Vouchers && ($record->voucher_type ?? '') === 'SV') {
                $relinked = SalikPaymentReversalService::completeRestore($record->fresh() ?? $record);
                $restoredItems[] = $relinked . ' salik record(s) re-linked';
            }

            if ($module === 'fuel_data' && $record instanceof FuelData) {
                $this->syncFuelMonthlyLedger($record);
                $restoredItems[] = 'monthly fuel ledger synced';
            }

            if ($module === 'salik' && $record instanceof salik) {
                $this->syncSalikMonthlyInvoice($record);
                $restoredItems[] = 'monthly salik invoice synced';
            }

            if ($module === 'sim_invoices' && $record instanceof SimInvoice) {
                $relatedTransactions = Transactions::onlyTrashed()
                    ->withoutGlobalScope('branch')
                    ->where('reference_type', 'SimInvoice')
                    ->where('reference_id', $record->id)
                    ->get();
                foreach ($relatedTransactions as $transaction) {
                    $transaction->restore();
                    if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
                        $transaction->deleted_by = null;
                        $transaction->save();
                    }
                    $restoredItems[] = 'Transaction #' . $transaction->id;
                }
            }

            if ($module === 'supplier_invoices' && $record instanceof SupplierInvoices) {
                $restoredItems = array_merge($restoredItems, $record->restoreRelatedRecords());
            }

            if (in_array($module, ['visa_expenses', 'license_expenses'], true)) {
                $restoredItems = array_merge(
                    $restoredItems,
                    $this->restoreExpenseRelatedRecords($module, $record)
                );
            }

            if (in_array($module, ['visa_installment_plans', 'license_installment_plans'], true)) {
                $restoredItems = array_merge(
                    $restoredItems,
                    $this->restoreInstallmentRelatedRecords($module, $record)
                );
            }

            $this->forgetDeletionCascades($config['model'], $id);

            DB::commit();

            // Build restoration message
            $message = $config['name'] . ' restored successfully.';
            if (!empty($restoredItems)) {
                $message .= ' (Also restored: ' . implode(', ', $restoredItems) . ')';
            }

            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            Flash::success($message);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore record: ' . $e->getMessage(),
                ], 422);
            }
            Flash::error('Failed to restore record: ' . $e->getMessage());
        }

        return redirect()->route('settings-panel.trash.index');
    }

    /**
     * Permanently delete a record
     */
    public function forceDestroy(Request $request, $company_slug, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            Flash::error('Invalid module specified.');
            return redirect()->route('settings-panel.trash.index');
        }

        $config = $this->softDeleteModels[$module];

        // Check permission (either global trash_force_delete or module-specific)
        $hasPermission = auth()->user()->can('settings_recycle_bin_delete');

        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        $model = $config['model'];

        // Use Eloquent to find the trashed record
        $record = $this->findTrashedRecord($model, $id);

        if (!$record) {
            Flash::error('Record not found in trash.');
            return redirect()->route('settings-panel.trash.index');
        }

        if (DeleteRequestService::hasPending($record)) {
            Flash::error('This record has a pending delete request. Resolve the request before permanently deleting.');
            return redirect()->route('settings-panel.delete-requests.index');
        }

        if ($module === 'suppliers' && $record instanceof Supplier) {
            $blockReason = $record->cannotBeDeletedReason();
            if ($blockReason) {
                Flash::error($blockReason);
                return redirect()->route('settings-panel.trash.index');
            }
        }

        DB::beginTransaction();
        try {
            $deletedItems = [];

            // DATABASE-DRIVEN: Fetch all cascaded deletions from deletion_cascades table
            $cascadedDeletions = \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('primary_model', $config['model'])
                ->where('primary_id', $id)
                ->get();

            // Check for business constraints before permanent deletion
            // Check constraint tables directly from database
            // Fuel/voucher/invoice children are not keyed by the parent id as account_id/customer_id.
            if (! in_array($module, ['fuel_data', 'vouchers', 'sim_invoices', 'supplier_invoices', 'visa_expenses', 'license_expenses', 'visa_installment_plans', 'license_installment_plans'], true)) {
                $constraintTables = [
                    'transactions' => ['account_id', 'customer_id', 'vendor_id', 'supplier_id'],
                    'invoices' => ['customer_id', 'vendor_id'],
                    'vouchers' => ['account_id', 'bank_id'],
                    'journal_entries' => ['account_id'],
                ];

                foreach ($constraintTables as $constraintTable => $foreignKeys) {
                    try {
                        foreach ($foreignKeys as $foreignKey) {
                            // Check if this table and foreign key combination has any records
                            if (Schema::hasColumn($constraintTable, $foreignKey)) {
                                $count = \App\Support\CompanyQuery::table($constraintTable)
                                    ->where($foreignKey, $id)
                                    ->count();

                                if ($count > 0) {
                                    DB::rollBack();
                                    Flash::error("Cannot permanently delete {$config['name']}. Record has {$count} related records in {$constraintTable}.");
                                    return redirect()->route('settings-panel.trash.index');
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Table might not exist, continue checking other constraints
                        continue;
                    }
                }

                // Check cascaded records for constraints
                foreach ($cascadedDeletions as $cascade) {
                    $relatedModelClass = $cascade->related_model;

                    if (class_exists($relatedModelClass)) {
                        try {
                            $relatedModelInstance = new $relatedModelClass;
                            $relatedTableName = $relatedModelInstance->getTable();

                            // Check constraints for related records
                            foreach ($constraintTables as $constraintTable => $foreignKeys) {
                                foreach ($foreignKeys as $foreignKey) {
                                    try {
                                        if (Schema::hasColumn($constraintTable, $foreignKey)) {
                                            $count = \App\Support\CompanyQuery::table($constraintTable)
                                                ->where($foreignKey, $cascade->related_id)
                                                ->count();

                                            if ($count > 0) {
                                                DB::rollBack();
                                                Flash::error("Cannot permanently delete {$config['name']}. Related " .
                                                    class_basename($relatedModelClass) .
                                                    " ({$cascade->related_name}) has {$count} related records in {$constraintTable}.");
                                                return redirect()->route('settings-panel.trash.index');
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error("Error checking constraints for cascaded record: " . $e->getMessage());
                            continue;
                        }
                    }
                }
            }

            // DATABASE-DRIVEN: Permanently delete all cascaded records
            foreach ($cascadedDeletions as $cascade) {
                $relatedModelClass = $cascade->related_model;

                if ($module === 'vouchers' && $relatedModelClass === salik::class) {
                    continue;
                }

                if (class_exists($relatedModelClass)) {
                    try {
                        // Use Eloquent to permanently delete the related record
                        $relatedRecord = $this->findTrashedRecord($relatedModelClass, $cascade->related_id);

                        if ($relatedRecord) {
                            if ($relatedRecord instanceof SupplierInvoices) {
                                $deletedItems = array_merge($deletedItems, $relatedRecord->purgeRelatedRecords());
                            }
                            $relatedRecord->forceDelete();
                            $deletedItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";

                            // Log the permanent deletion
                            ActivityLogger::custom(
                                'force deleted (cascaded from primary record)',
                                'Trash',
                                null,
                                [
                                    'deleted_with' => $config['model'],
                                    'primary_id' => $id,
                                    'cascade_type' => 'automatic',
                                    'model' => $relatedModelClass,
                                    'record_id' => $cascade->related_id,
                                    'record_name' => $cascade->related_name,
                                ]
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error("Error deleting cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            $this->forgetDeletionCascades($config['model'], $id);

            // Permanently delete the primary record using Eloquent
            DeleteRequestService::markPermanentlyDeletedFromBin($record, auth()->user());

            $fuelSyncRiderId = null;
            $fuelSyncBillingMonth = null;
            if ($module === 'fuel_data' && $record instanceof FuelData) {
                $fuelSyncRiderId = (int) $record->rider_id;
                $fuelSyncBillingMonth = $record->billing_month;
                // Clear any ledger still pointing at this fuel row before it is removed.
                Transactions::where('reference_type', 'fuel')
                    ->where('reference_id', $record->id)
                    ->delete();
            }

            $salikSyncRiderId = null;
            $salikSyncRentalCompanyId = null;
            $salikSyncBillingMonth = null;
            if ($module === 'salik' && $record instanceof salik) {
                $salikSyncRiderId = $record->rider_id ? (int) $record->rider_id : null;
                $salikSyncRentalCompanyId = $record->rental_company_id ? (int) $record->rental_company_id : null;
                $salikSyncBillingMonth = $record->billing_month;
                // Clear invoice ledger rows for this trip only (never payment vouchers).
                Transactions::whereIn('reference_type', ['salik', 'Salik'])
                    ->where('reference_id', $record->id)
                    ->delete();
            }

            if ($module === 'vouchers' && $record instanceof Vouchers && $record->trans_code) {
                $relatedTransactions = Transactions::withTrashed()
                    ->withoutGlobalScope('branch')
                    ->where('trans_code', $record->trans_code)
                    ->get();
                foreach ($relatedTransactions as $transaction) {
                    $transaction->forceDelete();
                    $deletedItems[] = 'Transaction #' . $transaction->id;
                }
            }

            if ($module === 'sim_invoices' && $record instanceof SimInvoice) {
                $relatedTransactions = Transactions::withTrashed()
                    ->withoutGlobalScope('branch')
                    ->where('reference_type', 'SimInvoice')
                    ->where('reference_id', $record->id)
                    ->get();
                foreach ($relatedTransactions as $transaction) {
                    $transaction->forceDelete();
                    $deletedItems[] = 'Transaction #' . $transaction->id;
                }

                SimInvoiceItem::where('inv_id', $record->id)->delete();

                if ($record->attachment && Storage::disk('public')->exists($record->attachment)) {
                    Storage::disk('public')->delete($record->attachment);
                }
            }

            if ($module === 'supplier_invoices' && $record instanceof SupplierInvoices) {
                $deletedItems = array_merge($deletedItems, $record->purgeRelatedRecords());
            }

            if (in_array($module, ['visa_expenses', 'license_expenses'], true)) {
                $deletedItems = array_merge(
                    $deletedItems,
                    $this->forceDestroyExpenseRelatedRecords($module, $record)
                );
            }

            if (in_array($module, ['visa_installment_plans', 'license_installment_plans'], true)) {
                $deletedItems = array_merge(
                    $deletedItems,
                    $this->forceDestroyInstallmentRelatedRecords($module, $record)
                );
            }

            $record->forceDelete();

            if ($fuelSyncRiderId) {
                app(FuelMonthlyLedgerService::class)->sync($fuelSyncRiderId, $fuelSyncBillingMonth);
                $deletedItems[] = 'monthly fuel ledger synced';
            }

            if ($salikSyncBillingMonth && ($salikSyncRiderId || $salikSyncRentalCompanyId)) {
                app(SalikController::class)->syncMonthlyInvoiceTransactions(
                    $salikSyncRiderId,
                    $salikSyncBillingMonth,
                    $salikSyncRentalCompanyId
                );
                $deletedItems[] = 'monthly salik invoice synced';
            }

            DB::commit();

            // Build deletion message
            $message = $config['name'] . ' permanently deleted.';
            if (!empty($deletedItems)) {
                $message .= ' (Also permanently deleted: ' . implode(', ', $deletedItems) . ')';
            }

            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            Flash::success($message);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to permanently delete record: ' . $e->getMessage(),
                ], 422);
            }
            Flash::error('Failed to permanently delete record: ' . $e->getMessage());
        }

        return redirect()->route('settings-panel.trash.index');
    }

    /**
     * Show a deleted record in modal (for vouchers and other modules)
     */
    public function show(Request $request, $company_slug, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Invalid module specified.'.$module], 404);
            }
            Flash::error('Invalid module specified.');
            return redirect()->route('settings-panel.trash.index');
        }

        $config = $this->softDeleteModels[$module];
        $model = $config['model'];

        // Find the trashed record
        $record = $this->findTrashedRecord($model, $id);

        if (!$record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Record not found in trash.'], 404);
            }
            Flash::error('Record not found in trash.');
            return redirect()->route('settings-panel.trash.index');
        }

        // For vouchers, load transactions separately since the relationship might not work with soft-deleted parent
        if ($module === 'vouchers') {
            // Load transactions using trans_code
            // Try normal query first, if no results, try withTrashed (in case transactions were also deleted)
            $transactionsQuery = Transactions::where('trans_code', $record->trans_code);
            $transactions = $transactionsQuery->get();

            // If no transactions found, try with trashed records
            if ($transactions->isEmpty()) {
                $transactions = Transactions::withTrashed()
                    ->where('trans_code', $record->trans_code)
                    ->get();
            }

            // Load accounts for each transaction (accounts might also be soft-deleted)
            $transactionsWithAccounts = $transactions->map(function ($transaction) {
                if ($transaction->account_id) {
                    // Try normal query first
                    $account = Accounts::find($transaction->account_id);
                    // If not found, try with trashed
                    if (!$account) {
                        $account = Accounts::withTrashed()->find($transaction->account_id);
                    }
                    if ($account) {
                        $transaction->setRelation('account', $account);
                    }
                }
                return $transaction;
            });

            // Manually set the transactions relationship on the voucher
            $record->setRelation('transactions', $transactionsWithAccounts);

            return view('trash.voucher_show_modal', [
                'voucher' => $record,
                'isDeleted' => true
            ]);
        }

        // For other modules, you can add specific views here
        // For now, return a generic view
        if ($request->ajax() || $request->wantsJson()) {
            return view('trash.show_modal', [
                'record' => $record,
                'module' => $module,
                'config' => $config
            ]);
        }

        return redirect()->route('settings-panel.trash.index');
    }

    /**
     * Get trash statistics
     */
    public function stats()
    {
        $stats = [];

        foreach ($this->softDeleteModels as $key => $config) {
            if (!auth()->user()->can('trash_view')) {
                continue;
            }

            $model = $config['model'];

            try {
                // Use Eloquent to count soft-deleted records for current company
                $count = $this->applyTrashModuleConstraints($this->trashedQuery($model), $config)->count();

                if ($count > 0) {
                    $stats[] = [
                        'module' => $key,
                        'name' => $config['name'],
                        'icon' => $config['icon'],
                        'count' => $count,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error fetching stats for {$key}: " . $e->getMessage());
                continue;
            }
        }

        return response()->json($stats);
    }

    /**
     * Rebuild monthly fuel ledger totals after recycle-bin restore/force-delete.
     */
    private function syncFuelMonthlyLedger(FuelData $fuelData): void
    {
        if (! $fuelData->rider_id || ! $fuelData->billing_month) {
            return;
        }

        app(FuelMonthlyLedgerService::class)->sync(
            (int) $fuelData->rider_id,
            $fuelData->billing_month
        );
    }

    /**
     * Rebuild monthly salik invoice after recycle-bin restore/force-delete.
     */
    private function syncSalikMonthlyInvoice(salik $salikRecord): void
    {
        if (! $salikRecord->billing_month) {
            return;
        }

        if (! $salikRecord->rider_id && ! $salikRecord->rental_company_id) {
            return;
        }

        app(SalikController::class)->syncMonthlyInvoiceTransactions(
            $salikRecord->rider_id ? (int) $salikRecord->rider_id : null,
            $salikRecord->billing_month,
            $salikRecord->rental_company_id ? (int) $salikRecord->rental_company_id : null
        );
    }

    /**
     * Restore cascaded LE/LV vouchers and transactions, then rebuild rider ledger.
     *
     * @return array<int, string>
     */
    private function restoreExpenseRelatedRecords(string $module, $record): array
    {
        $restoredItems = [];
        [$vouchers, $transactions] = $this->expenseRelatedRecords($module, $record, true);

        foreach ($vouchers as $voucher) {
            $voucher->restore();
            if (Schema::hasColumn($voucher->getTable(), 'deleted_by') && $voucher->deleted_by) {
                $voucher->deleted_by = null;
                $voucher->save();
            }
            $restoredItems[] = 'Voucher #' . $voucher->id;
        }

        foreach ($transactions as $transaction) {
            $transaction->restore();
            if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
                $transaction->deleted_by = null;
                $transaction->save();
            }
            $restoredItems[] = 'Transaction #' . $transaction->id;
        }

        DeleteRequestService::recalculateExpenseRiderLedger($record);
        $restoredItems[] = 'rider ledger synced';

        return $restoredItems;
    }

    /**
     * Permanently delete cascaded LE/LV vouchers and transactions, then rebuild rider ledger.
     *
     * @return array<int, string>
     */
    private function forceDestroyExpenseRelatedRecords(string $module, $record): array
    {
        $deletedItems = [];
        [$vouchers, $transactions] = $this->expenseRelatedRecords($module, $record, false);

        foreach ($vouchers as $voucher) {
            $voucher->forceDelete();
            $deletedItems[] = 'Voucher #' . $voucher->id;
        }

        foreach ($transactions as $transaction) {
            $transaction->forceDelete();
            $deletedItems[] = 'Transaction #' . $transaction->id;
        }

        DeleteRequestService::recalculateExpenseRiderLedger($record);
        $deletedItems[] = 'rider ledger synced';

        return $deletedItems;
    }

    /**
     * Restore cascaded installment vouchers/transactions and rebuild liability ledger.
     *
     * @return array<int, string>
     */
    private function restoreInstallmentRelatedRecords(string $module, $record): array
    {
        $restoredItems = [];
        [$vouchers, $transactions] = $this->installmentRelatedRecords($module, $record, true);

        foreach ($vouchers as $voucher) {
            $voucher->restore();
            if (Schema::hasColumn($voucher->getTable(), 'deleted_by') && $voucher->deleted_by) {
                $voucher->deleted_by = null;
                $voucher->save();
            }
            $restoredItems[] = 'Voucher #' . $voucher->id;
        }

        foreach ($transactions as $transaction) {
            $transaction->restore();
            if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
                $transaction->deleted_by = null;
                $transaction->save();
            }
            $restoredItems[] = 'Transaction #' . $transaction->id;
        }

        $this->syncInstallmentLiabilityLedger($record);
        $restoredItems[] = 'liability ledger synced';

        return $restoredItems;
    }

    /**
     * Permanently delete cascaded installment vouchers/transactions and rebuild liability ledger.
     *
     * @return array<int, string>
     */
    private function forceDestroyInstallmentRelatedRecords(string $module, $record): array
    {
        $deletedItems = [];
        [$vouchers, $transactions] = $this->installmentRelatedRecords($module, $record, false);

        foreach ($vouchers as $voucher) {
            $voucher->forceDelete();
            $deletedItems[] = 'Voucher #' . $voucher->id;
        }

        foreach ($transactions as $transaction) {
            $transaction->forceDelete();
            $deletedItems[] = 'Transaction #' . $transaction->id;
        }

        $this->syncInstallmentLiabilityLedger($record);
        $deletedItems[] = 'liability ledger synced';

        return $deletedItems;
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function installmentRelatedRecords(string $module, $record, bool $onlyTrashed): array
    {
        $isLicense = $module === 'license_installment_plans';
        $voucherType = $isLicense ? license_installment_plan::VOUCHER_TYPE : 'VL';
        $referenceType = $isLicense ? license_installment_plan::REFERENCE_TYPE : 'VL';

        $voucherQuery = Vouchers::query()
            ->withoutGlobalScope('branch')
            ->where('ref_id', $record->id)
            ->where('voucher_type', $voucherType);
        if ($isLicense) {
            $voucherQuery->where('reason', license_installment_plan::VOUCHER_REASON);
        }
        $vouchers = $onlyTrashed ? $voucherQuery->onlyTrashed()->get() : $voucherQuery->withTrashed()->get();

        $transactionQuery = Transactions::query()
            ->withoutGlobalScope('branch')
            ->where('reference_id', $record->id)
            ->where('reference_type', $referenceType);
        $transactions = $onlyTrashed
            ? $transactionQuery->onlyTrashed()->get()
            : $transactionQuery->withTrashed()->get();

        return [$vouchers, $transactions];
    }

    private function syncInstallmentLiabilityLedger($installment): void
    {
        $billingMonth = $installment->billing_month ?? null;
        $billingMonthForLedger = (strlen((string) $billingMonth) <= 7)
            ? $billingMonth . '-01'
            : $billingMonth;

        $rider = \App\Models\Riders::find($installment->rider_id)
            ?? ExpenseAccount::find($installment->rider_id)?->rider;
        if (!$rider || !$billingMonthForLedger) {
            return;
        }

        $liabilityAccount = Accounts::where('ref_id', $rider->id)
            ->where('account_type', 'Liability')
            ->orderBy('id')
            ->first();
        if (!$liabilityAccount) {
            return;
        }

        DeleteRequestService::recalculateLedgerAfterVoucherDeletion((int) $liabilityAccount->id, $billingMonthForLedger);
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function expenseRelatedRecords(string $module, $record, bool $onlyTrashed): array
    {
        $referenceType = $module === 'visa_expenses' ? 'LV' : 'LE';

        $voucherQuery = Vouchers::query()
            ->withoutGlobalScope('branch')
            ->where('ref_id', $record->id)
            ->where('voucher_type', $referenceType);
        if ($module === 'license_expenses') {
            $voucherQuery->where(function ($q) {
                $q->whereNull('reason')->orWhere('reason', '!=', license_installment_plan::VOUCHER_REASON);
            });
        }
        $vouchers = $onlyTrashed ? $voucherQuery->onlyTrashed()->get() : $voucherQuery->withTrashed()->get();

        $transactionQuery = Transactions::query()
            ->withoutGlobalScope('branch')
            ->where(function ($q) use ($record, $referenceType) {
                $q->where(function ($inner) use ($record, $referenceType) {
                    $inner->where('reference_id', $record->id)
                        ->where('reference_type', $referenceType);
                });
                if (!empty($record->trans_code)) {
                    $q->orWhere(function ($inner) use ($record, $referenceType) {
                        $inner->where('trans_code', $record->trans_code)
                            ->where('reference_type', $referenceType);
                    });
                }
            });
        $transactions = $onlyTrashed
            ? $transactionQuery->onlyTrashed()->get()->unique('id')
            : $transactionQuery->withTrashed()->get()->unique('id');

        return [$vouchers, $transactions];
    }
}
