<?php

namespace App\Http\Controllers;

use App\DataTables\SuppliersDataTable;
use App\DataTables\FilesDataTable;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Models\Accounts;
use App\Repositories\SuppliersRepository;
use App\Http\Controllers\AppBaseController;
use App\Helpers\Account;
use App\DataTables\LedgerDataTable;
use App\Models\Transactions;
use App\Models\Files;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Support\Facades\DB;
use Flash;




class SupplierController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
  public function index(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Supplier::query()
      ->orderBy('id', 'asc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('company_name') && !empty($request->company_name)) {
      $query->where('company_name', $request->company_name);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('Suppliers.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('Suppliers.index', [
      'data' => $data,
    ]);
  }

  public function files($supplier_id, FilesDataTable $filesDataTable)
  {
    $supplier = Supplier::find($supplier_id); // Fetch supplier
    if (!$supplier) {
      abort(404, 'Supplier not found');
    }

    return $filesDataTable
      ->with([

        'type_id' => $supplier_id,   // ✅ pass 'type_id'
      ])
      ->render('suppliers.document', compact('supplier'));
  }


  public function create()
  {
    return view('suppliers.create');
  }

  private $suppliersRepository;

  public function __construct(SuppliersRepository $suppliersRepo)
  {
    $this->suppliersRepository = $suppliersRepo;
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255|unique:suppliers,name',
      'email' => 'nullable|email',
      'phone' => 'nullable|string',
      'company_name' => 'nullable|string',
      'address' => 'nullable|string',
      'status' => 'nullable|string',
    ]);

    $supplier = Supplier::create($validated);

    // Create or get parent "Supplier" account
    $parentAccount = Accounts::where('name', 'Supplier')->where('account_type', 'Liability')->where('parent_id', null)->first();
    if (!$parentAccount) {
      Flash::error('Parent account "Supplier" not found.');
    }

    // Create linked account in chart of accounts
    $account = new Accounts();
    $account->account_code = 'SUP' . str_pad($supplier->id, 4, "0", STR_PAD_LEFT);
    $account->account_type = 'Liability';
    $account->name = $supplier->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'Supplier';
    $account->ref_id = $supplier->id;
    $account->status = 1;
    $account->save();

    $supplier->account_id = $account->id;
    $supplier->save();

    Flash::success('Supplier created successfully.');
    return redirect(route('suppliers.index'));
  }


  public function show($id)
  {
    if (!auth()->user()->hasPermissionTo('supplier_view')) {
      abort(403, 'Unauthorized action.');
    }

    $supplier = $this->suppliersRepository->find($id);
    if (empty($supplier)) {
      Flash::error('Supplier not found');
      return redirect(route('suppliers.index'));
    }

    return view('suppliers.show', [
      'supplier' => $supplier,
    ]);
  }

  public function edit($id)
  {
    $supplier = $this->suppliersRepository->find($id);
    if (empty($supplier)) {
      Flash::error('Supplier not found');
      return redirect(route('suppliers.index'));
    }
    return view('suppliers.edit')->with('supplier', $supplier);
  }

  public function update(Request $request, Supplier $supplier)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'nullable|email',
      'phone' => 'nullable|string',
      'company_name' => 'nullable|string',
      'address' => 'nullable|string',
      'status' => 'nullable|string',
    ]);

    $supplier->update($validated);

    $supplier = Supplier::all();

    Flash::success('Supplier updated successfully.');
    return redirect(route('suppliers.index'));

    $parentAccount = Accounts::where('name', 'Supplier')->where('account_type', 'Liability')->where('parent_id', null)->first();
    if (!$parentAccount) {
      Flash::error('Parent account "Supplier" not found.');
    }


    foreach ($supplier as $supplier) {
      $account = new Accounts();
      $account->account_code = 'SUP' . str_pad($supplier->id, 4, "0", STR_PAD_LEFT);
      $account->name = $supplier->name;
      $account->account_type = 'Liability';
      $account->ref_name = 'Supplier';
      $account->parent_id = $parentAccount->id;
      $account->ref_id = $supplier->id;
      $account->save();

      $supplier->account_id = $account->id;
      $supplier->save();
    }
  }

  public function destroy($id)
  {
    $supplier = $this->suppliersRepository->find($id);

    if (empty($supplier)) {
      return response()->json(['errors' => ['error' => 'Supplier not found!']], 422);
    }

    // Check if supplier has transactions - protect from deletion
    $transactionCount = $supplier->transactions()->count();
    if ($transactionCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete supplier. Supplier has ' . $transactionCount . ' transaction(s). Please deactivate instead.']], 422);
    }

    // Check if supplier account has ledger entries before deletion
    if ($supplier->account) {
      $ledgerEntriesCount = DB::table('ledger_entries')
        ->where('account_id', $supplier->account->id)
        ->count();

      if ($ledgerEntriesCount > 0) {
        return response()->json(['errors' => ['error' => "Cannot delete supplier. The supplier account has {$ledgerEntriesCount} ledger entry(ies). Please clear these first."]], 422);
      }
    }

    // Track cascaded deletions
    $cascadedItems = [];
    $supplierId = $supplier->id;
    $supplierName = $supplier->name;

    // Get account data BEFORE deleting (important!)
    $relatedAccount = $supplier->account;

    // Soft delete the supplier
    $supplier->delete();

    // Also soft delete the related account if exists and track it
    if ($relatedAccount) {
      $cascadedItems[] = [
        'model' => 'Accounts',
        'id' => $relatedAccount->id,
        'name' => $relatedAccount->name,
      ];

      $relatedAccount->delete();

      // Log the cascade
      $this->trackCascadeDeletion(
        'App\Models\Supplier',
        $supplierId,
        $supplierName,
        'App\Models\Accounts',
        $relatedAccount->id,
        $relatedAccount->name,
        'hasOne',
        'account',
        'soft',
        'Cascade deletion from Supplier deletion'
      );
    }

    // Build cascade message
    $cascadeMessage = '';
    if (!empty($cascadedItems)) {
      $cascadeMessage = ' (Also deleted: ';
      $parts = [];
      foreach ($cascadedItems as $item) {
        $parts[] = "{$item['model']}: {$item['name']}";
      }
      $cascadeMessage .= implode(', ', $parts) . ')';
    }

    return response()->json([
      'message' => 'Supplier moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=suppliers" class="alert-link">View Recycle Bin</a> to restore if needed.'
    ]);
  }

  public function ledger($id, LedgerDataTable $ledgerDataTable)
  {
    if (!auth()->user()->hasPermissionTo('supplier_view')) {
      abort(403, 'Unauthorized action.');
    }

    $supplier = $this->suppliersRepository->find($id);
    if (empty($supplier)) {
      Flash::error('Supplier not found');
      return redirect(route('suppliers.index'));
    }

    if (!$supplier->account_id) {
      Flash::error('Supplier has no associated account_id. Run /suppliers/update-accounts to create accounts.');
      return redirect(route('suppliers.index'));
    }

    $files = Transactions::where('account_id', $supplier->account_id)->get();
    $account_id = $supplier->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])
      ->render('suppliers.ledger', [
        'supplier' => $supplier,
        'files' => $files,
        'dataTable' => $ledgerDataTable
      ]);
  }
  //     public function files($supplier_id, FilesDataTable $filesDataTable)
  //   {
  //     return $filesDataTable->with(['supplier_id' => $supplier_id])->render('suppliers.document');
  //   }

  public function document($supplier_id)
  {
    if (request()->post()) {

      foreach (request('documents') as $document) {

        if ($document['expiry_date']) {
          $data = [];
          if (isset($document['file_name'])) {

            $extension = $document['file_name']->extension();
            $name = $document['type'] . '-' . $supplier_id . '-' . time() . '.' . $extension;
            $document['file_name']->storeAs('supplier', $name);

            $data['file_name'] = $name;
            $data['file_type'] = $extension;
          }

          $data['type_id'] = $supplier_id;  // Link to supplier
          $data['type'] = $document['type'];
          $data['expiry_date'] = $document['expiry_date'];

          $condition = [
            'type' => $document['type'],
            'type_id' => $supplier_id
          ];

          Files::updateOrCreate($condition, $data);
        } else {
          if (isset($document['file_name'])) {
            return response()->json([
              'errors' => [
                'error' => General::file_types($document['type']) . ' expiry date must be selected.'
              ]
            ], 422);
          }
        }
      }

      return 1;
    }

    $files = Files::where('type_id', $supplier_id)->get();
    $supplier = Supplier::find($supplier_id);

    return view('suppliers.document', compact('files', 'supplier'));
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return Supplier::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Supplier',
      'display_columns' => ['name', 'email', 'contact_number'],
      'trash_view' => 'suppliers.trash',
      'index_route' => 'suppliers.index',
    ];
  }
}
