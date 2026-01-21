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
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;

class LeasingCompaniesController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
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
      $query->where('contact_person', $request->contact_person);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
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

    $parentAccount = Accounts::where('name', 'Leasing Companies')->where('account_type', 'Liability')->where('parent_id', null)->first();
    if (!$parentAccount) {
      Flash::error('Parent account "Leasing Companies" not found.');
    }

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
   * Remove the specified LeasingCompanies from storage (soft delete with cascade tracking).
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      return response()->json(['errors' => ['error' => 'Company not found!']], 422);
    }

    // Check if leasing company has transactions - protect from deletion
    $transactionCount = $leasingCompanies->transactions()->count();
    if ($transactionCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $transactionCount . ' transaction(s). Please deactivate instead.']], 422);
    }

    // Check if leasing company has assigned bikes - protect from deletion
    $bikeCount = $leasingCompanies->bikes()->count();
    if ($bikeCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $bikeCount . ' assigned bike(s). Please deactivate instead.']], 422);
    }

    // Check if leasing company has related vouchers - protect from deletion
    $voucherCount = $leasingCompanies->vouchers()->count();
    if ($voucherCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $voucherCount . ' voucher(s). Please deactivate instead.']], 422);
    }

    // Track cascaded deletions
    $cascadedItems = [];
    $leasingCompanyId = $leasingCompanies->id;
    $leasingCompanyName = $leasingCompanies->name;

    // Get related account BEFORE deleting (important!)
    $relatedAccount = $leasingCompanies->account;

    // Soft delete the leasing company
    $leasingCompanies->delete();

    // Track and soft delete the related account if exists
    if ($relatedAccount) {
      $cascadedItems[] = [
        'model' => 'Accounts',
        'id' => $relatedAccount->id,
        'name' => $relatedAccount->name,
      ];

      $relatedAccount->delete();

      // Log the cascade deletion
      $this->trackCascadeDeletion(
        'App\Models\LeasingCompanies',
        $leasingCompanyId,
        $leasingCompanyName,
        'App\Models\Accounts',
        $relatedAccount->id,
        $relatedAccount->name,
        'hasOne',
        'account',
        'soft',
        'Cascade deletion from Leasing Company deletion'
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

    return response()->json([
      'message' => 'Leasing company moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=leasing_companies" class="alert-link">View Recycle Bin</a> to restore if needed.'
    ]);
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return LeasingCompanies::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Leasing Company',
      'display_columns' => ['name', 'contact_person', 'contact_number'],
      'trash_view' => 'leasing_companies.trash',
      'index_route' => 'leasingCompanies.index',
    ];
  }
}
