<?php

namespace App\Http\Controllers;

use App\DataTables\FilesDataTable;
use App\DataTables\LedgerDataTable;
use App\Helpers\Account;
use App\Helpers\General;
use App\Http\Requests\CreateBanksRequest;
use App\Http\Requests\UpdateBanksRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Cheques;
use App\Models\Files;
use App\Models\Transactions;
use App\Repositories\BanksRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Receipt;
use App\Models\Payment;


class BanksController extends AppBaseController
{
  use GlobalPagination, TracksCascadingDeletions;
  /** @var BanksRepository $banksRepository*/
  private $banksRepository;

  public function __construct(BanksRepository $banksRepo)
  {
    $this->banksRepository = $banksRepo;
    $this->middleware('permission:cash_&_banks_banks_view')->only('index', 'show', 'ledger', 'files', 'receipts', 'payments', 'cheques');
    $this->middleware('permission:cash_&_banks_banks_create')->only('create', 'store');
    $this->middleware('permission:cash_&_banks_banks_edit')->only('edit', 'update');
    $this->middleware('permission:cash_&_banks_banks_delete')->only('destroy');
  }

  /**
   * Display a listing of the Banks.
   */
  public function index(Request $request)
  {

    $fundIn = 0;
    $fundOut = 0;
    $banks = Banks::all();
    foreach ($banks as $bank) {
      $credit = Transactions::where('account_id', $bank->account_id)->sum('credit');
      $debit  = Transactions::where('account_id', $bank->account_id)->sum('debit');
      $balance = $debit - $credit;
      $fundIn += $debit;
      $fundOut += $credit;
      $bank->update(['balance' => $balance]);
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Banks::query()
      ->with('branch')
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
    if ($request->has('branch_id') && !empty($request->branch_id)) {
      $query->where('branch_id', $request->branch_id);
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
      'fundsIn' => $fundIn,
      'fundsOut' => $fundOut
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

    try {
      DB::beginTransaction();
      $banks = $this->banksRepository->create($input);

      //Adding Account and setting reference
      $parentId = \App\Support\GlobalAccounts::id('BANK');
      $account = new Accounts();
      $account->account_code = 'BK' . str_pad($banks->id, 4, "0", STR_PAD_LEFT);
      $account->account_type = 'Asset';
      $account->name = $banks->name;
      $account->parent_id = $parentId;
      $account->ref_name = 'Bank';
      $account->ref_id = $banks->id;
      $account->status = $banks->status;
      $account->branch_id = $banks->branch_id;
      $account->save();
      $banks->account_id = $account->id;
      $banks->save();
      DB::commit();
      if ($request->ajax()) {
        return response()->json([
          'message' => 'Bank Account Added Successfully',
          'reload' => true
        ], 200);
      }
      Flash::success('Bank added successfully.');
      return redirect()->back();
    } catch (\Exception $e) {
      \Log::error('error occured while creating bank account : ' . $e->getMessage());
      DB::rollBack();
      if ($request->ajax()) {
        return response()->json([
          'message' => 'Error: ' . $e->getMessage(),
        ], 500);
      }
      Flash::error('Error: ' . $e->getMessage());
      return redirect()->back();
    }
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
  public function edit($company_slug, $id)
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
  public function update($company_slug, $id, UpdateBanksRequest $request)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      if ($request->ajax()) {
        return response()->json(['message' => 'Bank not found!'], 404);
      }
      Flash::error('Bank not found!');
      return redirect()->back();
    }

    $banks = $this->banksRepository->update($request->all(), $id);
    if ($banks->account) {
      $banks->account->status = $banks->status;
      $banks->account->save();
    }

    if ($request->ajax()) {
      return response()->json([
        'message' => 'Bank updated successfully.',
        'reload' => true,
      ], 200);
    }

    Flash::success('Bank updated successfully.');
    return redirect()->back();
  }

  /**
   * Remove the specified Banks from storage (soft delete).
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
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

    Flash::success('Bank moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=banks" class="alert-link">View Recycle Bin</a> to restore if needed.')->important();
    return redirect(route('banks.index'));
  }

  public function ledger($company_slug, $id, LedgerDataTable $ledgerDataTable)
  {
    $banks = Banks::find($id);
    $files = Transactions::where('account_id', $banks->account_id)->get();
    $account_id = $banks->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('banks.bank_ledger', compact('files', 'banks'));
  }

  public function files($company_slug, $id, FilesDataTable $filesDataTable)
  {
    $files = \App\Support\CompanyQuery::table('files')->where('type', 'bank')->where('type_id', $id)->latest('id')->get();
    $banks = Banks::find($id);
    return view('banks.document', compact('files', 'banks'));
  }

  public function receipts(Request $request, $company_slug, $id)
  {
    $banks = Banks::find($id);
    $fundIn = 0;
    $fundOut = 0;
    $credit = Transactions::where('account_id', $banks->account_id)->sum('credit');
    $debit  = Transactions::where('account_id', $banks->account_id)->sum('debit');
    $balance = $debit - $credit;
    $fundIn += $debit;
    $fundOut += $credit;
    $banks->update(['balance' => $balance]);
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Receipt::query()->latest('id');
    $query->where('bank_id', $banks->id);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    return view('banks.receipts', ['data' => $data, 'banks' => $banks, 'fundsIn' => $fundIn, 'fundsOut' => $fundOut]);
  }

  public function payments(Request $request, $company_slug, $id)
  {
    $banks = Banks::find($id);
    $fundIn = 0;
    $fundOut = 0;
    $credit = Transactions::where('account_id', $banks->account_id)->sum('credit');
    $debit  = Transactions::where('account_id', $banks->account_id)->sum('debit');
    $balance = $debit - $credit;
    $fundIn += $debit;
    $fundOut += $credit;
    $banks->update(['balance' => $balance]);
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Payment::query()->latest('id');
    $query->where('bank_id', $id);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    return view('banks.payments', ['data' => $data, 'banks' => $banks, 'fundsIn' => $fundIn, 'fundsOut' => $fundOut]);
  }

  public function cheques(Request $request, $company_slug, $id)
  {
    $banks = Banks::find($id);
    $query = Cheques::query()->latest('issue_date');
    $query->where('bank_id', $id);

    // Apply pagination using the trait
    $data = $query->get();
    return view('banks.cheques', compact('data', 'banks'));
  }
}
