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
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Support\Facades\DB;
use Flash;

class VendorsController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
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

    Flash::success('Vendor added successfully.');
    return redirect(route('vendors.index'));
  }

  /**
   * Display the specified Vendors.
   */
  public function show($id)
  {
    $vendors = $this->vendorsRepository->find($id);

    if (empty($vendors)) {
      Flash::error('Vendor not found');

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
      Flash::error('Vendor not found');

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

      Flash::error('Vendor not found!');
      return redirect(route('vendors.index'));
    }

    $vendor = $this->vendorsRepository->update($request->all(), $id);
    $vendor->account->status = $vendor->status;
    $vendor->save();

    Flash::success('Vendor updated successfully.');
    return redirect(route('vendors.index'));
  }

  /**
   * Remove the specified Vendors from storage (soft delete with cascade tracking).
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $vendor = $this->vendorsRepository->find($id);

    if (empty($vendor)) {
      Flash::error('Vendor not found!');
      return redirect(route('vendors.index'));
    }

    // Check if vendor has transactions - protect from deletion
    if ($vendor->transactions()->count() > 0) {
      Flash::error('Cannot delete vendor. Vendor has ' . $vendor->transactions()->count() . ' transaction(s). Please deactivate instead.');
      return redirect(route('vendors.index'));
    }

    // Check if vendor account has ledger entries before deletion
    if ($vendor->account) {
      $ledgerEntriesCount = DB::table('ledger_entries')
        ->where('account_id', $vendor->account->id)
        ->count();

      if ($ledgerEntriesCount > 0) {
        Flash::error("Cannot delete vendor. The vendor account has {$ledgerEntriesCount} ledger entry(ies). Please clear these first.");
        return redirect(route('vendors.index'));
      }
    }

    // Track cascaded deletions
    $cascadedItems = [];

    // Get account data BEFORE deleting (important!)
    $relatedAccount = $vendor->account;

    // Soft delete the vendor
    $vendor->delete();

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
        'App\Models\Vendors',
        $vendor->id,
        $vendor->name,
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

    // Return JSON response for AJAX calls or Flash + redirect for regular requests
    if (request()->expectsJson() || request()->ajax()) {
      return response()->json([
        'message' => 'Vendor moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=vendors" class="alert-link">View Recycle Bin</a> to restore if needed.'
      ]);
    }

    Flash::success('Vendor moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=vendors" class="alert-link">View Recycle Bin</a> to restore if needed.')->important();
    return redirect(route('vendors.index'));
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return vendors::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Vendor',
      'display_columns' => ['name', 'email', 'contact_number'],
      'trash_view' => 'vendors.trash',
      'index_route' => 'vendors.index',
    ];
  }
}
