<?php

namespace App\Http\Controllers;

use App\DataTables\LeasingCompaniesDataTable;
use App\Helpers\Account;
use App\Http\Requests\CreateLeasingCompaniesRequest;
use App\Http\Requests\UpdateLeasingCompaniesRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\LeasingCompanies;
use App\Repositories\LeasingCompaniesRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;

class LeasingCompaniesController extends AppBaseController
{
    use GlobalPagination;
  /** @var LeasingCompaniesRepository $leasingCompaniesRepository*/
  private $leasingCompaniesRepository;

  public function __construct(LeasingCompaniesRepository $leasingCompaniesRepo)
  {
    $this->leasingCompaniesRepository = $leasingCompaniesRepo;
  }

  /**
   * Display a listing of the LeasingCompanies.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('leasing_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = LeasingCompanies::query()
        ->orderBy('id', 'desc');
    if ($request->has('name') && !empty($request->name)) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('contact_person') && !empty($request->contact_person)) {
        $query->where('contact_person',$request->contact_person);
    }
    if ($request->has('status') && !empty($request->status)) {
        $query->where('status',$request->status);
    }
    // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
        $tableData = view('leasing_companies.table', [
            'data' => $data,
        ])->render();
        $paginationLinks = $data->links('components.global-pagination')->render();
        return response()->json([
            'tableData' => $tableData,
            'paginationLinks' => $paginationLinks,
        ]);
    }
    return view('leasing_companies.index', [
        'data' => $data,
    ]);
  }


  /**
   * Show the form for creating a new LeasingCompanies.
   */
  public function create()
  {
    return view('leasing_companies.create');
  }

  /**
   * Store a newly created LeasingCompanies in storage.
   */
  public function store(CreateLeasingCompaniesRequest $request)
  {
    $input = $request->all();

    $leasingCompanies = $this->leasingCompaniesRepository->create($input);


    //Adding Account and setting reference

    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Leasing Companies', 'account_type' => 'Liability', 'parent_id' => null],
      ['name' => 'Leasing Companies', 'account_type' => 'Liability', 'account_code' => Account::code()]
    );

    $account = new Accounts();
    $account->account_code = 'LC' . str_pad($leasingCompanies->id, 4, "0", STR_PAD_LEFT);
    $account->account_type = 'Liability';
    $account->name = $leasingCompanies->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'LeasingCompany';
    $account->ref_id = $leasingCompanies->id;
    $account->status = $leasingCompanies->status;
    $account->save();

    $leasingCompanies->account_id = $account->id;
    $leasingCompanies->save();

    return response()->json(['message' => 'Company added successfully.']);

  }

  /**
   * Display the specified LeasingCompanies.
   */
  public function show($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      Flash::error('Leasing Companies not found');

      return redirect(route('leasingCompanies.index'));
    }

    return view('leasing_companies.show')->with('leasingCompanies', $leasingCompanies);
  }

  /**
   * Show the form for editing the specified LeasingCompanies.
   */
  public function edit($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      Flash::error('Leasing Companies not found');

      return redirect(route('leasingCompanies.index'));
    }

    return view('leasing_companies.edit')->with('leasingCompanies', $leasingCompanies);
  }

  /**
   * Update the specified LeasingCompanies in storage.
   */
  public function update($id, UpdateLeasingCompaniesRequest $request)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      return response()->json(['errors' => ['error' => 'Company not found!']], 422);

    }

    $leasingCompanies = $this->leasingCompaniesRepository->update($request->all(), $id);

    $leasingCompanies->account->name = $leasingCompanies->name;
    $leasingCompanies->account->status = $leasingCompanies->status;
    $leasingCompanies->account->save();


    return response()->json(['message' => 'Company updated successfully.']);

  }

  /**
   * Remove the specified LeasingCompanies from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      return response()->json(['errors' => ['error' => 'Company not found!']], 422);

    }

    if ($leasingCompanies->transactions->count() > 0) {
      return response()->json(['errors' => ['error' => 'Company have transactions.!']], 422);
    } else {
      $leasingCompanies->account->delete();
    }


    $this->leasingCompaniesRepository->delete($id);

    return response()->json(['message' => 'Company deleted successfully.']);

  }
}
