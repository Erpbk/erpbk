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
use App\Models\Garages;
use App\Models\Recruiters;
use App\Models\Riders;
use App\Models\Bikes;
use App\Models\Sims;
use App\Models\Items;
use App\Models\salik;
use App\Models\RiderInvoices;
use App\Models\DeletionCascade;
use App\Traits\TracksCascadingDeletions;
use Laracasts\Flash\Flash;
use App\Models\RtaFines;
use App\Models\Vouchers;
use App\Models\Transactions;

class TrashController extends Controller
{
    use TracksCascadingDeletions;
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
        'items' => [
            'model' => Items::class,
            'name' => 'Items',
            'icon' => 'fa-box',
            'display_columns' => ['name', 'price', 'cost'],
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
        'transactions' => [
            'model' => Transactions::class,
            'name' => 'Transactions',
            'icon' => 'fa-file-invoice',
            'display_columns' => ['id', 'trans_code', 'trans_date', 'billing_month', 'amount', 'status'],
        ],
    ];

    /**
     * Display centralized trash bin
     */
    public function index(Request $request)
    {
        // Check if user has permission to view trash
        if (!auth()->user()->can('trash_view')) {
            abort(403, 'You do not have permission to access the recycle bin.');
        }

        $moduleFilter = $request->get('module', 'all');
        $searchQuery = $request->get('search', '');

        $trashedRecords = [];
        $totalCount = 0;

        foreach ($this->softDeleteModels as $key => $config) {
            // Check if user has either trash_view or module-specific permission
            $hasPermission = auth()->user()->can('trash_view');

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
                $query = $model::onlyTrashed();

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
                    $canRestore = auth()->user()->can('trash_restore');

                    // Check force delete permission
                    $canForceDelete = auth()->user()->can('trash_force_delete');

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
        $cascadeHistory = DB::table('deletion_cascades')
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
    public function restore(Request $request, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            Flash::error('Invalid module specified.');
            return redirect()->route('trash.index');
        }

        $config = $this->softDeleteModels[$module];

        // Check permission (either global trash_restore or module-specific)
        $hasPermission = auth()->user()->can('trash_restore');

        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        $model = $config['model'];

        // Use Eloquent to find the trashed record
        $record = $model::onlyTrashed()->find($id);

        if (!$record) {
            Flash::error('Record not found in trash.');
            return redirect()->route('trash.index');
        }

        DB::beginTransaction();
        try {
            // Restore the primary record using Eloquent
            $record->restore();

            $restoredItems = [];

            // DATABASE-DRIVEN: Fetch cascaded deletions from deletion_cascades table
            $cascadedDeletions = DB::table('deletion_cascades')
                ->where('primary_model', $config['model'])
                ->where('primary_id', $id)
                ->where('deletion_type', 'soft')
                ->get();

            // Restore each cascaded record based on database data
            foreach ($cascadedDeletions as $cascade) {
                // Find the related model class
                $relatedModelClass = $cascade->related_model;

                if (class_exists($relatedModelClass)) {
                    try {
                        // Use Eloquent to restore the related record
                        $relatedRecord = $relatedModelClass::onlyTrashed()->find($cascade->related_id);

                        if ($relatedRecord) {
                            $relatedRecord->restore();
                            $restoredItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";

                            // Log the restoration
                            activity()
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'restored_with' => $config['model'],
                                    'primary_id' => $id,
                                    'cascade_type' => 'automatic',
                                    'model' => $relatedModelClass,
                                    'record_id' => $cascade->related_id,
                                ])
                                ->log('restored (cascaded from primary record)');
                        }
                    } catch (\Exception $e) {
                        Log::error("Error restoring cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

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

        return redirect()->route('trash.index');
    }

    /**
     * Permanently delete a record
     */
    public function forceDestroy(Request $request, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            Flash::error('Invalid module specified.');
            return redirect()->route('trash.index');
        }

        $config = $this->softDeleteModels[$module];

        // Check permission (either global trash_force_delete or module-specific)
        $hasPermission = auth()->user()->can('trash_force_delete');

        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        $model = $config['model'];

        // Use Eloquent to find the trashed record
        $record = $model::onlyTrashed()->find($id);

        if (!$record) {
            Flash::error('Record not found in trash.');
            return redirect()->route('trash.index');
        }

        DB::beginTransaction();
        try {
            $deletedItems = [];

            // DATABASE-DRIVEN: Fetch all cascaded deletions from deletion_cascades table
            $cascadedDeletions = DB::table('deletion_cascades')
                ->where('primary_model', $config['model'])
                ->where('primary_id', $id)
                ->get();

            // Check for business constraints before permanent deletion
            // Check constraint tables directly from database
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
                            $count = DB::table($constraintTable)
                                ->where($foreignKey, $id)
                                ->count();

                            if ($count > 0) {
                                DB::rollBack();
                                Flash::error("Cannot permanently delete {$config['name']}. Record has {$count} related records in {$constraintTable}.");
                                return redirect()->route('trash.index');
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
                                        $count = DB::table($constraintTable)
                                            ->where($foreignKey, $cascade->related_id)
                                            ->count();

                                        if ($count > 0) {
                                            DB::rollBack();
                                            Flash::error("Cannot permanently delete {$config['name']}. Related " .
                                                class_basename($relatedModelClass) .
                                                " ({$cascade->related_name}) has {$count} related records in {$constraintTable}.");
                                            return redirect()->route('trash.index');
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

            // DATABASE-DRIVEN: Permanently delete all cascaded records
            foreach ($cascadedDeletions as $cascade) {
                $relatedModelClass = $cascade->related_model;

                if (class_exists($relatedModelClass)) {
                    try {
                        // Use Eloquent to permanently delete the related record
                        $relatedRecord = $relatedModelClass::onlyTrashed()->find($cascade->related_id);

                        if ($relatedRecord) {
                            $relatedRecord->forceDelete();
                            $deletedItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";

                            // Log the permanent deletion
                            activity()
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'deleted_with' => $config['model'],
                                    'primary_id' => $id,
                                    'cascade_type' => 'automatic',
                                    'model' => $relatedModelClass,
                                    'record_id' => $cascade->related_id,
                                    'record_name' => $cascade->related_name,
                                ])
                                ->log('force deleted (cascaded from primary record)');
                        }
                    } catch (\Exception $e) {
                        Log::error("Error deleting cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            // Remove all cascade records associated with this deletion
            DB::table('deletion_cascades')
                ->where('primary_model', $config['model'])
                ->where('primary_id', $id)
                ->delete();

            DB::table('deletion_cascades')
                ->where('related_model', $config['model'])
                ->where('related_id', $id)
                ->delete();

            // Permanently delete the primary record using Eloquent
            $record->forceDelete();

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

        return redirect()->route('trash.index');
    }

    /**
     * Show a deleted record in modal (for vouchers and other modules)
     */
    public function show(Request $request, $module, $id)
    {
        if (!isset($this->softDeleteModels[$module])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Invalid module specified.'], 404);
            }
            Flash::error('Invalid module specified.');
            return redirect()->route('trash.index');
        }

        $config = $this->softDeleteModels[$module];
        $model = $config['model'];

        // Check permission
        if (!auth()->user()->can('trash_view')) {
            abort(403, 'Unauthorized action.');
        }

        // Find the trashed record
        $record = $model::onlyTrashed()->find($id);

        if (!$record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Record not found in trash.'], 404);
            }
            Flash::error('Record not found in trash.');
            return redirect()->route('trash.index');
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

        return redirect()->route('trash.index');
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
                // Use Eloquent to count soft-deleted records
                $count = $model::onlyTrashed()->count();

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
}
