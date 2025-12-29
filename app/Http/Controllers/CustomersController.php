<?php

namespace App\Http\Controllers;

use App\DataTables\CustomersDataTable;
use App\DataTables\FilesDataTable;
use App\DataTables\LedgerDataTable;
use App\Helpers\Account;
use App\Http\Requests\CreateCustomersRequest;
use App\Http\Requests\UpdateCustomersRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Customers;
use App\Models\Transactions;
use App\Repositories\CustomersRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;

class CustomersController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
  /** @var CustomersRepository $customersRepository*/
  private $customersRepository;

  public function __construct(CustomersRepository $customersRepo)
  {
    $this->customersRepository = $customersRepo;
  }

  /**
   * Display a listing of the Customers.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('customer_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Customers::query()
      ->orderBy('id', 'asc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('company_name') && !empty($request->company_name)) {
      $query->where('company_name', $request->company_name);
    }
    if ($request->has('account_id') && !empty($request->account_id)) {
      $query->where('account_id', $request->account_id);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('customers.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('customers.index', [
      'data' => $data,
    ]);
  }


  /**
   * Show the form for creating a new Customers.
   */
  public function create()
  {
    return view('customers.create');
  }

  /**
   * Store a newly created Customers in storage.
   */
  public function store(CreateCustomersRequest $request)
  {
    $input = $request->all();

    $customers = $this->customersRepository->create($input);


    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Receivable', 'account_type' => 'Asset', 'parent_id' => null],
      ['name' => 'Receivable', 'account_type' => 'Asset', 'account_code' => Account::code()]
    );

    $account = new Accounts();
    $account->account_code = 'CS' . str_pad($customers->id, 4, '0', STR_PAD_LEFT);
    $account->account_type = 'Asset';
    $account->name = $customers->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'Customer';
    $account->ref_id = $customers->id;
    $account->status = $customers->status;
    $account->save();

    $customers->account_id = $account->id;
    $customers->save();

    Flash::success('Customer added successfully.');
    return redirect(route('customers.index'));
  }

  /**
   * Display the specified Customers.
   */
  public function show($id)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found');

      return redirect(route('customers.index'));
    }

    return view('customers.show')->with('customers', $customers);
  }

  /**
   * Show the form for editing the specified Customers.
   */
  public function edit($id)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found');

      return redirect(route('customers.index'));
    }

    return view('customers.edit')->with('customers', $customers);
  }

  /**
   * Update the specified Customers in storage.
   */
  public function update($id, UpdateCustomersRequest $request)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found!');
      return redirect(route('customers.index'));
    }

    $customers = $this->customersRepository->update($request->all(), $id);

    $customers->account->status = $customers->status;
    $customers->save();

    Flash::success('Customer updated successfully.');
    return redirect(route('customers.index'));
  }

  /**
   * Remove the specified Customers from storage (soft delete with cascade tracking).
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found!');
      return redirect(route('customers.index'));
    }

    // Check if customer has transactions - protect from deletion
    if ($customers->transactions()->count() > 0) {
      Flash::error('Cannot delete customer. Customer has ' . $customers->transactions()->count() . ' transaction(s). Please deactivate instead.');
      return redirect(route('customers.index'));
    }

    // Track cascaded deletions
    $cascadedItems = [];

    // Get account data BEFORE deleting (important!)
    $relatedAccount = $customers->account;

    // Soft delete the customer
    $customers->delete();

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
        'App\Models\Customers',
        $customers->id,
        $customers->name,
        'App\Models\Accounts',
        $relatedAccount->id,
        $relatedAccount->name,
        'hasOne',
        'account',
        'soft'
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

    Flash::success('Customer moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=customers" class="alert-link">View Recycle Bin</a> to restore if needed.');
    return redirect(route('customers.index'));
  }

  public function ledger($id, LedgerDataTable $ledgerDataTable)
  {
    $customers = Customers::find($id);
    $files = Transactions::where('account_id', $customers->account_id)->get();
    $account_id = $customers->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('customers.customer_ledger', compact('files', 'customers'));
  }

  public function files($id, FilesDataTable $filesDataTable)
  {
    return $filesDataTable->with(['type_id' => $id, 'type' => 'customer'])->render('customers.document');
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return Customers::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Customer',
      'display_columns' => ['name', 'company_name', 'contact_number'],
      'trash_view' => 'customers.trash',
      'index_route' => 'customers.index',
    ];
  }
}
