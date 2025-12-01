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
use Flash;

class BanksController extends AppBaseController
{
    use GlobalPagination;
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
   * Remove the specified Banks from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $id = (int) $id;
    $banks = $this->banksRepository->find($id);

    if (empty($banks)) {
      Flash::error('Bank not found!');
    }


    if ($banks->transactions->count() > 0) {
      Flash::error('Bank have transactions!');
    } else {

      if ($banks->account) {
        $banks->account->delete();
      }
      $this->banksRepository->delete($id);
      Flash::success('Bank deleted successfully.');
    }
    return redirect(route('banks.index'));
  }
  public function ledger($id, LedgerDataTable $ledgerDataTable)
  {
    $banks = Banks::find($id);
    $files = Transactions::where('account_id', $banks->account_id)->get();
    $account_id = $banks->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('banks.bank_ledger', compact('files', 'banks'));
  }

  public function files($id, FilesDataTable $filesDataTable)
  {
    return $filesDataTable->with(['type_id' => $id, 'type' => 'bank'])->render('banks.document');
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
