<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGaragesRequest;
use App\Http\Requests\UpdateGaragesRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\GaragesRepository;
use App\Support\GlobalAccounts;
use App\Models\Garages;
use App\Models\Accounts;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Support\Facades\DB;

class GaragesController extends AppBaseController
{
  use AppliesModuleTopBarFilters, GlobalPagination;
  /** @var GaragesRepository $garagesRepository*/
  private $garagesRepository;

  public function __construct(GaragesRepository $garagesRepo)
  {
    $this->garagesRepository = $garagesRepo;
  }

  /**
   * Display a listing of the Garages.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('garage_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Garages::query()
      ->orderBy('id', 'desc');
    $this->applyModuleTopBarFilters($query, $request, 'garages');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('contact_person') && !empty($request->contact_person)) {
      $query->where('contact_person', $request->contact_person);
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
      $tableData = view('garages.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('garages.index', array_merge([
      'data' => $data,
    ], $this->moduleTopBarListingData($request, 'garages')));
  }


  /**
   * Show the form for creating a new Garages.
   */
  public function create()
  {
    return view('garages.create');
  }

  /**
   * Store a newly created Garages in storage.
   */
  public function store(CreateGaragesRequest $request)
  {
    $input = $request->all();
    $isInternal = ($input['garage_type'] ?? '') === 'internal';

    if ($isInternal) {
      $parentId = GlobalAccounts::id('GARAGE_ACCOUNT');
      if (! $parentId) {
        return response()->json([
          'message' => 'Chart of accounts is missing the Garage Inventory (Asset) head. Contact ERP Team to Configure it.',
        ], 422);
      }
      $accountType = 'Asset';
    } else {
      $parentId = GlobalAccounts::id('GARAGE_PARENT');
      if (! $parentId) {
        return response()->json([
          'message' => 'Chart of accounts is missing the Garage (Liability) head. Contact ERP Team to Configure it.',
        ], 422);
      }
      $accountType = 'Liability';
    }

    DB::beginTransaction();
    try {
      // Create Garage
      $garages = $this->garagesRepository->create($input);
      $account = new Accounts();
      $account->name = $garages->name;
      $account->account_type = $accountType;
      $account->parent_id = $parentId;
      $account->ref_name = 'Garage';
      $account->ref_id = $garages->id;
      $account->status = 1;
      $account->account_code = 'GAR-' . str_pad($garages->id, 5, '0', STR_PAD_LEFT);
      $account->branch_id = $garages->branch_id;
      $account->save();
      $garages->update([
        'account_id' => $account->id
      ]);
      DB::commit();
      return response()->json(['message' => 'Garage and account added successfully.']);
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error($e);
      return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
  }


  /**
   * Display the specified Garages.
   */
  public function show($company_slug, $id)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      Flash::error('Garages not found');

      return redirect(route('garages.index'));
    }

    return view('garages.show')->with('garages', $garages);
  }

  /**
   * Show the form for editing the specified Garages.
   */
  public function edit($company_slug, $id)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      Flash::error('Garages not found');

      return redirect(route('garages.index'));
    }

    return view('garages.edit')->with('garages', $garages);
  }

  /**
   * Update the specified Garages in storage.
   */
  public function update($company_slug, $id, UpdateGaragesRequest $request)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      return response()->json(['errors' => ['error' => 'Garage not found!']], 422);
    }

    $garages = $this->garagesRepository->update($request->except(['garage_type']), $id);

    return response()->json(['message' => 'Garage updated successfully.', 'reload' => true]);
  }

  /**
   * Remove the specified Garages from storage.
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      return response()->json(['errors' => ['error' => 'Garage not found!']], 422);
    }

    $this->garagesRepository->delete($id);

    return response()->json(['message' => 'Garage deleted successfully.']);
  }
}
