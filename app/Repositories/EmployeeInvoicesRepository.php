<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\HeadAccount;
use App\Models\EmployeeInvoiceItem;
use App\Models\EmployeeInvoices;
use App\Models\Transactions;
use App\Services\TransactionService;

class EmployeeInvoicesRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'inv_date',
        'employee_id',
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
        return EmployeeInvoices::class;
    }

    public function record($request, $id = null)
    {
        $input = $request->except(['item_id', '_method', '_token', 'qty', 'rate', 'amount', 'discount', 'tax']);
        $input['billing_month'] = $request->billing_month . '-01';

        if ($id) {
            $invoice = EmployeeInvoices::where('id', $id)->first();
            $exists = EmployeeInvoices::where('employee_id', $input['employee_id'])
                ->where('billing_month', $input['billing_month'])
                ->where('id', '!=', $id)
                ->first();

            if ($exists) {
                throw new \Exception('An invoice for this employee has already been generated for the selected billing month.');
            }
            $input['vat'] = array_sum($input['vat_amount']);
            $input['subtotal'] = $input['total_amount'] - $input['vat'];
            \Log::info('input:',[ $input]);
            $invoice->update($input);
            EmployeeInvoiceItem::where('inv_id', $id)->delete();
        } else {
            $exists = EmployeeInvoices::where('employee_id', $input['employee_id'])
                ->where('billing_month', $input['billing_month'])
                ->exists();

            if ($exists) {
                throw new \Exception('An invoice for this employee has already been generated for the selected billing month.');
            }

            $input['vat'] = array_sum($input['vat_amount']);
            $input['subtotal'] = $input['total_amount'] - $input['vat'];
            \Log::info('input:',[ $input]);

            $invoice = EmployeeInvoices::create($input);
        }

        foreach ($request['item_id'] as $key => $val) {
            if (!empty($request['item_id'][$key]) && $request['amount'][$key] > 0) {
                $amountValue = $request['amount'][$key];
                if (is_string($amountValue) && strpos($amountValue, 'AED') !== false) {
                    $amountValue = str_replace('AED', '', $amountValue);
                    $amountValue = str_replace(',', '', $amountValue);
                    $amountValue = trim($amountValue);
                }

                $amountValue = is_numeric($amountValue) ? round((float) $amountValue, 2) : 0;

                EmployeeInvoiceItem::create([
                    'item_id' => $request['item_id'][$key],
                    'qty' => $request['qty'][$key] ?? 0,
                    'rate' => $request['rate'][$key],
                    'amount' => $amountValue,
                    'discount' => $request['discount'][$key],
                    'inv_id' => $invoice->id,
                    'tax' => $request['tax'][$key]
                ]);
            }
        }

        $subtotal = $invoice->subtotal;
        $vat = $invoice->vat;
        $total = $invoice->total_amount;

        $transactionService = new TransactionService();

        if ($id) {
            $oldTransCode = Transactions::where('reference_type', 'EmployeeInvoice')
                ->where('reference_id', $id)
                ->value('trans_code');

            Transactions::where('reference_type', 'EmployeeInvoice')
                ->where('reference_id', $id)
                ->delete();

            $transCode = $oldTransCode ?: Account::trans_code();
        } else {
            $transCode = Account::trans_code();
        }

        if ($invoice->vat > 0) {
            $transactionService->recordTransaction([
                'account_id' => HeadAccount::TAX_ACCOUNT,
                'reference_id' => $invoice->id,
                'reference_type' => 'EmployeeInvoice',
                'trans_code' => $transCode,
                'trans_date' => $invoice->inv_date,
                'narration' => 'Vat on Employee Invoice #' . $invoice->id,
                'debit' => $vat ?? 0,
                'billing_month' => $invoice->billing_month,
            ]);
        }

        $transactionService->recordTransaction([
            'account_id' => $invoice->employee->account_id ?? null,
            'reference_id' => $invoice->id,
            'reference_type' => 'EmployeeInvoice',
            'trans_code' => $transCode,
            'trans_date' => $invoice->inv_date,
            'narration' => $invoice->descriptions ?? 'Employee Invoice #' . $invoice->id ,
            'credit' => $total ?? 0,
            'billing_month' => $invoice->billing_month,
        ]);

        $transactionService->recordTransaction([
            'account_id' => HeadAccount::SALARY_ACCOUNT,
            'reference_id' => $invoice->id,
            'reference_type' => 'EmployeeInvoice',
            'trans_code' => $transCode,
            'trans_date' => $invoice->inv_date,
            'narration' => 'Employee Invoice #' . $invoice->id . ' - ' . $invoice->descriptions,
            'debit' => $total ?? 0,
            'billing_month' => $invoice->billing_month,
        ]);

        return $invoice;
    }
}

