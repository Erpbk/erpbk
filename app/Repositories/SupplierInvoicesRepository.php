<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Garages;
use App\Models\Items;
use App\Models\InventoryPurchase;
use App\Models\SupplierInvoicesItem;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Repositories\BaseRepository;
use App\Services\TransactionService;
use DB;

class SupplierInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'supplier_id',
        'vendor_id',
        'zone',
        'login_hours',
        'working_days',
        'perfect_attendance',
        'rejection',
        'performance',
        'off',
        'month_invoice',
        'descriptions',
        'total_amount',
        'billing_month',
        'gaurantee',
        'notes',
        'garage_id',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return SupplierInvoices::class;
    }

    public function record($request, $id = null)
    {
        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'descriptions' => 'required|string',
            'notes' => 'nullable|string',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:items,id',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'numeric|min:0.01',
            'item_rate' => 'required|array|min:1',
            'item_rate.*' => 'numeric|min:0',
            'item_vat' => 'nullable|array',
            'item_vat.*' => 'numeric|min:0|max:100',
        ];
        if ($request->has('is_invoice')) {
            $rules['inv_date'] = 'required|date';
            $rules['billing_month'] = 'required|date_format:Y-m';
            $rules['attachment'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['garage_id'] = 'required|exists:garages,id';
        } else {
            $rules['order_date'] = 'required|date';
            $rules['garage_id'] = 'nullable|exists:garages,id';
        }
        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Handle attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('supplier_invoices', $fileName, 'public');
                $request['attachment'] = $attachmentPath;
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
                    'item_des' => $item->name,
                    'qty' => $quantity,
                    'rate' => $rate,
                    'tax' => $vatPercent,
                    'tax_amount' => $itemVat,
                    'total_amount' => $itemTotal,
                ];
            }
            if ($request->has('is_invoice'))
                $request['billing_month'] = $request['billing_month'] . '-01';
            $request['subtotal'] = $subtotal;
            $request['vat'] = $vatTotal;
            $request['total_amount'] = $grandTotal;

            if ($id) {
                $invoice = SupplierInvoices::find($id);
                $isInvoice = $request->has('is_invoice') && $request['is_invoice'];
                InventoryPurchase::reassignUsageForInvoice($invoice->id);
                if (! $isInvoice && InventoryPurchase::hasBeenUsedForInvoice($invoice->id)) {
                    throw new \Exception('Cannot convert to order. Inventory from this Purchase has already been used');
                }
                $request['updated_by'] = auth()->id();
                $invoice->update($request->all());
                $invoice->items()->delete();
                if (! $isInvoice) {
                    InventoryPurchase::where('inv_id', $invoice->id)->forceDelete();
                }
            } else {
                $request['created_by'] = auth()->id();
                $invoice = SupplierInvoices::create($request->all());
            }

            // Create invoice items
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }

            if ($request->has('is_invoice') && $request['is_invoice']) {
                $this->syncInvoiceInventory($invoice, $itemsData);

                //Create Transactions Against Invoice
                $transCode = null;
                if ($id) {
                    $transCode = Transactions::where('reference_type', 'SUP')
                        ->where('reference_id', $id)
                        ->value('trans_code');
                    Transactions::where('trans_code', $transCode)->delete();
                }
                if (!$transCode) {
                    $transCode = \App\Helpers\Account::trans_code();
                }
                $invoice->load('supplier', 'garage');
                $debitInventoryAccountId = GlobalAccounts::id('GARAGE_ACCOUNT');
                if ($invoice->garage_id) {
                    $g = $invoice->garage ?? Garages::find($invoice->garage_id);
                    if ($g && $g->account_id) {
                        $debitInventoryAccountId = (int) $g->account_id;
                    }
                }
                // Credit the Supplier Account
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $invoice->inv_date,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'SUP',
                    'account_id' => $invoice->supplier->account_id,
                    'credit' => $invoice->total_amount,
                    'debit' => 0,
                    'billing_month' => $invoice->billing_month,
                    'narration' => $invoice->descriptions,
                    'branch_id' => $invoice->supplier->branch_id,
                ]);

                // Debit internal garage / Garage Inventory (Asset)
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $invoice->inv_date,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'SUP',
                    'account_id' => $debitInventoryAccountId,
                    'credit' => 0,
                    'debit' => $invoice->subtotal,
                    'billing_month' => $invoice->billing_month,
                    'branch_id' => $invoice->supplier->branch_id,
                    'narration' => $invoice->descriptions,
                ]);

                //Debit VAT on Purchase Account (VAT Purchase Account)
                if ($invoice->vat > 0) {
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $invoice->inv_date,
                        'reference_id' => $invoice->id,
                        'reference_type' => 'SUP',
                        'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                        'credit' => 0,
                        'debit' => $invoice->vat,
                        'billing_month' => $invoice->billing_month,
                        'branch_id' => $invoice->supplier->branch_id,
                        'narration' => 'Vat on supplier invoice ' . $invoice->inv_id,
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage() . $e->getTraceAsString(),
            ];
        }
    }

    private function uniqueInventoryBatchNo(): string
    {
        do {
            $batchNo = 'Batch-' . strtoupper(bin2hex(random_bytes(5)));
        } while (InventoryPurchase::where('batch_no', $batchNo)->exists());

        return $batchNo;
    }

    private function syncInvoiceInventory(SupplierInvoices $invoice, array $itemsData): void
    {
        $purchases = InventoryPurchase::where('inv_id', $invoice->id)->orderBy('id')->get();
        $batchNo = $purchases->first()->batch_no ?? $this->uniqueInventoryBatchNo();

        $incomingByItem = [];
        foreach ($itemsData as $index => $line) {
            $incomingByItem[(int) $line['item_id']][] = $index;
        }

        $matched = [];

        foreach ($purchases as $purchase) {
            $itemId = (int) $purchase->item_id;
            $nextIndex = null;
            if (! empty($incomingByItem[$itemId])) {
                $nextIndex = array_shift($incomingByItem[$itemId]);
            }

            if ($purchase->isUsed()) {
                if ($nextIndex === null) {
                    throw new \Exception('Cannot remove '.$purchase->item_name.'. Inventory from this item has already been used');
                }
                $line = $itemsData[$nextIndex];
                if (round((float) $line['qty'], 2) !== round((float) $purchase->quantity, 2)) {
                    throw new \Exception('Cannot change quantity of '.$purchase->item_name.'. Inventory from this item has already been used');
                }
                if (round((float) $line['rate'], 2) !== round((float) $purchase->unit_cost, 2)) {
                    throw new \Exception('Cannot change cost of '.$purchase->item_name.'. Inventory from this item has already been used');
                }
                $matched[$nextIndex] = true;
                continue;
            }

            if ($nextIndex === null) {
                $purchase->delete();
                continue;
            }

            $line = $itemsData[$nextIndex];
            $matched[$nextIndex] = true;
            $purchase->update([
                'item_name' => $line['item_des'],
                'quantity' => $line['qty'],
                'remaining_quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'purchase_date' => $invoice->inv_date,
                'supplier_id' => $invoice->supplier_id,
                'garage_id' => $invoice->garage_id,
            ]);
        }

        foreach ($itemsData as $index => $line) {
            if (isset($matched[$index])) {
                continue;
            }

            InventoryPurchase::create([
                'item_id' => $line['item_id'],
                'supplier_id' => $invoice->supplier_id,
                'item_name' => $line['item_des'],
                'purchase_date' => $invoice->inv_date,
                'inv_id' => $invoice->id,
                'quantity' => $line['qty'],
                'remaining_quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'batch_no' => $batchNo,
                'garage_id' => $invoice->garage_id,
                'created_by' => $invoice->created_by,
            ]);
        }
    }
}
