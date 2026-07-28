<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Support\GlobalAccounts;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Transactions;
use App\Services\TransactionService;

class RiderInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'rider_id',
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
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return RiderInvoices::class;
    }

    public function record($request, $id = null)
    {
        // $request = $request->except(['_method', '_token']);
        // $input = $request->all();

        $input = $request->except(['item_id', '_method', '_token', 'qty', 'rate', 'amount', 'discount', 'tax']);

        $input['billing_month'] = $request->billing_month.'-01';
        $rider = Riders::where('id', $input['rider_id'])->first();
        $input['branch_id'] = $rider->branch_id;

        if (empty($input['template_id']) && \App\Models\RiderInvoiceTemplate::isSchemaReady()) {
            $defaultTemplate = app(\App\Services\RiderInvoice\RiderInvoiceTemplateResolver::class)->defaultTemplate();
            if ($defaultTemplate) {
                $input['template_id'] = $defaultTemplate->id;
            }
        }

        if ($id) {
            $invoice = RiderInvoices::where('id', $id)->first();

            // Check for duplicate only if rider_id or billing_month is being changed
            $existingInvoice = RiderInvoices::where('rider_id', $input['rider_id'])
                ->where('billing_month', $input['billing_month'])
                ->where('id', '!=', $id) // Exclude current invoice
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this rider has already been generated for the selected billing month.');
            }

            $invoice->update($input);
            RiderInvoiceItem::where('inv_id', $id)->delete();
        } else {
            // Check for duplicate invoice for same rider and billing month
            $existingInvoice = RiderInvoices::where('rider_id', $input['rider_id'])
                ->where('billing_month', $input['billing_month'])
                ->first();

            if ($existingInvoice) {
                throw new \Exception('An invoice for this rider has already been generated for the selected billing month.');
            }

            $invoice = RiderInvoices::create($input);
        }

        foreach ($request['item_ids'] as $key => $val) {

            if (empty($request['item_ids'][$key])) {
                continue;
            }

            // Form amount may include line VAT (JS). Recalculate ex-VAT like import for DB + GL.
            $qty = (float) str_replace(',', '', (string) ($request['qty'][$key] ?? 0));
            $rate = (float) str_replace(',', '', (string) ($request['rate'][$key] ?? 0));
            $discount = (float) str_replace(',', '', (string) ($request['discount'][$key] ?? 0));

            if ($qty == 0) {
                continue;
            }

            $amountValue = round(($qty * $rate) - $discount, 2);
            $storedQty = (int) ($qty > 0 ? ceil($qty) : floor($qty));

            RiderInvoiceItem::create([
                'item_id' => $request['item_ids'][$key],
                'qty' => $storedQty,
                'rate' => $rate,
                'amount' => $amountValue,
                'discount' => $discount,
                'tax' => $request['tax'][$key] ?? 0,
                'inv_id' => $invoice->id,
            ]);
        }

        $invoice->refresh();
        $rider_amount = RiderInvoiceItem::where('inv_id', $invoice->id)->sum('amount');
        $total = $rider_amount;
        $vat = 0;
        if ($invoice->rider->vat == 1) {
            $vat = $total * (Common::getSetting('vat_percentage') / 100);
            $total = $total + $vat;
        }
        $transactionService = new TransactionService;

        $isUnpaid = (int) $invoice->status === 0;

        if ($id && $isUnpaid) {
            // Delete existing unpaid invoice GL only (match import: no GL for paid)
            $oldTransCode = Transactions::where('reference_type', 'Invoice')
                ->where('reference_id', $id)
                ->value('trans_code');

            Transactions::where('reference_type', 'Invoice')
                ->where('reference_id', $id)
                ->delete();

            $trans_code = $oldTransCode ? $oldTransCode : Account::trans_code();
        } else {
            $trans_code = Account::trans_code();
        }

        // Match import: post GL only for unpaid invoices
        if ($isUnpaid && $total != 0) {
            $absTotal = abs($total);
            $absSubtotal = abs($rider_amount);
            $isNegativeTotal = $total < 0;

            if ($vat != 0) {
                $absVat = abs($vat);
                $transactionService->recordTransaction([
                    'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                    'reference_id' => $invoice->id,
                    'reference_type' => 'Invoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $invoice->inv_date,
                    'narration' => 'Rider Invoice #'.$invoice->id.' - '.$invoice->descriptions,
                    'debit' => $vat > 0 ? $absVat : 0,
                    'credit' => $vat < 0 ? $absVat : 0,
                    'billing_month' => $invoice->billing_month,
                ]);
            }

            $transactionService->recordTransaction([
                'account_id' => $invoice->rider->account_id,
                'reference_id' => $invoice->id,
                'reference_type' => 'Invoice',
                'trans_code' => $trans_code,
                'trans_date' => $invoice->inv_date,
                'narration' => 'Rider Invoice #'.$invoice->id.' - '.$invoice->descriptions,
                'debit' => $isNegativeTotal ? $absTotal : 0,
                'credit' => $isNegativeTotal ? 0 : $absTotal,
                'billing_month' => $invoice->billing_month,
            ]);

            $transactionService->recordTransaction([
                'account_id' => GlobalAccounts::id('SALARY_ACCOUNT'),
                'reference_id' => $invoice->id,
                'reference_type' => 'Invoice',
                'trans_code' => $trans_code,
                'trans_date' => $invoice->inv_date,
                'narration' => 'Rider Invoice #'.$invoice->id.' - '.$invoice->descriptions,
                'debit' => $isNegativeTotal ? 0 : $absSubtotal,
                'credit' => $isNegativeTotal ? $absSubtotal : 0,
                'billing_month' => $invoice->billing_month,
            ]);
        }

        $invoice->total_amount = $total;
        $invoice->vat = $vat;
        $invoice->subtotal = $rider_amount;
        $invoice->save();

        return $invoice;
    }
}
