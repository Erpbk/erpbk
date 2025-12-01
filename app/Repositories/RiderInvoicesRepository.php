<?php

namespace App\Repositories;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\HeadAccount;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Transactions;
use App\Repositories\BaseRepository;
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
    'notes'
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
    //$request = $request->except(['_method', '_token']);
    //$input = $request->all();

    $input = $request->except(['item_id', '_method', '_token', 'qty', 'rate', 'amount', 'discount', 'tax']);

    $input['billing_month'] = $request->billing_month . "-01";

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

    foreach ($request['item_id'] as $key => $val) {

      if (!empty($request['item_id'][$key]) && $request['amount'][$key] > 0) {

        // Clean amount value - remove AED prefix and comma formatting
        $amountValue = $request['amount'][$key];
        if (is_string($amountValue) && strpos($amountValue, 'AED') !== false) {
          $amountValue = str_replace('AED', '', $amountValue);
          $amountValue = str_replace(',', '', $amountValue);
          $amountValue = trim($amountValue);
        }

        // Ensure amount is numeric
        $amountValue = is_numeric($amountValue) ? (float)$amountValue : 0;

        $dta['item_id'] = $request['item_id'][$key];
        $dta['qty'] = $request['qty'][$key] ?? 0;
        $dta['rate'] = $request['rate'][$key];
        $dta['amount'] = $amountValue;
        //$dta['tax'] = $request['tax'][$key];
        $dta['discount'] = $request['discount'][$key];
        $dta['inv_id'] = $invoice->id;
        RiderInvoiceItem::create($dta);
      }
    }
    $rider_amount = RiderInvoiceItem::where('inv_id', $invoice->id)->sum('amount');
    $total = $rider_amount;
    $vat = 0;
    if ($invoice->rider->vat == 1) {
      $vat = $total * (Common::getSetting('vat_percentage') / 100);
      $total = $total + $vat;
    }
    $trans_code = Account::trans_code();
    $transactionService = new TransactionService();

    if ($id) {
      $trans_code = Transactions::where('reference_type', 'Invoice')->where('reference_id', $id)->value('trans_code');
      $transactionService->deleteTransaction($trans_code);
    }


    if ($invoice->rider->vat == 1) {

      $transactionData = [
        'account_id' => HeadAccount::TAX_ACCOUNT, //VAT Account asked to set by Adnan 08-05-2025
        'reference_id' => $invoice->id,
        'reference_type' => 'Invoice',
        'trans_code' => $trans_code,
        'trans_date' => $invoice->inv_date,
        'narration' => "Rider Invoice #" . $invoice->id . ' - ' . $invoice->descriptions,
        'debit' => $vat ?? 0,
        'billing_month' => $invoice->billing_month,
      ];
      $transactionService->recordTransaction($transactionData);
    }

    $transactionData = [
      'account_id' => $invoice->rider->account_id,
      'reference_id' => $invoice->id,
      'reference_type' => 'Invoice',
      'trans_code' => $trans_code,
      'trans_date' => $invoice->inv_date,
      'narration' => "Rider Invoice #" . $invoice->id . ' - ' . $invoice->descriptions,
      //'debit' => $request['dr_amount'][$key] ?? 0,
      'credit' => $total ?? 0,
      'billing_month' => $invoice->billing_month,
    ];
    $transactionService->recordTransaction($transactionData);


    $transactionData = [
      'account_id' => HeadAccount::SALARY_ACCOUNT, //Salary Account asked to set by Adnan 08-03-2025
      'reference_id' => $invoice->id,
      'reference_type' => 'Invoice',
      'trans_code' => $trans_code,
      'trans_date' => $invoice->inv_date,
      'narration' => "Rider Invoice #" . $invoice->id . ' - ' . $invoice->descriptions,
      'debit' => $rider_amount ?? 0,
      'billing_month' => $invoice->billing_month,
    ];
    $transactionService->recordTransaction($transactionData);


    $invoice->total_amount = $total;
    $invoice->vat = $vat;
    $invoice->subtotal = $rider_amount;
    $invoice->save();

    return $invoice;
  }
}
