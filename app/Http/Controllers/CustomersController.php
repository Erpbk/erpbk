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
use App\Models\CustomerInvoices;
use App\Models\Customers;
use App\Models\RiderInventoryAssignment;
use App\Models\Transactions;
use App\Models\Files;
use App\Models\Payment;
use App\Models\Receipt;
use App\Repositories\CustomersRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;
use App\Support\TopBarNumericStatus;

class CustomersController extends AppBaseController
{
  use AppliesModuleTopBarFilters, GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
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

    if (! \App\Support\RoleFieldAccess::canAccessModule('customer')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Customers::query()
      ->orderBy('id', 'asc');
    $this->applyModuleTopBarFilters($query, $request, 'customers');
    if ($request->has('company_name') && !empty($request->company_name)) {
      $query->where('company_name', $request->company_name);
    }
    $listStatusKeys = TopBarNumericStatus::normalizeStatusKeys($request->input('list_status'));
    if ($listStatusKeys !== []) {
      TopBarNumericStatus::applyActiveInactiveOrGroup($query, 'customers.status', $listStatusKeys);
    } elseif ($request->has('status') && !empty($request->status)) {
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
    return view('customers.index', array_merge([
      'data' => $data,
    ], $this->moduleTopBarListingData($request, 'customers')));
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
    $exist = Customers::where('name', $input['name'])->where('branch_id', $input['branch_id'])->first();
    if ($exist) {
      if ($request->ajax()) {
        return response()->json([
          'message' => 'Customer already exists.',
        ], 400);
      }
      Flash::error('Customer already exists.');
      return redirect()->back();
    }
    $parentAccount = \App\Support\GlobalAccounts::id('CUSTOMER_PARENT');
    try {
      $input = \App\Support\RoleFieldAccess::stripNonEditableInput($input, 'customer');
      $customers = $this->customersRepository->create($input);
      $account = new Accounts();
      $account->account_code = 'CS' . str_pad($customers->id, 4, '0', STR_PAD_LEFT);
      $account->account_type = 'Asset';
      $account->name = $customers->name;
      $account->parent_id = $parentAccount;
      $account->ref_name = 'Customer';
      $account->ref_id = $customers->id;
      $account->status = $customers->status;
      $account->branch_id = $customers->branch_id;
      $account->save();

      $customers->account_id = $account->id;
      $customers->save();
      if ($request->ajax()) {
        return response()->json([
          'mesaage' => 'Customer Added Successfully.',
          'reload' => true
        ], 200);
      }
      Flash::success('Customer added successfully.');
      return redirect(route('customers.index'));
    } catch (\Exception $e) {
      \Log::error('error creating customer: ' . $e->getMessage());
      if ($request->ajax()) {
        return response()->json([
          'mesaage' => 'Error: ' . $e->getMessage(),
        ], 500);
      }
      Flash::error('Error creating customer: ' . $e->getMessage());
      return redirect(route('customers.index'));
    }
  }

  /**
   * Display the specified Customers.
   */
  public function show($company_slug, $id)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found');

      return redirect(route('customers.index'));
    }

