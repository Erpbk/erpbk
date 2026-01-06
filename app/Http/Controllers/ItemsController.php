<?php

namespace App\Http\Controllers;

use App\DataTables\ItemsDataTable;
use App\Http\Requests\CreateItemsRequest;
use App\Http\Requests\UpdateItemsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Items;
use App\Models\RiderItemPrice;
use App\Repositories\ItemsRepository;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Flash;

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

    if (!auth()->user()->hasPermissionTo('item_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Items::query()
      ->orderBy('id', 'desc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('code') && !empty($request->code)) {
      $query->where('code', $request->code);
    }
    if ($request->has('customer_id') && !empty($request->customer_id)) {
      $query->where('customer_id', $request->customer_id);
    }
    if ($request->has('supplier_id') && !empty($request->supplier_id)) {
      $query->where('supplier_id', $request->supplier_id);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
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

    $items = $this->itemsRepository->create($input);

    Flash::success('Item added successfully.');
    return redirect()->back();
  }

  /**
   * Display the specified Items.
   */
  public function show($id)
  {
    $items = $this->itemsRepository->find($id);

    if (empty($items)) {
      Flash::error('Items not found');

      return redirect(route('items.index'));
    }

    return view('items.show')->with('items', $items);
  }

  /**
   * Show the form for editing the specified Items.
   */
  public function edit($id)
  {
    $items = $this->itemsRepository->find($id);

    if (empty($items)) {
      Flash::error('Items not found');

      return redirect(route('items.index'));
    }

    return view('items.edit')->with('items', $items);
  }

  /**
   * Update the specified Items in storage.
   */
  public function update($id, UpdateItemsRequest $request)
  {
    $items = $this->itemsRepository->find($id);

    if (empty($items)) {
      Flash::error('Item not found!');
    }

    $items = $this->itemsRepository->update($request->all(), $id);
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
  public function destroy($id)
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

  public function search_item_price($rider_id, $item_id)
  {
    $result = RiderItemPrice::where('item_id', $item_id)->where('RID', $rider_id)->first();
    if ($result && $result->price > 0) {
      return $result;
    } else {
      $result = Items::where('id', $item_id)->first();
      return $result;
    }
  }
  public function get_item_price($item_id)
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
}
