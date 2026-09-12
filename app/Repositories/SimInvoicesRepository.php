<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Support\GlobalAccounts;
use App\Models\Items;
use App\Models\SimInvoice;
use App\Models\SimInvoiceItem;
use App\Models\Sims;
use App\Models\Transactions;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SimInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'vendor_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'total_amount',
        'attachment',
        'status',
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return SimInvoice::class;
    }

    public function record($request, $id = null)
    {
        DB::beginTransaction();

        try {
            $input = $request->except([
                'sim_id',
                'charge_item_ids',
                'charges',
                'vat_percent',
                '_method',
                '_token',
                'vat_total',
                'total_amount_display',
                'subtotal',
                'company_id',
            ]);
            $input['vendor_id'] = $request->input('company_id');
            $input['billing_month'] = $request->billing_month . '-01';

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('invoice', 'public');
                $input['attachment'] = $path;
            }

            if ($id) {
                $invoice = SimInvoice::where('id', $id)->first();
                if (! $invoice) {
                    throw new \Exception('Invoice not found.');
                }

                $existingInvoice = SimInvoice::where('vendor_id', $input['vendor_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
                }

                if (isset($input['attachment']) && $invoice->attachment) {
                    Storage::disk('public')->delete($invoice->attachment);
                }

                $invoice->update($input);
                SimInvoiceItem::where('inv_id', $id)->delete();
            } else {
                $existingInvoice = SimInvoice::where('vendor_id', $input['vendor_id'])
                    ->where('billing_month', $input['billing_month'])
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
                }

                $input['status'] = 0;
                $invoice = SimInvoice::create($input);
            }

            $lines = $this->expandPivotToLines($request, (int) $input['vendor_id']);
            if (empty($lines)) {
                throw new \Exception('Add at least one SIM with a non-zero charge.');
            }

            $subtotal = 0.0;
            $totalVat = 0.0;

            foreach ($lines as $line) {
                $subtotal += $line['excl'];
                $totalVat += $line['tax'];

                SimInvoiceItem::create([
                    'inv_id' => $invoice->id,
                    'sim_id' => $line['sim_id'],
                    'item_id' => $line['item_id'],
                    'qty' => $line['qty'],
                    'rate' => $line['rate'],
                    'discount' => $line['discount'],
                    'tax' => $line['tax'],
                    'amount' => $line['amount'],
                ]);
            }

            $invoice->subtotal = round($subtotal, 2);
            $invoice->vat = round($totalVat, 2);
            $invoice->total_amount = round($subtotal + $totalVat, 2);
            $invoice->save();

            if ($id) {
                $oldTransCode = Transactions::where('reference_type', 'SimInvoice')
                    ->where('reference_id', $id)
                    ->value('trans_code');
                Transactions::where('reference_type', 'SimInvoice')
                    ->where('reference_id', $id)
                    ->delete();
                $this->recordTransactionsForInvoice($invoice, $oldTransCode ?: null);
            } else {
                $this->recordTransactionsForInvoice($invoice);
            }

            DB::commit();
            return $invoice;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Expand pivoted form arrays into flat charge lines.
     *
     * @return array<int, array{sim_id:int,item_id:int,qty:float,rate:float,discount:float,excl:float,tax:float,amount:float}>
     */
    public function expandPivotToLines($request, int $vendorId): array
    {
        $chargeItemIds = array_values(array_filter(array_map('intval', (array) $request->input('charge_item_ids', []))));
        if (empty($chargeItemIds)) {
            throw new \Exception('Select at least one charge item column.');
        }

        $validItems = Items::whereIn('id', $chargeItemIds)
            ->where('status', 1)
            ->whereJsonContains('owner', 'sim')
            ->get()
            ->keyBy(fn ($item) => (int) $item->id);

        foreach ($chargeItemIds as $itemId) {
            if (! $validItems->has($itemId)) {
                throw new \Exception('Invalid SIM charge item selected.');
            }
        }

        $defaultVat = (float) (Common::getSetting('vat_percentage') ?? 5);
        $simIds = (array) $request->input('sim_id', []);
        $vatPercents = (array) $request->input('vat_percent', []);
        $chargesMatrix = (array) $request->input('charges', []);

        $lines = [];
        $seenSims = [];

        foreach ($simIds as $rowIndex => $simId) {
            if ($simId === null || $simId === '') {
                continue;
            }
            $simId = (int) $simId;
            if (isset($seenSims[$simId])) {
                throw new \Exception('Duplicate SIM on the invoice form.');
            }
            $seenSims[$simId] = true;

            $sim = Sims::where('id', $simId)->where('company', $vendorId)->first();
            if (! $sim) {
                $sim = Sims::withTrashed()->find($simId);
                if ($sim && $sim->trashed()) {
                    throw new \Exception('SIM ' . $sim->number . ' is deleted.');
                }
                throw new \Exception('SIM does not belong to this Company.');
            }

            $vatPercent = isset($vatPercents[$rowIndex]) && $vatPercents[$rowIndex] !== ''
                ? (float) $vatPercents[$rowIndex]
                : $defaultVat;
            if ($vatPercent < 0) {
                $vatPercent = 0;
            }

            $rowCharges = (array) ($chargesMatrix[$rowIndex] ?? []);
            foreach ($chargeItemIds as $itemId) {
                $qty = round((float) ($rowCharges[$itemId] ?? 0), 2);
                if (abs($qty) < 0.00001) {
                    continue;
                }

                $item = $validItems->get($itemId);
                $rate = round((float) ($item->price ?? 0), 2);
                $discount = 0.0;
                $excl = round(($qty * $rate) - $discount, 2);
                $tax = $vatPercent > 0 ? round($excl * ($vatPercent / 100), 2) : 0.0;
                $amount = round($excl + $tax, 2);
                if (abs($amount) < 0.00001) {
                    continue;
                }

                $lines[] = [
                    'sim_id' => $simId,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'rate' => $rate,
                    'discount' => $discount,
                    'excl' => $excl,
                    'tax' => $tax,
                    'amount' => $amount,
                ];
            }
        }

        return $lines;
    }

    public function recordTransactionsForInvoice(SimInvoice $invoice, $transCode = null)
    {
        $invoice->load('company');
        $company = $invoice->company;
        if (! $company || ! $company->account_id) {
            throw new \Exception('Vendor does not have a linked ledger account. Please set the account for this vendor before creating invoices.');
        }

        $trans_code = $transCode !== null ? $transCode : Account::trans_code();
        $subtotal = (float) $invoice->subtotal;
        $vatAmount = (float) $invoice->vat;
        $totalAmount = (float) $invoice->total_amount;
        $narration = 'SIM Invoice #' . ($invoice->invoice_number ?? $invoice->id) . ' - ' . ($invoice->descriptions ?? 'SIM Invoice');

        $expenseAccountId = GlobalAccounts::id('SIM_EXPENSE_ACCOUNT');
        $vatAccountId = GlobalAccounts::id('VAT_PURCHASE_ACCOUNT');

        $expenseAccountExists = \App\Support\CompanyQuery::table('accounts')->where('id', $expenseAccountId)->whereNull('deleted_at')->exists();
        if (! $expenseAccountExists) {
            throw new \Exception('Expense account (ID ' . $expenseAccountId . ') not found in Chart of Accounts.');
        }

        $vatAccountExists = \App\Support\CompanyQuery::table('accounts')->where('id', $vatAccountId)->whereNull('deleted_at')->exists();
        if (! $vatAccountExists) {
            throw new \Exception('VAT account (ID ' . $vatAccountId . ') not found in Chart of Accounts.');
        }

        $transDate = $invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d');
        $billingMonthStr = $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m-d') : date('Y-m-01');

        $transactionService = new TransactionService();
        try {
            $transactionService->recordTransaction([
                'account_id' => $expenseAccountId,
                'reference_id' => $invoice->id,
                'reference_type' => 'SimInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $subtotal,
                'billing_month' => $billingMonthStr,
            ], true);

            if ($vatAmount > 0) {
                $transactionService->recordTransaction([
                    'account_id' => $vatAccountId,
                    'reference_id' => $invoice->id,
                    'reference_type' => 'SimInvoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $transDate,
                    'narration' => $narration . ' - VAT',
                    'debit' => $vatAmount,
                    'billing_month' => $billingMonthStr,
                ], true);
            }

            $transactionService->recordTransaction([
                'account_id' => $company->account_id,
                'reference_id' => $invoice->id,
                'reference_type' => 'SimInvoice',
                'trans_code' => $trans_code,
                'trans_date' => $transDate,
                'narration' => $narration,
                'credit' => $totalAmount,
                'billing_month' => $billingMonthStr,
            ], true);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to record transaction for SIM Invoice. ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a SIM invoice from parsed import rows.
     * Line tax and invoice.vat are calculated from header vat_percent.
     *
     * @param  array{vendor_id:int,inv_date:string,billing_month:string,reference_number:string,descriptions?:?string,notes?:?string,attachment?:?string,vat_percent?:float}  $header
     * @param  array<int, array{sim_id:int,item_id:int,qty:float,rate:float,discount?:float,tax?:float}>  $items
     */
    public function createFromImport(array $header, array $items): SimInvoice
    {
        DB::beginTransaction();

        try {
            $billingMonth = $header['billing_month'] . '-01';
            $existingInvoice = SimInvoice::where('vendor_id', $header['vendor_id'])
                ->where('billing_month', $billingMonth)
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this vendor has already been generated for the selected billing month.');
            }

            $vatPercent = max(0.0, (float) ($header['vat_percent'] ?? 0));

            $invoice = SimInvoice::create([
                'inv_date' => $header['inv_date'],
                'vendor_id' => $header['vendor_id'],
                'billing_month' => $billingMonth,
                'reference_number' => $header['reference_number'],
                'descriptions' => $header['descriptions'] ?? null,
                'notes' => $header['notes'] ?? null,
                'attachment' => $header['attachment'] ?? null,
                'status' => 0,
            ]);

            $subtotal = 0.0;
            $totalVat = 0.0;

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 1);
                $rate = (float) ($item['rate'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $excl = round(($qty * $rate) - $discount, 2);
                $tax = $vatPercent > 0
                    ? round($excl * ($vatPercent / 100), 2)
                    : round((float) ($item['tax'] ?? 0), 2);
                $amount = round($excl + $tax, 2);

                if (abs($amount) < 0.00001) {
                    continue;
                }

                $subtotal += $excl;
                $totalVat += $tax;

                SimInvoiceItem::create([
                    'inv_id' => $invoice->id,
                    'sim_id' => $item['sim_id'],
                    'item_id' => $item['item_id'],
                    'qty' => $qty,
                    'rate' => $rate,
                    'discount' => $discount,
                    'tax' => $tax,
                    'amount' => $amount,
                ]);
            }

            if ($subtotal == 0.0 && $totalVat == 0.0) {
                throw new \Exception('No valid SIM charge lines were found in the file.');
            }

            $invoice->subtotal = round($subtotal, 2);
            // Invoice vat column stores the calculated VAT amount (from vat_percent × subtotal lines).
            $invoice->vat = round($totalVat, 2);
            $invoice->total_amount = round($subtotal + $totalVat, 2);
            $invoice->save();

            $this->recordTransactionsForInvoice($invoice);

            DB::commit();
            return $invoice;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
