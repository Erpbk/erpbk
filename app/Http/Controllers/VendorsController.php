<?php

namespace App\Http\Controllers;

use App\DataTables\VendorsDataTable;
use App\Helpers\Account;
use App\Http\Requests\CreateVendorsRequest;
use App\Http\Requests\UpdateVendorsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\vendors;
use App\Repositories\VendorsRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;

class VendorsController extends AppBaseController
{
  use GlobalPagination;
  /** @var VendorsRepository $vendorsRepository*/
  private $vendorsRepository;

  public function __construct(VendorsRepository $vendorsRepo)
  {
    $this->vendorsRepository = $vendorsRepo;
  }

  /**
   * Display a listing of the Vendors.
   */
  public function index(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = vendors::query()
      ->orderBy('id', 'desc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
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
      $tableData = view('vendors.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('vendors.index', [
      'data' => $data,
    ]);
    return $vendorsDataTable->render('vendors.index');
  }


  /**
   * Show the form for creating a new Vendors.
   */
  public function create()
  {
    return view('vendors.create');
  }

  /**
   * Store a newly created Vendors in storage.
   */
  public function store(CreateVendorsRequest $request)
  {
    $input = $request->all();

    $vendor = $this->vendorsRepository->create($input);

    //Adding Account and setting reference

    $parentAccount = Accounts::firstOrCreate(
      ['name' => 'Vendor', 'account_type' => 'Liability', 'parent_id' => null],
      ['name' => 'Vendor', 'account_type' => 'Liability', 'account_code' => Account::code()]
    );

    $account = new Accounts();
    $account->account_code = 'VD' . str_pad($vendor->id, 4, "0", STR_PAD_LEFT);
    $account->account_type = 'Liability';
    $account->name = $vendor->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'Vendor';
    $account->ref_id = $vendor->id;
    $account->status = $vendor->status;
    $account->save();

    $vendor->account_id = $account->id;
    $vendor->save();

    return response()->json(['message' => 'Vendor added successfully.']);
  }

  /**
   * Display the specified Vendors.
   */
  public function show($id)
  {
    $vendors = $this->vendorsRepository->find($id);

    if (empty($vendors)) {
      Flash::error('Vendors not found');

      return redirect(route('vendors.index'));
    }

    return view('vendors.show')->with('vendors', $vendors);
  }

  /**
   * Show the form for editing the specified Vendors.
   */
  public function edit($id)
  {
    $vendors = $this->vendorsRepository->find($id);

    if (empty($vendors)) {
      Flash::error('Vendors not found');

      return redirect(route('vendors.index'));
    }

    return view('vendors.edit')->with('vendors', $vendors);
  }

  /**
   * Update the specified Vendors in storage.
   */
  public function update($id, UpdateVendorsRequest $request)
  {
    $vendor = $this->vendorsRepository->find($id);

    if (empty($vendor)) {

      return response()->json(['errors' => ['error' => 'Vendor not found!']], 422);
    }

    $vendor = $this->vendorsRepository->update($request->all(), $id);
    $vendor->account->status = $vendor->status;
    $vendor->save();

    return response()->json(['message' => 'Vendor updated successfully.']);
  }

  /**
   * Remove the specified Vendors from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $vendor = $this->vendorsRepository->find($id);

    if (empty($vendor)) {
      return response()->json(['errors' => ['error' => 'Vendor not found!']], 422);
    }


    if ($vendor->transactions->count() > 0) {
      return response()->json(['errors' => ['error' => 'Vendor have transactions!']], 422);
    } else {

      if ($vendor->account) {
        $vendor->account->delete();
      }
      $this->vendorsRepository->delete($id);
    }


    return response()->json(['message' => 'Vendor deleted successfully.']);
  }
}
