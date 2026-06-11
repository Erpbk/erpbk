<?php

namespace App\Http\Controllers;

use App\Models\RiderInventoryAssignment;
use App\Models\RiderInventoryItem;
use App\Support\CompanyAuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Flash;
use DB;

class RiderInventoryItemController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!auth()->user()->hasPermissionTo('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = RiderInventoryItem::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (int) $request->status);
        }

        $items = $query->orderBy('display_order')->orderBy('name')->get();
        $itemRoute = str_replace('.index', '', $request->route()->getName());

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('rider_inventory_items.table', [
                    'items' => $items,
                    'itemRoute' => $itemRoute,
                ])->render(),
            ]);
        }

        return view('rider_inventory_items.index', compact('items', 'itemRoute'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_create')) {
            abort(403, 'Unauthorized action.');
        }

        return redirect()->route($this->itemsIndexRoute())->with('open_create_modal', true);
    }

    public function show($company_slug, $id)
    {
        if (!auth()->check()) {
            return redirect()->route($this->itemsIndexRoute());
        }

        if (!auth()->user()->hasPermissionTo('riderinventory_view')) {
            abort(403, 'Unauthorized action.');
        }

        return redirect()->route($this->itemsIndexRoute())->with('open_edit_item_id', (int) $id);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:rider_inventory_items,name',
            'item_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->route($this->itemsIndexRoute())
                ->withErrors($validator)
                ->withInput()
                ->with('open_create_modal', true);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            $item = new RiderInventoryItem();
            $item->name = $validated['name'];
            $item->item_price = $validated['item_price'];
            $item->is_active = $request->has('is_active');
            $item->display_order = $validated['display_order']
                ?? ((int) (RiderInventoryItem::max('display_order') ?? 0) + 1);
            $item->created_by = auth()->id();
            $item->save();

            DB::commit();

            Flash::success('Inventory item added successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->route($this->itemsIndexRoute())
                ->withInput()
                ->with('open_create_modal', true);
        }
    }

    public function edit($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        return redirect()->route($this->itemsIndexRoute())->with('open_edit_item_id', (int) $id);
    }

    public function update(Request $request, $company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $item = RiderInventoryItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:rider_inventory_items,name,' . $id,
            'item_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->route($this->itemsIndexRoute())
                ->withErrors($validator)
                ->withInput()
                ->with('open_edit_item_id', (int) $id);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            $item->name = $validated['name'];
            $item->item_price = $validated['item_price'];
            $item->is_active = $request->has('is_active');
            $item->display_order = $validated['display_order'] ?? $item->display_order;
            $item->updated_by = auth()->id();
            $item->save();

            DB::commit();

            Flash::success('Inventory item updated successfully.');
            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->route($this->itemsIndexRoute())
                ->withInput()
                ->with('open_edit_item_id', (int) $id);
        }
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $item = RiderInventoryItem::findOrFail($id);

            $isUsed = RiderInventoryAssignment::where('inventory_item_id', $item->id)->exists();
            if ($isUsed) {
                $message = 'Cannot delete this item as it has assignment history.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            $item->delete();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory item deleted successfully.',
                    'id' => (int) $id,
                ]);
            }

            Flash::success('Inventory item deleted successfully.');

            return $this->redirectAfterAction($request);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    public function toggleActive($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $item = RiderInventoryItem::findOrFail($id);
            $item->is_active = !$item->is_active;
            $item->updated_by = auth()->id();
            $item->save();

            $status = $item->is_active ? 'activated' : 'deactivated';
            Flash::success("Inventory item {$status} successfully.");

            return $this->redirectAfterAction(request());
        } catch (\Exception $e) {
            Flash::error('Error: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    public function reorder(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('riderinventory_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $order = $request->input('order', []);
        if (!is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order.'], 422);
        }

        foreach ($order as $position => $id) {
            RiderInventoryItem::where('id', (int) $id)->update(['display_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function itemsIndexRoute(): string
    {
        $name = request()->route()?->getName() ?? '';

        return str_starts_with($name, 'settings-panel.')
            ? 'settings-panel.rider-inventory-items.index'
            : 'rider-inventory-items.index';
    }

    private function itemsRouteBase(): string
    {
        $name = request()->route()?->getName() ?? '';

        return str_starts_with($name, 'settings-panel.')
            ? 'settings-panel.rider-inventory-items'
            : 'rider-inventory-items';
    }

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            return redirect()->to($returnTo);
        }

        return redirect()->route($this->itemsIndexRoute());
    }
}
