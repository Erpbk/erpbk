<?php

namespace App\Http\Controllers;

use App\Models\InventoryPurchase;
use App\Models\Items;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;

class InventoryPurchaseController extends Controller
{
    use GlobalPagination;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = Items::query()->whereJsonContains('owner', 'garage')->whereNotNull('is_maintained')->where('is_maintained', true);
        if ($request->filled('item_id')) {
            $query->where('id', $request->item_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $data = $this->applyPagination($query, $paginationParams);
        return view('inventory_purchases.items', compact('data'));
    }

    public function indexBatches(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = InventoryPurchase::query()->with(['item', 'supplier', 'adjustments', 'maintenanceItems']);
        
        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }
        
        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by batch_no
        if ($request->filled('batch_no')) {
            $query->where('batch_no','like', '%'.$request->batch_no.'%');
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('purchase_date', '<=', $request->date_to);
        }
        
        $purchases = $this->applyPagination($query, $paginationParams);
        
        $items = Items::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        
        return view('inventory_purchases.index', compact('purchases', 'items', 'suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryPurchase $inventoryPurchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryPurchase $inventoryPurchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryPurchase $inventoryPurchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryPurchase $inventoryPurchase)
    {
        //
    }

    public function showBatch($company_slug, $batch_no)
    {
        $batches = InventoryPurchase::with(['item', 'supplier', 'adjustments.adjustedBy', 'maintenanceItems.bikeMaintenance.bike'])->where('batch_no', $batch_no)->get();
        return view('inventory.showBatch', compact('batches'));
    }
}
