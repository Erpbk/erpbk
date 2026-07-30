<?php

namespace App\Http\Controllers;

use App\Models\CustomerInvoices;
use App\Models\Customers;
use App\Models\Transactions;
use App\Models\Branch;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Storage;
use App\Support\GlobalAccounts;

class CustomerInvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomerInvoices::with(['items', 'customer'])->orderBy('inv_date', 'desc');
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by billing month
        if ($request->has('billing_month') && $request->billing_month) {
            $query->where('billing_month', $request->billing_month . '-01');
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('reference') && $request->reference) {
            $query->where('reference', 'Like', $request->reference);
        }
        $invoices = $query->get();
        return view('customer_invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $invoiceId = request()->input('invoice_id') ?? null;
        $customer_id = request()->input('customer_id') ?? null;
        $invoice = $invoiceId ? CustomerInvoices::find($invoiceId) : null;
        $branches = Branch::whereIn('id', app('user_branches'))->get();
        return view('customer_invoices.create', compact('invoice', 'customer_id', 'branches'));
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
            'item_rate.*' => 'numeric',
            'item_vat' => 'nullable|array',
            'item_vat.*' => 'numeric|max:100',
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
                'billing_month' => $request->billing_month . '-01',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'description' => $request->description,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'vat' => $vatTotal,
                'total' => $grandTotal,
                'attachment' => $attachmentPath,
                'branch_id' => Customers::where('id', $request->customer_id)->value('branch_id'),
            ]);

            // Create invoice items
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }

            //Create Transactions Against Invoice
            $transCode = \App\Helpers\Account::trans_code();
            $customerAccountId = (int) Customers::where('id', $request->customer_id)->value('account_id');
            if ($customerAccountId <= 0) {
                throw new \RuntimeException('Selected customer has no linked account.');
            }

            // DEBIT the customer account
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $invoice->inv_date,
                'reference_id' => $invoice->id,
                'reference_type' => 'CI',
                'account_id' => $customerAccountId,
                'credit' => 0,
                'debit' => $invoice->total,
                'billing_month' => $invoice->billing_month,
                'narration' => 'Invoice CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) . ' : ' . $invoice->description,
                'branch_id' => $invoice->branch_id,
            ]);

            //Credit Sales Account
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $invoice->inv_date,
                'reference_id' => $invoice->id,
                'reference_type' => 'CI',
                'account_id' => GlobalAccounts::id('SALES_ACCOUNT'),
                'credit' => $invoice->subtotal,
                'debit' => 0,
                'billing_month' => $invoice->billing_month,
                'branch_id' => $invoice->branch_id,
                'narration' => 'Invoice CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) . ' : ' . $invoice->description,
            ]);

            //Credit VAT Account
            if ($invoice->vat > 0) {
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $invoice->inv_date,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'CI',
                    'account_id' => GlobalAccounts::id('VAT_ON_SALES'),
                    'credit' => $invoice->vat,
                    'debit' => 0,
                    'billing_month' => $invoice->billing_month,
                    'branch_id' => $invoice->branch_id,
                    'narration' => $invoice->description,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Invoice created successfully!',
                'redirect' => route('customer_invoices.show', $invoice->id)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Failed to create invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $company_slug, $id)
    {
        $invoice = CustomerInvoices::find($id);
        if (!$invoice) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Invoice Does not exist'], 500);
            }
            Flash::error('Invoice Not Found');
            return redirect()->back();
        }
        $invoice->load(['items', 'customer']);
        return view('customer_invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($company_slug, $id)
    {
        $invoice = CustomerInvoices::find($id);
        $invoice->load('items');
        return view('customer_invoices.edit', compact('invoice'));
    }

    public function clone($company_slug, $id)
    {
        $invoice = CustomerInvoices::find($id);
        $invoice->load('items');
        return view('customer_invoices.create', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $company_slug, $id)
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
            'item_rate.*' => 'numeric',
            'item_vat' => 'nullable|array',
            'item_vat.*' => 'numeric|max:100',
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
                'branch_id' => Customers::where('id', $request->customer_id)->value('branch_id'),
                'attachment' => $attachmentPath,
            ]);

            // 🔥 IMPORTANT: Delete old items
            $invoice->items()->delete();

            // Re-insert items
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }

            $transactions = Transactions::where(['reference_id' =>  $invoice->id, 'reference_type' => 'CI'])->get();
            $transCode = $transactions->first()->trans_code;
            $customerAccountId = (int) Customers::where('id', $request->customer_id)->value('account_id');
            if ($customerAccountId <= 0) {
                throw new \RuntimeException('Selected customer has no linked account.');
            }
            // DEBIT the customer account
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $invoice->inv_date,
                'reference_id' => $invoice->id,
                'reference_type' => 'CI',
                'account_id' => $customerAccountId,
                'credit' => 0,
                'debit' => $invoice->total,
                'billing_month' => $invoice->billing_month,
                'narration' => 'Invoice CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) . ' : ' . $invoice->description,
                'branch_id' => $invoice->branch_id,
            ]);

            //Credit Sales Account
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $invoice->inv_date,
                'reference_id' => $invoice->id,
                'reference_type' => 'CI',
                'account_id' => GlobalAccounts::id('SALES_ACCOUNT'),
                'credit' => $invoice->subtotal,
                'debit' => 0,
                'billing_month' => $invoice->billing_month,
                'branch_id' => $invoice->branch_id,
                'narration' => 'Invoice CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) . ' : ' . $invoice->description,
            ]);

            //Credit VAT Account
            if ($invoice->vat > 0) {
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $invoice->inv_date,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'CI',
                    'account_id' => GlobalAccounts::id('VAT_ON_SALES'),
                    'credit' => $invoice->vat,
                    'debit' => 0,
                    'billing_month' => $invoice->billing_month,
                    'branch_id' => $invoice->branch_id,
                    'narration' => $invoice->description,
                ]);
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
    public function destroy(Request $request, $company_slug, $id)
    {
        try {
            DB::beginTransaction();

            $invoice = CustomerInvoices::findOrFail($id);

            // 🔥 Delete attachment if exists
            if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            Transactions::where(['reference_id' =>  $invoice->id, 'reference_type' => 'CI'])->delete();

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
