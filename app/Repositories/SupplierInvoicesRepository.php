<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Models\SupplierInvoicesItem; 
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Repositories\BaseRepository;
use App\Services\TransactionService;

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
        'notes'
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
        $input = $request->except([
            'item_id', '_method', '_token', 'qty', 'rate', 'amount', 'discount', 'tax'
        ]);

        $input['billing_month'] = $request->billing_month . "-01";

        if ($id) {
            $invoice = SupplierInvoices::where('id', $id)->first();
            $invoice->update($input);
            // SupplierInvoicesItem::where('inv_id', $id)->delete();
        } else {
            $invoice = SupplierInvoices::create($input);
        }

        foreach ($request['item_id'] as $key => $val) {
    if (!empty($request['item_id'][$key]) && $request['amount'][$key] > 0) {
        $itemId = $request['item_row_id'][$key] ?? null; // Optional hidden input for existing item ID

        $dta = [
            'item_id' => $request['item_id'][$key],
            'qty' => $request['qty'][$key] ?? 0,
            'rate' => $request['rate'][$key],
            'amount' => $request['amount'][$key],
            'tax' => $request['tax'][$key],
            'discount' => $request['discount'][$key],
            'inv_id' => $invoice->id
        ];

        if ($itemId) {
            SupplierInvoicesItem::where('id', $itemId)->update($dta); // Update existing line
        } else {
            SupplierInvoicesItem::create($dta); // Add new line
        }
    }
}

        $trans_code = Account::trans_code();
        $transactionService = new TransactionService();

        if ($id) {
            $trans_code = Transactions::where('reference_type', 'Invoice')
                ->where('reference_id', $id)
                ->value('trans_code');
            $transactionService->deleteTransaction($trans_code);
        }

        // Credit transaction to Supplier's account
        $transactionData = [
            'account_id' => $invoice->supplier->account_id,
            'reference_id' => $invoice->id,
            'reference_type' => 'Invoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoice->inv_date,
            'narration' => "Supplier Invoice #{$invoice->id} - {$invoice->descriptions}",
            'credit' => $invoice->total_amount ?? 0,
            'billing_month' => $invoice->billing_month,
        ];
        $transactionService->recordTransaction($transactionData);

        // Debit from Salary Account (or relevant account)
        $transactionData = [
            'account_id' => HeadAccount::SALARY_ACCOUNT, // Adjust if needed
            'reference_id' => $invoice->id,
            'reference_type' => 'Invoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoice->inv_date,
            'narration' => "Supplier Invoice #{$invoice->id} - {$invoice->descriptions}",
            'debit' => $invoice->total_amount ?? 0,
            'billing_month' => $invoice->billing_month,
        ];
        $transactionService->recordTransaction($transactionData);

        return $invoice;
    }
}
