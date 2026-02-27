<?php

namespace App\Http\Controllers;

use App\DataTables\AccountsDataTable;
use App\Helpers\IConstants;
use App\Http\Requests\CreateAccountsRequest;
use App\Http\Requests\UpdateAccountsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\AccountCustomField;
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
   * Display a listing of the Accounts (Chart of Accounts view).
   */
  public function index(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('account_view')) {
      abort(403, 'Unauthorized action.');
    }
    $search = $request->get('search');
    $all = Accounts::orderBy('account_code')->get()->keyBy('id');
    $roots = $all->whereNull('parent_id')->sortBy('account_code')->values();
    foreach ($roots as $r) {
      $r->setRelation('children', $this->buildChildren($r->id, $all));
    }
    $accounts = $this->flattenAccountTree($roots, $search);
    return view('accounts.index', compact('accounts'));
  }

  /**
   * Build children collection for a parent from a keyed collection of all accounts.
   */
  private function buildChildren($parentId, $all)
  {
    return $all->where('parent_id', $parentId)->sortBy('account_code')->values()->map(function ($child) use ($all) {
      $child->setRelation('children', $this->buildChildren($child->id, $all));
      return $child;
    });
  }

  /**
   * Flatten account tree into a list with depth for hierarchical display.
   *
   * @param \Illuminate\Support\Collection $nodes
   * @param string|null $search
   * @param int $depth
   * @return \Illuminate\Support\Collection
   */
  private function flattenAccountTree($nodes, $search = null, $depth = 0)
  {
    $result = collect();
    foreach ($nodes as $account) {
      $match = !$search || stripos($account->name, $search) !== false
        || stripos((string)($account->account_code ?? ''), $search) !== false
        || stripos((string)($account->account_type ?? ''), $search) !== false;
      if ($match) {
        $result->push((object)['account' => $account, 'depth' => $depth]);
      }
      $children = $account->relationLoaded('children') ? $account->children : collect();
      $result = $result->merge($this->flattenAccountTree($children, $search, $depth + 1));
    }
    return $result;
  }
  public function tree(AccountsDataTable $accountsDataTable)
  {
    $accounts = Accounts::with('children')->whereNull('parent_id')->orderBy('account_code')->get();
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
    $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();

    return view('accounts.create', compact('parents', 'customFields'));
  }

  /**
   * Store a newly created Accounts in storage.
   */
  public function store(CreateAccountsRequest $request)
  {
    $input = $request->except(['custom_field_values']);
    // Set is_locked=1 if parent_id is not set (root account)

    $accounts = $this->accountsRepository->create($input);
    $accounts->account_code = str_pad($accounts->id, 4, "0", STR_PAD_LEFT);
    $accounts->is_locked = 0;
    $accounts->save();

    $this->saveCustomFieldValues($accounts, $request->input('custom_field_values', []));

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

    $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();

    return view('accounts.show', compact('accounts', 'customFields'));
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
    $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();

    return view('accounts.edit', compact('accounts', 'parents', 'customFields'));
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

    $input = $request->except(['custom_field_values']);
    $accounts = $this->accountsRepository->update($input, $id);

    if ($accounts) {
      $this->saveCustomFieldValues($accounts, $request->input('custom_field_values', []));
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
   * Save custom field values for an account (only valid field IDs from settings).
   */
  private function saveCustomFieldValues(Accounts $account, array $values): void
  {
    $validIds = AccountCustomField::pluck('id')->flip()->all();
    $filtered = [];
    foreach ($values as $fieldId => $value) {
      if (isset($validIds[$fieldId])) {
        $filtered[(string) $fieldId] = $value;
      }
    }
    $account->custom_field_values = $filtered;
    $account->save();
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
      'message' => 'Account moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=accounts" class="alert-link">View Recycle Bin</a> to restore if needed.'
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
   * Account detail panel (AJAX): ledger summary, closing balance, full ledger (paginated).
   */
  public function accountDetail(Request $request, $id)
  {
    $account = Accounts::findOrFail($id);
    $currency = $request->get('currency', 'bcy');

    $closingBalance = (float) Transactions::where('account_id', $account->id)->sum(DB::raw('debit - credit'));

    $perPage = (int) $request->get('per_page', 25);
    $perPage = max(10, min(100, $perPage));

    $ledgerPaginator = Transactions::where('account_id', $account->id)
      ->with(['voucher'])
      ->orderBy('trans_date', 'desc')
      ->orderBy('id', 'desc')
      ->paginate($perPage, ['*'], 'page', 1);

    $ledgerUrl = route('accounts.ledger') . '?account=' . $account->id;

    $html = view('accounts.detail_panel', compact('account', 'closingBalance', 'ledgerPaginator', 'currency', 'ledgerUrl'))->render();

    if ($request->wantsJson() || $request->ajax()) {
      return response()->json(['html' => $html, 'account_id' => $account->id]);
    }
    return $html;
  }

  /**
   * Ledger entries for an account (AJAX pagination): returns table rows + pagination meta.
   */
  public function ledgerEntries(Request $request, $id)
  {
    $account = Accounts::findOrFail($id);
    $currency = $request->get('currency', 'bcy');
    $perPage = (int) $request->get('per_page', 25);
    $perPage = max(10, min(100, $perPage));
    $page = (int) $request->get('page', 1);

    $paginator = Transactions::where('account_id', $account->id)
      ->with(['voucher'])
      ->orderBy('trans_date', 'desc')
      ->orderBy('id', 'desc')
      ->paginate($perPage, ['*'], 'page', $page);

    $html = view('accounts._ledger_entries_rows', ['transactions' => $paginator->items()])->render();

    return response()->json([
      'html' => $html,
      'pagination' => [
        'current_page' => $paginator->currentPage(),
        'last_page' => $paginator->lastPage(),
        'per_page' => $paginator->perPage(),
        'total' => $paginator->total(),
        'from' => $paginator->firstItem(),
        'to' => $paginator->lastItem(),
      ],
    ]);
  }

  /**
   * Toggle account active/inactive status (AJAX)
   */
  public function toggleStatus(Request $request, $id)
  {
    $account = Accounts::findOrFail($id);
    $account->status = ($account->status == 1) ? 2 : 1;
    $account->save();
    return response()->json([
      'success' => true,
      'status' => $account->status,
      'is_active' => $account->status == 1,
      'message' => $account->status == 1 ? 'Account marked as active.' : 'Account marked as inactive.',
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