    return view('customers.show', [
      'customers' => $customers,
      'customer' => $customers,
    ]);
  }

  /**
   * Show the form for editing the specified Customers.
   */
  public function edit($company_slug, $id)
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
  public function update($company_slug, $id, UpdateCustomersRequest $request)
  {
    $customers = $this->customersRepository->find($id);

    if (empty($customers)) {
      Flash::error('Customer not found!');
      return redirect(route('customers.index'));
    }

    $customers = $this->customersRepository->update(
      \App\Support\RoleFieldAccess::stripNonEditableInput($request->all(), 'customer'),
      $id
    );

    // Update linked account safely via relation model instance.
    $account = $customers->account;
    if ($account) {
      $account->status = $customers->status;
      $account->branch_id = $customers->branch_id;
      $account->save();
    }

    Flash::success('Customer updated successfully.');
    return redirect(route('customers.index'));
  }

  /**
   * Remove the specified Customers from storage (soft delete with cascade tracking).
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
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

    Flash::success('Customer moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=customers" class="alert-link">View Recycle Bin</a> to restore if needed.');
    return redirect(route('customers.index'));
  }

  public function ledger($company_slug, $id, LedgerDataTable $ledgerDataTable)
  {
    $customer = Customers::find($id);
    if (!$customer) {
      Flash::error('Customer not found');
      return redirect(route('customers.index'));
    }

    if (empty($customer->account_id)) {
      Flash::error('Customer account not linked. Please assign an account first.');
      return redirect(route('customers.show', $customer->id));
    }

    $details = $this->getDetails($customer->account_id);
    $account_id = $customer->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('customers.customer_ledger', compact('customer', 'details'));
  }

  public function files($company_slug, $id, FilesDataTable $filesDataTable)
  {
    $customer = Customers::find($id);
    if (!$customer) {
      Flash::error('Customer not found');
      return redirect(route('customers.index'));
    }
    $files = Files::where(['type' => 'customer', 'type_id' => $id])->latest('id')->get();
    $details = $this->getDetails($customer->account_id);
    return view('customers.document', compact('files', 'details', 'customer'));
  }

  public function payments(Request $request, $company_slug, $id)
  {
    $customer = Customers::find($id);

    if (empty($customer)) {
      Flash::error('Customer not found');
      return redirect(route('customers.index'));
    }

    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Payment::query()->latest('date_of_payment');
    $query->where('payee_account_id', $customer->account_id);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('customers.payments', compact('data', 'customer', 'details'));
  }

  public function receipts(Request $request, $company_slug, $id)
  {
    $customer = Customers::find($id);

    if (empty($customer)) {
      Flash::error('Customer not found');
      return redirect(route('customers.index'));
    }

    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Receipt::query()->latest('date_of_receipt')->with('payerAccount', 'payeeAccount');
    $query->where('payer_account_id', $customer->account_id);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('customers.receipts', compact('data', 'customer', 'details'));
  }

  public function cReceipts(Request $request)
  {
    $account_ids = Customers::all()->pluck('account_id')->toArray();
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Receipt::query()->latest('date_of_receipt');
    $query->whereIn('payer_account_id', $account_ids);
    $data = $this->applyPagination($query, $paginationParams);
    return view('customers.receipt', compact('data'));
  }

  public function invoices(Request $request, $company_slug, $id)
  {
    $customer = Customers::find($id);
    if (!$customer) {
      Flash::error('customer not found');
      return redirect()->back();
    }
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = CustomerInvoices::query()->latest('billing_month')->where('customer_id', $id);
    $invoices = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('customers.invoice', compact('invoices', 'customer', 'details'));
  }

  public function inventory($company_slug, $id)
  {
    if (! \App\Support\RoleFieldAccess::canAccessModule('riderinventory') && ! \App\Support\RoleFieldAccess::canAccessModule('customer')) {
      abort(403, 'Unauthorized action.');
    }

    $customer = Customers::find($id);
    if (!$customer) {
      Flash::error('Customer not found');
      return redirect(route('customers.index'));
    }

    $assignments = RiderInventoryAssignment::query()
      ->with(['rider', 'inventoryItem', 'assignedByUser', 'returnedByUser'])
      ->where('customer_id', $customer->id)
      ->orderByDesc('assigned_date')
      ->orderByDesc('id')
      ->get();

    $details = $this->getDetails($customer->account_id);

    return view('customers.inventory', compact('customer', 'assignments', 'details'));
  }

  private function getDetails($accountId)
  {
    $currentMonthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $currentMonthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
    $transactions = Transactions::where('account_id', $accountId)->get();
    $currentMonthCredit = $transactions->whereBetween('trans_date', [$currentMonthStart, $currentMonthEnd])->sum('credit');
    $currentMonthDebit = $transactions->whereBetween('trans_date', [$currentMonthStart, $currentMonthEnd])->sum('debit');
    $netFlow = $currentMonthDebit - $currentMonthCredit;

    // Calculate balance
    $credit = $transactions->sum('credit');
    $debit = $transactions->sum('debit');
    $balance = $debit - $credit;
    return [
      'currentMonthCredit' => $currentMonthCredit,
      'currentMonthDebit' => $currentMonthDebit,
      'netFlow' => $netFlow,
      'credit' => $credit,
      'debit' => $debit,
      'balance' => $balance
    ];
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
