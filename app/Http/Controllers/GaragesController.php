<?php

namespace App\Http\Controllers;

use App\DataTables\GaragesDataTable;
use App\Http\Requests\CreateGaragesRequest;
use App\Http\Requests\UpdateGaragesRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\GaragesRepository;
use App\Models\Garages;
use App\Models\Accounts;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Support\Facades\DB;

class GaragesController extends AppBaseController
{
    use GlobalPagination;
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
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('contact_person') && !empty($request->contact_person)) {
      $query->where('contact_person', $request->contact_person);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
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
    return view('garages.index', [
      'data' => $data,
    ]);
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
    DB::beginTransaction();
    try{
      // Create Garage
      $garages = $this->garagesRepository->create($input);
      $account = new Accounts();
      $account->name = $garages->name;
      $account->account_type = 'Liability';
      $account->parent_id = 1664; // fixed parent
      $account->ref_name = 'Garage';
      $account->ref_id = $garages->id;
      $account->status = 1;
      $account->account_code = 'GAR-' . str_pad($garages->id, 5, '0', STR_PAD_LEFT);
      $account->save();
      $garages->update([
          'account_id' => $account->id
      ]);
      DB::commit();
      return response()->json(['message' => 'Garage and account added successfully.']);
    }catch(\Exception $e){
      DB::rollBack();
      \Log::error($e);
      return response()->json(['message' => 'Error: '.$e->getMessage()],500);
    }
  }


  /**
   * Display the specified Garages.
   */
  public function show($id)
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
  public function edit($id)
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
  public function update($id, UpdateGaragesRequest $request)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      return response()->json(['errors' => ['error' => 'Garage not found!']], 422);
    }

    $garages = $this->garagesRepository->update($request->all(), $id);

    return response()->json(['message' => 'Garage updated successfully.']);
  }

  /**
   * Remove the specified Garages from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $garages = $this->garagesRepository->find($id);

    if (empty($garages)) {
      return response()->json(['errors' => ['error' => 'Garage not found!']], 422);
    }

    $this->garagesRepository->delete($id);

    return response()->json(['message' => 'Garage deleted successfully.']);
  }
}
