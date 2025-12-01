<?php

namespace App\Http\Controllers;

use App\DataTables\AccountsDataTable;
use App\Helpers\IConstants;
use App\Http\Requests\CreateAccountsRequest;
use App\Http\Requests\UpdateAccountsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Customers;
use App\Models\LeasingCompanies;
use App\Models\Riders;
use App\Models\Transactions;
use App\Repositories\AccountsRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;

class AccountsController extends AppBaseController
{
  use GlobalPagination;
  /** @var AccountsRepository $accountsRepository*/
  private $accountsRepository;

  public function __construct(AccountsRepository $accountsRepo)
  {
    $this->accountsRepository = $accountsRepo;
  }

  /**
   * Display a listing of the Accounts.
   */

  public function index(AccountsDataTable $accountsDataTable)
  {

    if (!auth()->user()->hasPermissionTo('account_view')) {
      abort(403, 'Unauthorized action.');
    }
    return $accountsDataTable->render('accounts.index');
  }
  public function tree(AccountsDataTable $accountsDataTable)
  {
    //return $accountsDataTable->render('accounts.index');
    $accounts = Accounts::whereNull('parent_id')->get();
    return view('accounts.tree', compact('accounts'));
  }


  /**
   * Show the form for creating a new Accounts.
   */
  public function create()
  {
    //$parents = Accounts::whereNull('parent_account_id')->pluck('account_name', 'id')->prepend('Select', null);
    //$parents = Accounts::with('children')->whereNull('parent_account_id')->get();
    $parents = Accounts::all(['id', 'name', 'parent_id'])->groupBy('parent_id');

    return view('accounts.create', compact('parents'));
  }

  /**
   * Store a newly created Accounts in storage.
   */
  public function store(CreateAccountsRequest $request)
  {
    $input = $request->all();
    // Set is_locked=1 if parent_id is not set (root account)


    $accounts = $this->accountsRepository->create($input);
    $accounts->account_code = str_pad($accounts->id, 4, "0", STR_PAD_LEFT);
    $accounts->is_locked = 0;
    $accounts->save();

    return response()->json(['message' => 'Account added successfully.']);
  }

  /**
   * Display the specified Accounts.
   */
  public function show($id)
  {
    $accounts = $this->accountsRepository->find($id);

    if (empty($accounts)) {
      Flash::error('Accounts not found');

      return redirect(route('accounts.index'));
    }

    return view('accounts.show')->with('accounts', $accounts);
  }

  /**
   * Show the form for editing the specified Accounts.
   */
  public function edit($id)
  {
    $accounts = $this->accountsRepository->find($id);

    if (empty($accounts)) {
      Flash::error('Accounts not found');

      return redirect(route('accounts.index'));
    }
    //$parents = Accounts::whereNot('id', $id)->whereNull('parent_account_id')->pluck('account_name', 'id')->prepend('Select', null);
    $parents = Accounts::all(['id', 'name', 'parent_id'])->groupBy('parent_id');
    return view('accounts.edit', compact('accounts', 'parents'));
  }

  /**
   * Update the specified Accounts in storage.
   */
  public function update($id, UpdateAccountsRequest $request)
  {
    $accounts = $this->accountsRepository->find($id);

    if (empty($accounts)) {
      Flash::error('Accounts not found');

      return redirect(route('accounts.index'));
    }

    $accounts = $this->accountsRepository->update($request->all(), $id);

    if ($accounts) {
      $row = \App\Helpers\Accounts::getRef(['ref_name' => $accounts->ref_name, 'ref_id' => $accounts->ref_id]);
      if (isset($row)) {
        $row->name = $accounts->name;
        $row->account_code = $accounts->account_code;
        $row->status = $accounts->status;
        $row->save();
      }
    }



    return response()->json(['message' => 'Account updated successfully.']);
  }

  /**
   * Remove the specified Accounts from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $accounts = $this->accountsRepository->find($id);
    $isParent = Accounts::where('parent_id', $accounts->id)->count();
    $Transactions = Transactions::where('account_id', $accounts->id)->count();
    if ($isParent > 0) {
      return response()->json(['errors' => ['error' => 'This account have sub accounts.']], 422);
    }
    if ($Transactions > 0) {
      return response()->json(['errors' => ['error' => 'This account have transactions.']], 422);
    }
    if (empty($accounts)) {
      Flash::error('Accounts not found');
      return redirect(route('accounts.index'));
    }
    if ($accounts) {
      $row = \App\Helpers\Accounts::getRef(['ref_name' => $accounts->ref_name, 'ref_id' => $accounts->ref_id]);
      if (isset($row)) {
        $row->delete();
      }
    }
    $this->accountsRepository->delete($id);

    return response()->json(['message' => 'Account deleted successfully.']);
  }

  /**
   * Toggle lock status for an account (AJAX)
   */
  public function toggleLock(Request $request, $id)
  {
    $account = Accounts::findOrFail($id);
    $account->is_locked = !$account->is_locked;
    $account->save();
    return response()->json([
      'success' => true,
      'is_locked' => $account->is_locked,
      'icon' => $account->is_locked ? 'fa-lock' : 'fa-unlock',
      'icon_class' => $account->is_locked ? 'text-secondary' : 'text-success',
      'title' => $account->is_locked ? 'Parent account is locked' : 'Unlocked'
    ]);
  }

  /**
   * Get head accounts by account type (AJAX)
   */
  public function getHeadAccountsByType($type)
  {
    $accounts = Accounts::whereNull('parent_id')->where('account_type', $type)->pluck('name', 'id');
    return response()->json($accounts);
  }
}
