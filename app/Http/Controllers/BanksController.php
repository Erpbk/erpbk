<?php

namespace App\Http\Controllers;

use App\DataTables\BanksDataTable;
use App\DataTables\FilesDataTable;
use App\DataTables\LedgerDataTable;
use App\Helpers\Account;
use App\Helpers\General;
use App\Http\Requests\CreateBanksRequest;
use App\Http\Requests\UpdateBanksRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Files;
use App\Models\Transactions;
use App\Repositories\BanksRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use DB;

class BanksController extends AppBaseController
{
  use GlobalPagination, TracksCascadingDeletions;
  /** @var BanksRepository $banksRepository*/
  private $banksRepository;

  public function __construct(BanksRepository $banksRepo)
  {
    $this->banksRepository = $banksRepo;
  }

  /**
   * Display a listing of the Banks.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('bank_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Banks::query()
      ->orderBy('id', 'asc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('title') && !empty($request->title)) {
      $query->where('title', $request->title);
    }
    if ($request->has('account_no') && !empty($request->account_no)) {
      $query->where('account_no', $request->account_no);
    }
    if ($request->has('account_type') && !empty($request->account_type)) {
      $query->where('account_type', $request->account_type);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('banks.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('banks.index', [
      'data' => $data,
    ]);
  }


  /**
   * Show the form for creating a new Banks.
   */
  public function create()
  {
    return view('banks.create');
  }

  /**
   * Store a newly created Banks in storage.
   */
  public function store(CreateBanksRequest $request)
  {
    $input = $request->all();

    $banks = $this->banksRepository->create($input);

    //Adding Account and setting reference

    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Bank', 'account_type' => 'Asset', 'parent_id' => 1639],
      ['name' => 'Bank', 'account_type' => 'Asset', 'account_code' => Account::code()]
    );

    $account = new Accounts();
    $account->account_code = 'BK' . str_pad($banks->id, 4, "0", STR_PAD_LEFT);
    $account->account_type = 'Asset';
    $account->name = $banks->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'Bank';
    $account->ref_id = $banks->id;
    $account->status = $banks->status;
    $account->save();

    $banks->account_id = $account->id;
    $banks->save();
    Flash::success('Bank added successfully.');
    return redirect()->back();
  }

  /**
   * Display the specified Banks.
   */
  public function show($id)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      Flash::error('Banks not found');

      return redirect(route('banks.index'));
    }

    return view('banks.show')->with('banks', $banks);
  }

  /**
   * Show the form for editing the specified Banks.
   */
  public function edit($id)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      Flash::error('Banks not found');

      return redirect(route('banks.index'));
    }

    return view('banks.edit')->with('banks', $banks);
  }

  /**
   * Update the specified Banks in storage.
   */
  public function update($id, UpdateBanksRequest $request)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      Flash::error('Bank not found!');
    }

    $banks = $this->banksRepository->update($request->all(), $id);
    $banks->account->status = $banks->status;
    $banks->save();

    Flash::success('Bank updated successfully.');
    return redirect()->back();
  }

  /**
   * Remove the specified Banks from storage (soft delete).
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      Flash::error('Bank not found!');
      return redirect(route('banks.index'));
    }

    // Check if bank has transactions
    if ($banks->transactions()->count() > 0) {
      Flash::error('Cannot delete bank. Bank has ' . $banks->transactions()->count() . ' transaction(s). Please deactivate instead.');
      return redirect(route('banks.index'));
    }

    // Track cascaded deletions
    $cascadedItems = [];

    // Get account data BEFORE deleting (important!)
    $relatedAccount = $banks->account;

    // Soft delete the bank
    $banks->delete();

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
        'App\Models\Banks',
        $banks->id,
        $banks->name,
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

    Flash::success('Bank moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=banks" class="alert-link">View Recycle Bin</a> to restore if needed.')->important();
    return redirect(route('banks.index'));
  }

  // ========================================================================
  // DEPRECATED: Old trash methods - now handled by centralized TrashController
  // These methods are kept for backward compatibility but should not be used
  // Use /trash route instead for all trash operations
  // ========================================================================

  /*
  public function trashed(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('bank_view_delete')) {
      abort(403, 'Unauthorized action.');
    }

    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $query = Banks::onlyTrashed()
      ->orderBy('deleted_at', 'desc');

    // Apply same filters as index
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('account_no') && !empty($request->account_no)) {
      $query->where('account_no', $request->account_no);
    }

    $data = $this->applyPagination($query, $paginationParams);

    if ($request->ajax()) {
      $tableData = view('banks.trashed_table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }

    return view('banks.trashed', [
      'data' => $data,
    ]);
  }

  public function restore($id)
  {
    if (!auth()->user()->hasPermissionTo('bank_view_delete')) {
      abort(403, 'Unauthorized action.');
    }

    $bank = Banks::onlyTrashed()->find($id);

    if (empty($bank)) {
      Flash::error('Bank not found in trash!');
      return redirect(route('banks.trashed'));
    }

    // Restore the bank
    $bank->restore();

    // Restore the related account if it was soft deleted
    if ($bank->account && $bank->account->trashed()) {
      $bank->account->restore();
    }

    Flash::success('Bank restored successfully.');
    return redirect(route('banks.index'));
  }

  public function forceDestroy($id)
  {
    if (!auth()->user()->hasPermissionTo('bank_view_delete')) {
      abort(403, 'Unauthorized action.');
    }

    $bank = Banks::onlyTrashed()->find($id);

    if (empty($bank)) {
      Flash::error('Bank not found in trash!');
      return redirect(route('banks.trashed'));
    }

    // Check if bank has any transactions (even soft deleted)
    if ($bank->transactions()->withTrashed()->count() > 0) {
      Flash::error('Cannot permanently delete bank. Bank has transaction history.');
      return redirect(route('banks.trashed'));
    }

    // Permanently delete the related account
    if ($bank->account) {
      $bank->account->forceDelete();
    }

    // Permanently delete the bank
    $bank->forceDelete();

    Flash::success('Bank permanently deleted.');
    return redirect(route('banks.trashed'));
  }
  */
  public function ledger($id, LedgerDataTable $ledgerDataTable)
  {
    $banks = Banks::find($id);
    $files = Transactions::where('account_id', $banks->account_id)->get();
    $account_id = $banks->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('banks.bank_ledger', compact('files', 'banks'));
  }

  public function files($id, FilesDataTable $filesDataTable)
  {
    $files = DB::table('files')->where('type','bank')->where('type_id', $id)->latest('id')->get();
    return view('banks.document', compact('files'));
    // return $filesDataTable->with(['type_id' => $id, 'type' => 'bank'])->render('banks.document');
  }

  public function receipts(Request $request)
  {
    return view('banks.receipts.receipts');
  }

  public function payments(Request $request)
  {
    return view('banks.payments.payments');
  }
}
