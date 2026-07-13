<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateItemsRequest;
use App\Http\Requests\UpdateItemsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Items;
use App\Models\RiderItemPrice;
use App\Models\Riders;
use App\Repositories\ItemsRepository;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Flash;
use \Illuminate\Support\Facades\Storage;

class ItemsController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
  /** @var ItemsRepository $itemsRepository*/
  private $itemsRepository;

  public function __construct(ItemsRepository $itemsRepo)
  {
    $this->itemsRepository = $itemsRepo;
  }

  /**
   * Display a listing of the Items.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->can('items_index')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Items::query();
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('code') && !empty($request->code)) {
      $query->where('code', $request->code);
    }
    if ($request->has('owner') && !empty($request->owner)) {
      $query->whereJsonContains('owner', $request->owner);
    }
    if ($request->has('supplier_id') && !empty($request->supplier_id)) {
      $query->where('supplier_id', $request->supplier_id);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
    $stats['total'] = $query->count();
    $stats['active'] = (clone $query)->where('status', 1)->count();
    $stats['inactive'] = (clone $query)->where('status', 2)->count();
    $query->orderBy('name');
    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('items.table', [
        'data' => $data,
      ])->render();

      // Use global pagination component
      if (method_exists($data, 'links')) {
        $paginationLinks = $data->links('components.global-pagination')->render();
      } else {
        $paginationLinks = '';
      }

      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
        'total' => method_exists($data, 'total') ? $data->total() : $data->count(),
        'per_page' => method_exists($data, 'perPage') ? $data->perPage() : $data->count(),
      ]);
    }

    return view('items.index', [
      'data' => $data,
      'stats' => $stats
    ]);
  }

  /**
   * Show the form for creating a new Items.
   */
  public function create()
  {
    return view('items.create');
  }

  /**
   * Store a newly created Items in storage.
   */
  public function store(CreateItemsRequest $request)
  {
    $input = $request->all();

    // Handle owner types - decode JSON from the form
    if ($request->has('owner') && !empty($request->owner)) {
      $ownerData = json_decode($request->owner, true);
      $input['owner'] = $ownerData;
    } else {
      $input['owner'] = [];
    }
    $attachmentPath = null;
    // Handle attachment file upload
    if ($request->hasFile('attachment')) {
      $file = $request->file('attachment');
      $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
      $attachmentPath = $file->storeAs('items', $fileName, 'public');
    }
    $input['attachment'] = $attachmentPath;
    // Handle status (checkbox returns '1' when checked, doesn't exist when unchecked)
    // The hidden field sends '2' as fallback, but checkbox value '1' overrides when checked
    $input['status'] = $request->has('status') && $request->status == '1' ? 1 : 2;

    // Create the item
    $items = $this->itemsRepository->create($input);

    if ($request->ajax()) {
      return response()->json([
        'message' => 'Item created successfully',
        'reload' => true,
      ], 200);
    }

    Flash::success('Item added successfully.');
    return redirect()->back();
  }

  /**
   * Display the specified Items.
   */
  public function show($company_slug, $id)
  {
    $item = $this->itemsRepository->find($id);

    if (empty($item)) {
      Flash::error('Items not found');

      return redirect()->back();
    }

    $history = $item->purchaseHistory();

    return view('items.show', compact('history'));
  }

  /**
   * Show the form for editing the specified Items.
   */
  public function edit($company_slug, $id)
  {
    $items = $this->itemsRepository->find($id);

    if (empty($items)) {
      Flash::error('Items not found');

      return redirect()->back();
    }

    return view('items.edit')->with('items', $items);
  }

  /**
   * Update the specified Items in storage.
   */
  public function update($company_slug, $id, UpdateItemsRequest $request)
  {
    $input = $request->all();

    // Find the existing item
    $item = $this->itemsRepository->find($id);

    if (empty($item)) {
      Flash::error('Item not found');
      return redirect()->back();
    }

    // Handle owner types - decode JSON from the form
    if ($request->has('owner') && !empty($request->owner)) {
      $ownerData = json_decode($request->owner, true);
      $input['owner'] = $ownerData;
    } else {
      $input['owner'] = [];
    }

    // Handle attachment file upload
    $attachmentPath = $item->attachment; // Keep existing by default

    if ($request->hasFile('attachment')) {
      // Delete old attachment if exists
      if ($item->attachment && Storage::disk('public')->exists($item->attachment)) {
        Storage::disk('public')->delete($item->attachment);
      }

      $file = $request->file('attachment');
      $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
      $attachmentPath = $file->storeAs('items', $fileName, 'public');
    }

    $input['attachment'] = $attachmentPath;

    // Handle status (checkbox returns '1' when checked, doesn't exist when unchecked)
    // The hidden field sends '2' as fallback, but checkbox value '1' overrides when checked
    $input['status'] = $request->has('status') && $request->status == '1' ? 1 : 2;

    // Update the item
    $item = $this->itemsRepository->update($input, $id);

    if ($request->ajax()) {
      return response()->json([
        'message' => 'Item updated successfully',
        'reload' => true,
      ], 200);
    }

    Flash::success('Item updated successfully.');
    return redirect()->back();
  }

  /**
   * Remove the specified Items from storage (soft delete with cascade tracking).
   * If item is linked to invoices, deletion is prevented.
   * Otherwise, soft deletes item and cascades to soft delete related RiderItemPrice records.
   *
   * @throws \Exception
   */
  public function destroy($company_slug, $id)
  {
    $item = $this->itemsRepository->find($id);

    if (empty($item)) {
      Flash::error('Item not found!');
      return redirect()->back();
    }

    // Check if item is used in any rider invoices
    $riderInvoiceCount = \App\Models\RiderInvoiceItem::where('item_id', $id)->count();

    // Check if item is used in any supplier invoices
    $supplierInvoiceCount = \App\Models\SupplierInvoicesItem::where('item_id', $id)->count();

    // If item exists in any invoice, prevent deletion
    if ($riderInvoiceCount > 0 || $supplierInvoiceCount > 0) {
      $totalInvoices = $riderInvoiceCount + $supplierInvoiceCount;
      Flash::error("Cannot delete this item as it is linked to {$totalInvoices} invoice(s). Please remove the item from all invoices first.");
      return redirect()->back();
    }

    DB::beginTransaction();
    try {
      // Track cascaded deletions
      $cascadedItems = [];

      // Get related data BEFORE deleting (important!)
      $riderItemPrices = RiderItemPrice::where('item_id', $id)->get();

      // Log item deletion with related record count
      \Log::info('Item deletion: checking for related RiderItemPrice records', [
        'item_id' => $item->id,
        'item_name' => $item->name,
        'related_count' => $riderItemPrices->count()
      ]);

      // Set who deleted the item before soft deleting
      $item->deleted_by = auth()->id();
      $item->save();

      // Soft delete the item itself
      $item->delete();

      // Always process related records - Cascade soft delete and track each one
      foreach ($riderItemPrices as $riderItemPrice) {
        $cascadedItems[] = [
          'model' => 'RiderItemPrice',
          'id' => $riderItemPrice->id,
          'name' => "Rider ID: {$riderItemPrice->RID}, Price: {$riderItemPrice->price}",
        ];

        // Set who deleted the related record before soft deleting
        $riderItemPrice->deleted_by = auth()->id();
        $riderItemPrice->save();

        // Soft delete the related record
        $riderItemPrice->delete();

        // Track cascade deletion to database - runs for EVERY related record
        \Log::info('Tracking cascade deletion', [
          'primary_model' => 'App\Models\Items',
          'primary_id' => $item->id,
          'primary_name' => $item->name,
          'related_model' => 'App\Models\RiderItemPrice',
          'related_id' => $riderItemPrice->id,
          'related_name' => "Rider ID: {$riderItemPrice->RID}, Price: {$riderItemPrice->price}",
        ]);

        $cascadeRecord = $this->trackCascadeDeletion(
          'App\Models\Items',
          $item->id,
          $item->name,
          'App\Models\RiderItemPrice',
          $riderItemPrice->id,
          "Rider ID: {$riderItemPrice->RID}, Price: {$riderItemPrice->price}",
          'hasMany',
          'riderItemPrices',
          'soft'
        );

        \Log::info('Cascade deletion tracked successfully', [
          'cascade_record_id' => $cascadeRecord ? $cascadeRecord->id : 'NULL'
        ]);
      }

      DB::commit();

      // Build cascade message
      $cascadeMessage = '';
      if (!empty($cascadedItems)) {
        $cascadeMessage = ' (Also deleted: ';
        $parts = [];
        foreach ($cascadedItems as $deletedItem) {
          $parts[] = "{$deletedItem['model']}: {$deletedItem['name']}";
        }
        $cascadeMessage .= implode(', ', $parts) . ')';
      }

      Flash::success('Item moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=items" class="alert-link">View Recycle Bin</a> to restore if needed.');
      return redirect()->back();
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error('Failed to delete item with cascades', [
        'item_id' => $id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      Flash::error('Failed to delete item: ' . $e->getMessage());
      return redirect()->back();
    }
  }

  public function search_item_price($company_slug, $rider_id, $item_id)
  {
    $result = RiderItemPrice::where('item_id', $item_id)->where('RID', $rider_id)->first();
    if ($result && $result->price > 0) {
      return $result;
    } else {
      $result = Items::where('id', $item_id)->first();
      return $result;
    }
  }
  public function get_item_price($company_slug, $item_id)
  {

    $result = Items::where('id', $item_id)->first();
    return $result;
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return Items::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Item',
      'display_columns' => ['name', 'code', 'price'],
      'trash_view' => 'items.trash',
      'index_route' => 'items.index',
    ];
  }

  public function getOwners(Request $request)
  {
    $request->validate([
      'owner_type' => 'required|string|in:customer,leasingCompany,supplier,garage',
      'search' => 'nullable|string|max:255',
      'page' => 'nullable|integer|min:1'
    ]);

    $type = $request->owner_type;
    $search = $request->search;
    $page = $request->page ?? 1;
    $perPage = 20;

    $model = $this->getOwnerModel($type);
    if (!$model) {
      return response()->json(['success' => false, 'data' => []]);
    }

    $query = $model::query();

    if ($search) {
      $searchFields = $this->getSearchFields($type);
      $query->where(function ($q) use ($searchFields, $search) {
        foreach ($searchFields as $field) {
          $q->orWhere($field, 'LIKE', "%{$search}%");
        }
      });
    }

    $owners = $query->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
      'success' => true,
      'data' => $owners->items(),
      'current_page' => $owners->currentPage(),
      'has_more' => $owners->hasMorePages(),
      'total' => $owners->total()
    ]);
  }

  private function getSearchFields(string $type): array
  {
    $fields = [
      'customer' => ['name', 'email', 'phone'],
      'supplier' => ['name', 'company_name', 'email'],
      'leasingCompany' => ['company_name', 'contact_person', 'email'],
      'garage' => ['name', 'address', 'phone']
    ];

    return $fields[$type] ?? ['name'];
  }

  private function getOwnerName($owner, string $type): string
  {
    switch ($type) {
      case 'customer':
        return $owner->name ?? 'Unnamed Customer';
      case 'supplier':
        return $owner->company_name ?? $owner->name ?? 'Unnamed Supplier';
      case 'leasingCompany':
        return $owner->company_name ?? 'Unnamed Leasing Company';
      case 'garage':
        return $owner->name ?? 'Unnamed Garage';
      default:
        return 'Unknown';
    }
  }

  // Get owner model class
  private function getOwnerModel(string $type)
  {
    $models = [
      'customer' => \App\Models\Customers::class,
      'supplier' => \App\Models\Supplier::class,
      'leasingCompany' => \App\Models\LeasingCompanies::class,
      'garage' => \App\Models\Garages::class,
    ];

    return $models[$type] ?? null;
  }

  public function itemsByRider(Request $request, $company_slug, $rider_id)
  {
    try {
      $riderId = $rider_id;

      if (!$riderId) {
        return response()->json(['success' => false, 'items' => []]);
      }

      // Get the rider with customer_id
      $rider = Riders::find($riderId);

      if (!$rider) {
        return response()->json(['success' => false, 'items' => []]);
      }

      // Debug: Log the customer_id to check
      \Log::info('Rider Customer ID: ' . $rider->customer_id);

      // Get items based on rider's customer_id
      $items = Items::where(function ($query) use ($rider) {
        $query->whereJsonContains('ref_name->customer', (string)$rider->customer_id);
        // Add more conditions if needed:
        // ->orWhereJsonContains('ref_name->leasingCompany', (string)$rider->customer_id)
        // ->orWhereJsonContains('ref_name->supplier', (string)$rider->customer_id);
      })->pluck('name', 'id');

      // Debug: Log the items found
      \Log::info('Items found: ' . $items->count());

      return response()->json([
        'success' => true,
        'items' => $items
      ]);
    } catch (\Exception $e) {
      \Log::error('Error loading items by rider: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Error loading items',
        'error' => $e->getMessage()
      ]);
    }
  }
}
