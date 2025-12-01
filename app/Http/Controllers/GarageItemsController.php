<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGarageItemRequest;
use App\Http\Requests\UpdateGarageItemRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\GarageItem;
use App\Models\Supplier;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Traits\GlobalPagination;
use App\Services\GarageItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;

class GarageItemsController extends AppBaseController
{
    use GlobalPagination;

    /** @var GarageItemService */
    private $garageItemService;

    /**
     * Constructor
     */
    public function __construct(GarageItemService $garageItemService)
    {
        $this->garageItemService = $garageItemService;
    }

    /**
     * Display a listing of the GarageItems.
     */
    public function index(Request $request)
    {
        // Check permissions if needed
        // For now, we'll skip the permission check
        // if (!auth()->user()->hasPermissionTo('item_view')) {
        //     abort(403, 'Unauthorized action.');
        // }

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = GarageItem::query()
            ->orderBy('id', 'desc');

        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('item_code') && !empty($request->item_code)) {
            $query->where('item_code', 'like', '%' . $request->item_code . '%');
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
            $tableData = view('garage_items.table', [
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

        $suppliers = Supplier::pluck('name', 'id')->prepend('Select Supplier', '');

        return view('garage_items.index', [
            'data' => $data,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Show the form for creating a new GarageItem.
     */
    public function create()
    {
        $suppliers = Supplier::pluck('name', 'id')->prepend('Select Supplier', '');
        return view('garage_items.create', compact('suppliers'));
    }

    /**
     * Store a newly created GarageItem in storage.
     */
    public function store(CreateGarageItemRequest $request)
    {
        $input = $request->all();

        try {
            DB::beginTransaction();

            // Check if item code is provided
            $itemCode = $input['item_code'] ?? null;

            // Check if item already exists by item_code (if provided)
            $existingItem = null;
            if ($itemCode) {
                $existingItem = GarageItem::where('item_code', $itemCode)->first();
            }

            if ($existingItem) {
                // Calculate weighted average price and update the item
                $calculatedValues = $this->garageItemService->calculateWeightedAverage(
                    $existingItem,
                    $input['qty'],
                    $input['price']
                );

                $existingItem->qty = $calculatedValues['qty'];
                $existingItem->price = $calculatedValues['price']; // Latest price per unit
                $existingItem->avg_price = $calculatedValues['avg_price']; // Weighted average price
                $existingItem->total_amount = $calculatedValues['total_amount']; // Total value
                $existingItem->purchase_date = $input['purchase_date']; // Update purchase date
                $existingItem->save();

                // Update stock status
                $existingItem->updateStockStatus();

                $garageItem = $existingItem;
            } else {
                // For new items, price and avg_price are the same initially
                $input['avg_price'] = $input['price'];
                $input['total_amount'] = $input['price'] * $input['qty'];

                // Create new item
                $garageItem = GarageItem::create($input);

                // Set initial stock status
                $garageItem->updateStockStatus();
            }

            // Create accounting entries and get voucher
            $voucher = $this->createAccountingEntries($garageItem);

            // Generate garage report
            $this->generateGarageReport($garageItem);

            DB::commit();

            if ($voucher && !isset($voucher->error)) {
                Flash::success('Garage Item saved successfully. Voucher created with transaction code: ' .
                    ($voucher->trans_code ?? 'N/A'));
            } else {
                Flash::success('Garage Item saved successfully.');
            }
            return redirect(route('garage-items.index'));
        } catch (\Exception $e) {
            DB::rollback();
            Flash::error('Error saving Garage Item: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified GarageItem.
     */
    public function show($id)
    {
        $garageItem = GarageItem::find($id);

        if (empty($garageItem)) {
            Flash::error('Garage Item not found');
            return redirect(route('garage-items.index'));
        }

        return view('garage_items.show')->with('garageItem', $garageItem);
    }

    /**
     * Show the form for editing the specified GarageItem.
     */
    public function edit($id)
    {
        $garageItem = GarageItem::find($id);

        if (empty($garageItem)) {
            Flash::error('Garage Item not found');
            return redirect(route('garage-items.index'));
        }

        $suppliers = Supplier::where('parent_id', 1287)->pluck('name', 'id')->prepend('Select Supplier', '');

        return view('garage_items.edit', compact('garageItem', 'suppliers'));
    }

    /**
     * Update the specified GarageItem in storage.
     */
    public function update($id, UpdateGarageItemRequest $request)
    {
        $garageItem = GarageItem::find($id);

        if (empty($garageItem)) {
            Flash::error('Garage Item not found');
            return redirect(route('garage-items.index'));
        }

        try {
            DB::beginTransaction();

            $oldQty = $garageItem->qty;
            $garageItem->fill($request->all());
            $garageItem->save();

            // Update stock status
            $garageItem->updateStockStatus();

            // Find existing vouchers for this garage item
            $existingVouchers = $this->garageItemService->findGarageItemVouchers($garageItem->id);

            // If quantity increased, create accounting entries and generate report
            if ($garageItem->qty > $oldQty) {
                // Create accounting entries for the difference in quantity
                $qtyDifference = $garageItem->qty - $oldQty;

                // If there are existing vouchers, update the latest one
                if ($existingVouchers->count() > 0) {
                    Log::debug('Found existing vouchers for garage item: ' . $garageItem->id . ', updating the latest one');

                    // Get the latest voucher
                    $latestVoucher = $existingVouchers->sortByDesc('id')->first();

                    // Update the voucher with new amount
                    $totalAmount = $garageItem->price * $garageItem->qty;
                    $voucher = $this->garageItemService->updateGarageVoucher(
                        $latestVoucher->id,
                        $garageItem,
                        $totalAmount
                    );

                    // Generate garage report
                    $this->generateGarageReport($garageItem);

                    DB::commit();

                    if ($voucher) {
                        Flash::success('Garage Item updated successfully. Voucher updated with transaction code: ' .
                            ($latestVoucher->trans_code ?? 'N/A'));
                    } else {
                        Flash::success('Garage Item updated successfully, but voucher update failed.');
                    }
                } else {
                    // Create a new GarageItem object with just the difference for voucher creation
                    $diffItem = new GarageItem();
                    $diffItem->name = $garageItem->name;
                    $diffItem->supplier_id = $garageItem->supplier_id;
                    $diffItem->price = $garageItem->price;
                    $diffItem->qty = $qtyDifference;
                    $diffItem->purchase_date = $garageItem->purchase_date;

                    // Create voucher for the additional quantity
                    $voucher = $this->createAccountingEntries($diffItem);

                    // Generate garage report
                    $this->generateGarageReport($garageItem);

                    DB::commit();

                    if ($voucher && !isset($voucher->error)) {
                        Flash::success('Garage Item updated successfully. New voucher created with transaction code: ' .
                            ($voucher->trans_code ?? 'N/A'));
                    } else {
                        Flash::success('Garage Item updated successfully.');
                    }
                }
            } else {
                // For quantity reduction or other changes
                if ($existingVouchers->count() > 0) {
                    // Get the latest voucher
                    $latestVoucher = $existingVouchers->sortByDesc('id')->first();

                    // Update the voucher with new amount
                    $totalAmount = $garageItem->price * $garageItem->qty;
                    $voucher = $this->garageItemService->updateGarageVoucher(
                        $latestVoucher->id,
                        $garageItem,
                        $totalAmount
                    );

                    // Generate garage report if quantity changed
                    if ($oldQty != $garageItem->qty) {
                        $this->generateGarageReport($garageItem);
                    }

                    DB::commit();

                    if ($voucher) {
                        Flash::success('Garage Item updated successfully. Voucher updated with transaction code: ' .
                            ($latestVoucher->trans_code ?? 'N/A'));
                    } else {
                        Flash::success('Garage Item updated successfully, but voucher update failed.');
                    }
                } else {
                    // Just generate a report for quantity reduction or other changes
                    if ($oldQty != $garageItem->qty) {
                        $this->generateGarageReport($garageItem);
                    }

                    DB::commit();
                    Flash::success('Garage Item updated successfully.');
                }
            }

            return redirect(route('garage-items.index'));
        } catch (\Exception $e) {
            DB::rollback();
            Flash::error('Error updating Garage Item: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified GarageItem from storage.
     */
    public function destroy($id)
    {
        $garageItem = GarageItem::find($id);

        if (empty($garageItem)) {
            Flash::error('Garage Item not found');
            return redirect(route('garage-items.index'));
        }

        $garageItem->delete();

        Flash::success('Garage Item deleted successfully.');
        return redirect(route('garage-items.index'));
    }

    /**
     * Redirect to vouchers page with filter for this garage item
     */
    public function vouchers($id)
    {
        $garageItem = GarageItem::find($id);

        if (empty($garageItem)) {
            Flash::error('Garage Item not found');
            return redirect(route('garage-items.index'));
        }

        // Redirect to the main vouchers page with filters pre-applied
        Flash::info('Showing vouchers for garage item: ' . $garageItem->name);
        return redirect()->route('vouchers.index', [
            'voucher_type' => 'GV',
            'quick_search' => $garageItem->name
        ]);
    }

    /**
     * Create accounting entries for garage item
     */
    private function createAccountingEntries($garageItem)
    {
        try {
            Log::debug('Starting createAccountingEntries in GarageItemsController');
            Log::debug('GarageItem: ' . json_encode($garageItem->toArray()));

            // Get supplier account
            $supplier = Supplier::find($garageItem->supplier_id);
            if (!$supplier) {
                throw new \Exception('Supplier not found');
            }

            Log::debug('Supplier found: ' . $supplier->id);

            // Create a GV (Garage Voucher) type voucher
            Log::debug('Calling garageItemService->createGarageVoucher');
            $voucher = $this->garageItemService->createGarageVoucher(
                $garageItem,
                $garageItem->qty,
                $garageItem->price
            );

            Log::debug('Voucher created: ' . ($voucher ? json_encode($voucher) : 'null'));

            if ($voucher && isset($voucher->error)) {
                Log::error('Voucher creation error: ' . $voucher->error);
            }

            return $voucher;
        } catch (\Exception $e) {
            Log::error('Error creating accounting entries: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate garage report for item
     */
    private function generateGarageReport($garageItem)
    {
        try {
            $supplier = Supplier::find($garageItem->supplier_id);

            $data = [
                'supplier_name' => $supplier ? $supplier->name : 'Unknown',
                'item_name' => $garageItem->name,
                'quantity' => $garageItem->qty,
                'price' => $garageItem->price,
                'total_price' => $garageItem->price * $garageItem->qty,
                'purchase_date' => $garageItem->purchase_date,
                'status' => $garageItem->status,
                'item_code' => $garageItem->item_code
            ];

            // For now, just log the report data instead of generating a PDF
            Log::info('Garage Report generated for item: ' . $garageItem->name);
            Log::info('Report data: ' . json_encode($data));

            $filename = 'garage_report_' . $garageItem->id . '_' . date('YmdHis') . '.pdf';
            return $filename;
        } catch (\Exception $e) {
            Log::error('Error generating garage report: ' . $e->getMessage());
            return null;
        }
    }
}
