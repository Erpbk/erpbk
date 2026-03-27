<?php

namespace App\Http\Controllers;

use App\Models\CustomerInvoices;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Storage;

class CustomerInvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomerInvoices::with(['items','customer'])->orderBy('inv_date','desc');
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Filter by billing month
        if ($request->has('billing_month') && $request->billing_month) {
            $query->where('billing_month', $request->billing_month.'-01');
        }

        if($request->has('reference') && $request->reference){
            $query->where('reference','Like',$request->reference);
        }
        $invoices = $query->get();
        return view('customer_invoices.index',compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $invoiceId = request()->input('invoice_id') ?? null;
        $invoice = $invoiceId ? CustomerInvoices::find($invoiceId) : null;
        return view('customer_invoices.create',compact('invoice'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'inv_date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'description' => 'required|string',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:items,id',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'numeric|min:0.01',
            'item_rate' => 'required|array|min:1',
            'item_rate.*' => 'numeric|min:0',
            'item_vat' => 'nullable|array',
            'item_vat.*' => 'numeric|min:0|max:100',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Handle attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('customer_invoices', $fileName, 'public');
            }
            
            // Calculate totals
            $subtotal = 0;
            $vatTotal = 0;
            $grandTotal = 0;
            $itemsData = [];
            
            foreach ($request->item_ids as $index => $itemId) {
                $item = \App\Models\Items::find($itemId);
                $quantity = $request->item_qty[$index];
                $rate = $request->item_rate[$index];
                $vatPercent = $request->item_vat[$index] ?? 0;
                
                $itemSubtotal = $quantity * $rate;
                $itemVat = $itemSubtotal * ($vatPercent / 100);
                $itemTotal = $itemSubtotal + $itemVat;
                
                $subtotal += $itemSubtotal;
                $vatTotal += $itemVat;
                $grandTotal += $itemTotal;
                
                $itemsData[] = [
                    'item_id' => $itemId,
                    'item_name' => $item->name,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'vat' => $vatPercent,
                    'vat_amount' => $itemVat,
                    'total_amount' => $itemTotal,
                ];
            }
            
            // Create invoice
            $invoice = CustomerInvoices::create([
                'customer_id' => $request->customer_id, // Assuming company_id maps to customer_id
                'inv_date' => $request->inv_date,
                'billing_month' => $request->billing_month.'-01',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'description' => $request->description,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'vat' => $vatTotal,
                'total' => $grandTotal,
                'attachment' => $attachmentPath,
            ]);
            
            // Create invoice items
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }
            
            DB::commit();
            
            return redirect()->route('customer_invoices.show', $invoice)
                            ->with('success', 'Invoice created successfully!');
                            
        } catch (\Exception $e) {
            DB::rollBack();
            if($request->ajax()){
                return response()->json([
                    'message' => 'Error'.$e->getMessage(),
                    ],500);
            }
            return back()->with('error', 'Failed to create invoice: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $invoice = CustomerInvoices::find($id);
        if(!$invoice){
            if($request->ajax()){
                return response()->json(['message' => 'Invoice Does not exist'],500);
            }
            Flash::error('Invoice Not Found');
            return redirect()->back();
        }
        $invoice->load(['items','customer']);
        return view('customer_invoices.show',compact('invoice'));
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $invoice = CustomerInvoices::find($id);
        $invoice->load('items');
        return view('customer_invoices.edit', compact('invoice'));
    }

    public function clone($id)
    {
        $invoice = CustomerInvoices::find($id);
        $invoice->load('items');
        return view('customer_invoices.create', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'inv_date' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'description' => 'required|string',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:items,id',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'numeric|min:0.01',
            'item_rate' => 'required|array|min:1',
            'item_rate.*' => 'numeric|min:0',
            'item_vat' => 'nullable|array',
            'item_vat.*' => 'numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $invoice = CustomerInvoices::findOrFail($id);

            // Handle attachment (replace if new uploaded)
            $attachmentPath = $invoice->attachment;

            if ($request->hasFile('attachment')) {
                // delete old file (optional but recommended)
                if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                    Storage::disk('public')->delete($invoice->attachment);
                }

                $file = $request->file('attachment');
                $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('customer_invoices', $fileName, 'public');
            }

            // Recalculate totals
            $subtotal = 0;
            $vatTotal = 0;
            $grandTotal = 0;
            $itemsData = [];

            foreach ($request->item_ids as $index => $itemId) {
                $item = \App\Models\Items::find($itemId);

                $quantity = $request->item_qty[$index];
                $rate = $request->item_rate[$index];
                $vatPercent = $request->item_vat[$index] ?? 0;

                $itemSubtotal = $quantity * $rate;
                $itemVat = $itemSubtotal * ($vatPercent / 100);
                $itemTotal = $itemSubtotal + $itemVat;

                $subtotal += $itemSubtotal;
                $vatTotal += $itemVat;
                $grandTotal += $itemTotal;

                $itemsData[] = [
                    'item_id' => $itemId,
                    'item_name' => $item->name,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'vat' => $vatPercent,
                    'vat_amount' => $itemVat,
                    'total_amount' => $itemTotal,
                ];
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $request->customer_id,
                'inv_date' => $request->inv_date,
                'billing_month' => $request->billing_month . '-01',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'description' => $request->description,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'vat' => $vatTotal,
                'total' => $grandTotal,
                'attachment' => $attachmentPath,
            ]);

            // 🔥 IMPORTANT: Delete old items
            $invoice->items()->delete();

            // Re-insert items
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }

            DB::commit();

            return redirect()->route('customer_invoices.show', $invoice)
                            ->with('success', 'Invoice updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to update invoice: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $invoice = CustomerInvoices::findOrFail($id);

            // 🔥 Delete attachment if exists
            if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            // 🔥 Delete related items
            $invoice->items()->delete();

            // 🔥 Delete invoice
            $invoice->delete();

            DB::commit();

            // AJAX response
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice deleted successfully!',
                    'reload' => true
                ]);
            }

            return redirect()->route('customer_invoices.index')
                            ->with('success', 'Invoice deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }
}
