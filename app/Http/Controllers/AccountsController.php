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
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Support\Facades\DB;
use Flash;

class AccountsController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
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
   * Remove the specified Accounts from storage (soft delete with protection).
   * Accounts with transactions CANNOT be deleted - must be protected.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $accounts = $this->accountsRepository->find($id);

    if (empty($accounts)) {
      return response()->json(['errors' => ['error' => 'Account not found!']], 422);
    }

    // Check if account is a parent (has child accounts)
    $childAccountsCount = Accounts::where('parent_id', $accounts->id)->count();
    if ($childAccountsCount > 0) {
      return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$childAccountsCount} sub-account(s). Please delete or reassign child accounts first."]], 422);
    }

    // CRITICAL: Check if account has transactions - MUST PROTECT
    $transactionsCount = Transactions::where('account_id', $accounts->id)->count();
    if ($transactionsCount > 0) {
      return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$transactionsCount} transaction(s). Accounts with transactions cannot be deleted to maintain data integrity."]], 422);
    }

    // Check if account has ledger entries
    $ledgerEntriesCount = DB::table('ledger_entries')
      ->where('account_id', $accounts->id)
      ->count();
    if ($ledgerEntriesCount > 0) {
      return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$ledgerEntriesCount} ledger entry(ies). Please clear these first."]], 422);
    }

    // Track cascaded deletions for referenced records
    $cascadedItems = [];

    // Get the referenced record (e.g., Bank, Customer, Vendor) if it exists
    if ($accounts->ref_name && $accounts->ref_id) {
      $referencedRecord = \App\Helpers\Accounts::getRef(['ref_name' => $accounts->ref_name, 'ref_id' => $accounts->ref_id]);
      
      if ($referencedRecord) {
        // Store info before deletion
        $cascadedItems[] = [
          'model' => $accounts->ref_name,
          'id' => $referencedRecord->id,
          'name' => $referencedRecord->name ?? "ID: {$referencedRecord->id}",
        ];

        // Soft delete the referenced record
        $referencedRecord->delete();

        // Log the cascade (reverse direction - account deletion causes ref deletion)
        $this->trackCascadeDeletion(
          'App\Models\Accounts',
          $accounts->id,
          $accounts->name,
          get_class($referencedRecord),
          $referencedRecord->id,
          $referencedRecord->name ?? "ID: {$referencedRecord->id}",
          'belongsTo',
          'ref_record',
          'soft'
        );
      }
    }

    // Soft delete the account
    $accounts->delete();

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
      'message' => 'Account moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=accounts" class="alert-link">View Recycle Bin</a> to restore if needed.'
    ]);
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

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return Accounts::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Account',
      'display_columns' => ['account_code', 'name', 'account_type'],
      'trash_view' => 'accounts.trash',
      'index_route' => 'accounts.index',
    ];
  }
}
