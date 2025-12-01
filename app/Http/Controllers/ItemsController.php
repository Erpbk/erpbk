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
use Illuminate\Http\Request;
use Flash;

class ItemsController extends AppBaseController
{
  use GlobalPagination;
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
   * Remove the specified Items from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $items = $this->itemsRepository->find($id);

    if (empty($items)) {
      Flash::error('Item not found!');
    }

    $this->itemsRepository->delete($id);
    Flash::success('Item deleted successfully.');
    return redirect()->back();
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
}
