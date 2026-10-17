<?php

namespace App\Services;

use App\Exceptions\GlobalAccountNotConfiguredException;
use App\Helpers\Account;
use App\Helpers\General;
use App\Models\Accounts as LedgerAccount;
use App\Models\Accounts\JournalVoucher;
use App\Models\Accounts\Transaction;
use App\Models\Accounts\TransactionAccount;
use App\Models\Sim;
use App\Models\SimCharge;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use App\Support\PublicStorageDisk;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{

  private $TransactionService;

  public function __construct(TransactionService $TransactionService)
  {
    $this->TransactionService = $TransactionService;
  }

  public function JournalVoucher($request)
  {

    $rules = [
      'trans_date' => 'required',
      'payment_type' => 'required',
      'reference_number' => 'required|string|max:255',
      'branch_id' => 'required|exists:branches,id',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      'payment_type.required' => 'Payment Type Required',
      'reference_number.required' => 'Reference Number is required',
      'branch_id.required' => 'Please Select a Branch',
    ];
    $request->validate($rules, $message);
    $data = $request->except(['_method', '_token', 'v_trans_code', 'narration', 'dr_amount', 'cr_amount', 'account_id', 'id']);
    //$data = $request->all();
    $id = $request->v_trans_code;


    $data['remarks'] = ($request->voucher_type === 'TRF')
      ? 'Funds Transfer'
      : 'Journal Voucher';
    $data['amount'] = array_sum($request->cr_amount);
    $data['billing_month'] = $request->billing_month;


    if ($id == '' || $id == 0) {
      $trans_code = Account::trans_code();
      $data['Created_By'] = \Auth::user()->id;
      $data['trans_code'] = $trans_code;

      $ret = Vouchers::create($data);

      foreach ($request->account_id as $key => $val) {
        if (!empty($request['account_id'][$key]) && $request['dr_amount'][$key] > 0) {
          //  transaction recording
          $transactionData = [
            'account_id' => $request['account_id'][$key],
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $request['narration'][$key],
            'debit' => $request['dr_amount'][$key] ?? 0,
            //'credit' => $data['credit'] ?? 0,
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $request['branch_id'],
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
        if (!empty($request['account_id'][$key]) && $request['cr_amount'][$key] > 0) {

          //  transaction recording
          $transactionData = [
            'account_id' => $request['account_id'][$key],
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $request['narration'][$key],
            //'debit' => $request['dr_amount'][$key] ?? 0,
            'credit' => $request['cr_amount'][$key] ?? 0,
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $request['branch_id'],
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
      }
    } else {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->first();
      $ret->update($data);
      Transactions::where('trans_code', $id)->delete();

      foreach ($request->account_id as $key => $val) {
        if (!empty($request['account_id'][$key]) && $request['dr_amount'][$key] > 0) {

          //  transaction recording
          $transactionData = [
            'account_id' => $request['account_id'][$key],
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $id,
            'trans_date' => $data['trans_date'],
            'narration' => $request['narration'][$key],
            'debit' => $request['dr_amount'][$key] ?? 0,
            //'credit' => $data['credit'] ?? 0,
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $data['branch_id'] ,
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
        if (!empty($request['account_id'][$key]) && $request['cr_amount'][$key] > 0) {

          //  transaction recording
          $transactionData = [
            'account_id' => $request['account_id'][$key],
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $id,
            'trans_date' => $data['trans_date'],
            'narration' => $request['narration'][$key],
            //'debit' => $request['dr_amount'][$key] ?? 0,
            'credit' => $request['cr_amount'][$key] ?? 0,
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $data['branch_id'],
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
      }
    }
    return $ret;
  }

  /**
   * Funds transfer between two bank accounts.
   * The sender is credited for the transfer plus bank charges. The receiver is
   * debited for the transfer amount. Bank charges are a separate debit on the
   * bank charges account, the same way a payment posts that fee as its own line.
   */
  public function FundsTransfer($request)
  {
    if (! $request->hasFile('attachment')) {
      $request->files->remove('attachment');
    } else {
      $file = $request->file('attachment');
      if (! $file || ! $file->isValid()) {
        $request->files->remove('attachment');
      }
    }

    $request->validate([
      'trans_date' => 'required|date',
      'payment_type' => 'required|integer|in:1,2,3,4',
      'billing_month' => 'required',
      'reference_number' => 'required|string|max:255',
      'branch_id' => 'required|exists:branches,id',
      'from_account_id' => 'required|integer|exists:accounts,id',
      'to_account_id' => 'required|integer|different:from_account_id|exists:accounts,id',
      'amount' => 'required|numeric|min:0.01',
      'bank_charges' => 'nullable|numeric|min:0',
      'description' => 'nullable|string|max:1000',
      'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    ], [
      'trans_date.required' => 'Transaction date is required',
      'payment_type.required' => 'Payment mode is required',
      'payment_type.in' => 'Please select a payment mode',
      'reference_number.required' => 'Reference number is required',
      'branch_id.required' => 'Please select a branch',
      'from_account_id.required' => 'Please select a sending account',
      'to_account_id.required' => 'Please select a receiving account',
      'to_account_id.different' => 'Sending and receiving accounts must be different',
      'amount.required' => 'Transfer amount is required',
      'amount.min' => 'Transfer amount must be greater than zero',
      'bank_charges.min' => 'Bank charges cannot be negative',
    ]);

    $fromAccountId = (int) $request->input('from_account_id');
    $toAccountId = (int) $request->input('to_account_id');
    $transferAmount = round((float) $request->input('amount'), 2);
    $bankCharges = round(max(0, (float) $request->input('bank_charges', 0)), 2);
    $totalAmount = round($transferAmount + $bankCharges, 2);
    $billingMonth = $this->normalizeBillingMonth($request->billing_month ?? $request->input('billing_month'));
    $description = trim((string) $request->input('description', ''));
    if ($description === '') {
      $description = 'Funds Transfer';
    }
    $branchId = $request->input('branch_id');

    $bankParentId = (int) GlobalAccounts::id('BANK');
    $bankAccounts = LedgerAccount::query()
      ->whereIn('id', [$fromAccountId, $toAccountId])
      ->get()
      ->keyBy('id');

    foreach (['from_account_id' => $fromAccountId, 'to_account_id' => $toAccountId] as $field => $accountId) {
      $account = $bankAccounts->get($accountId);
      if (! $account || (int) $account->parent_id !== $bankParentId || $account->account_type !== 'Asset') {
        throw ValidationException::withMessages([
          $field => 'Select a bank account.',
        ]);
      }
    }

    $bankChargesAccountId = null;
    if ($bankCharges > 0.009) {
      try {
        $bankChargesAccountId = GlobalAccounts::account('BANK_CHARGES')->id;
      } catch (GlobalAccountNotConfiguredException $e) {
        throw ValidationException::withMessages([
          'bank_charges' => 'Bank charges account is not configured. Set it in Global Accounts before entering bank charges.',
        ]);
      }
    }

    $transCodeId = $request->input('v_trans_code');
    $isUpdate = ! ($transCodeId === null || $transCodeId === '' || (int) $transCodeId === 0);

    return DB::transaction(function () use (
      $request,
      $fromAccountId,
      $toAccountId,
      $transferAmount,
      $bankCharges,
      $totalAmount,
      $billingMonth,
      $description,
      $branchId,
      $bankChargesAccountId,
      $transCodeId,
      $isUpdate
    ) {
      $payload = [
        'trans_date' => $request->input('trans_date'),
        'payment_type' => $request->input('payment_type'),
        'billing_month' => $billingMonth,
        'reference_number' => $request->input('reference_number'),
        'branch_id' => $branchId,
        'voucher_type' => 'TRF',
        'remarks' => 'Funds Transfer',
        'amount' => $totalAmount,
        'payment_from' => $fromAccountId,
        'payment_to' => $toAccountId,
        'custom_field_values' => $request->input('custom_field_values', $request->input('voucher_custom_fields', [])),
      ];

      if ($isUpdate) {
        $voucher = Vouchers::where('trans_code', $transCodeId)->first();
        if (! $voucher) {
          throw ValidationException::withMessages([
            'error' => 'Funds transfer voucher was not found.',
          ]);
        }
        $payload['Updated_By'] = auth()->id();
        $voucher->update($payload);
        Transactions::where('trans_code', $transCodeId)->delete();
        $transCode = $transCodeId;
      } else {
        $transCode = Account::trans_code();
        $payload['Created_By'] = auth()->id();
        $payload['trans_code'] = $transCode;
        $payload['status'] = 1;
        $voucher = Vouchers::create($payload);
      }

      $senderNarration = $description;
      if ($bankCharges > 0.009) {
        $senderNarration = trim($description.' | Bank charges '.number_format($bankCharges, 2));
      }

      $this->TransactionService->recordTransaction([
        'account_id' => $fromAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'Voucher',
        'trans_code' => $transCode,
        'trans_date' => $payload['trans_date'],
        'narration' => $senderNarration,
        'credit' => $totalAmount,
        'billing_month' => $billingMonth,
        'branch_id' => $branchId,
      ], true);

      $this->TransactionService->recordTransaction([
        'account_id' => $toAccountId,
        'reference_id' => $voucher->id,
        'reference_type' => 'Voucher',
        'trans_code' => $transCode,
        'trans_date' => $payload['trans_date'],
        'narration' => $description,
        'debit' => $transferAmount,
        'billing_month' => $billingMonth,
        'branch_id' => $branchId,
      ], true);

      if ($bankCharges > 0.009 && $bankChargesAccountId) {
        $this->TransactionService->recordTransaction([
          'account_id' => $bankChargesAccountId,
          'reference_id' => $voucher->id,
          'reference_type' => 'Voucher',
          'trans_code' => $transCode,
          'trans_date' => $payload['trans_date'],
          'narration' => 'Bank charges for ( '.$description.' )',
          'debit' => $bankCharges,
          'billing_month' => $billingMonth,
          'branch_id' => $branchId,
        ], true);
      }

      if ($request->hasFile('attachment')) {
        $file = $request->file('attachment');
        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName()) ?: 'document';
        $fileName = time().'_'.$safeBase;
        $oldFile = $voucher->attach_file ? basename((string) $voucher->attach_file) : null;
        PublicStorageDisk::storeUploadedFile($file, 'vouchers', $fileName);
        if ($oldFile && $oldFile !== $fileName) {
          PublicStorageDisk::delete('vouchers/'.$oldFile);
        }
        $voucher->attach_file = $fileName;
        $voucher->save();
      }

      return $voucher;
    });
  }

  private function normalizeBillingMonth($value): string
  {
    $value = trim((string) $value);
    if (preg_match('/^(\d{4}-\d{2})/', $value, $matches)) {
      return $matches[1].'-01';
    }

    return date('Y-m-01');
  }

  public function InvoiceVoucher($request)
  {


    $rules = [
      'trans_date' => 'required',
      'payment_from' => 'required',
      'payment_type' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      'payment_from.required' => 'Bank/Cash Account Required',
      'payment_type.required' => 'Payment Type Required',
    ];
    $request->validate($rules, $message);
    $data = $request->except(['_method', '_token', 'narration', 'dr_amount', 'cr_amount', 'trans_acc_id', 'v_trans_code', 'id', 'VID', 'RID', 'riderBalance', 'inv_id', 'riderInvoiceBalance']);
    $data['trans_date'] = $request->trans_date;
    $data['posting_date'] = $request->trans_date;
    $data['billing_month'] = $request->billing_month;

    if ($request->invoice_voucher_type == 5) {
      $data['payment_to'] = $request->RID;
      $data['remarks'] = 'Payment to Rider direct....';
    } else {
      $data['payment_to'] = $request->VID;
      $data['remarks'] = 'Payment to Rider of Vendor....';
    }

    $id = $request->v_trans_code;
    if ($id) {
      Transaction::where('trans_code', $id)->delete();
      $trans_code = $id;
    } else {
      $trans_code = Account::trans_code();
    }
    //account entry
    $tData['trans_date'] = $request->trans_date;
    $tData['posting_date'] = $request->trans_date;
    //$tData['payment_to']=$request->RID??$request->VID;
    $tData['payment_from'] = $request->payment_from;
    $tData['status'] = 1;
    $tData['vt'] = 5;
    $tData['trans_code'] = $trans_code;
    $tData['payment_type'] = @$request->payment_type;

    /* if (isset($request->attach_file)) {
        $doc = $request->attach_file;
        $extension = $doc->extension();
        $name = time() . '.' . $extension;
        $doc->storeAs('voucher', $name);
        $data['attach_file'] = $name;
    } */

    $total_amount = 0;
    $count = count($request->amount);


    for ($i = 0; $i < $count; $i++) {
      if ($request['amount'][$i] > 0) {
        $total_amount += $request['amount'][$i];

        $RTAID = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $request['id'][$i]])->value('id');
        $tData['Created_By'] = \Auth::user()->id;
        $tData['SID'] = $request['inv_id'][$i] ?? null;
        //dr to rider
        $tData['billing_month'] = $request->billing_month; //$request['inv_billing_month'][$i];
        $tData['trans_acc_id'] = $RTAID;
        $tData['dr_cr'] = 1;
        $tData['amount'] = $request['amount'][$i];
        $tData['narration'] = $request['narration'][$i];
        Transaction::create($tData);
      }
    }

    //cr to cash bank
    $tData['SID'] = 0;
    $tData['trans_acc_id'] = $request->payment_from;
    $tData['dr_cr'] = 2;
    $tData['amount'] = $total_amount;
    Transaction::create($tData);
    //creating Payment Voucher transaction


    $data['amount'] = $total_amount;
    if ($id) {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->update($data);
    } else {
      $data['Created_By'] = \Auth::user()->id;

      $data['trans_code'] = $trans_code;

      $ret = Vouchers::create($data);
    }
    return $ret;
  }

  public function SimVoucher($request)
  {

    $rules = [
      'trans_date' => 'required',
      /* 'ref_id' => 'required', */
      /*             'CID' => 'required',
       */
      'amount' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /*             'ref_id.required' => 'Please Select Sim',
       */
      'amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type;
    $data['payment_from'] = $request->payment_from;
    $data['billing_month'] = $request->billing_month;
    $data['ref_id'] = @$request->ref_id;
    $id = $request->v_trans_code;
    if ($id) {
      Transaction::where('trans_code', $id)->delete();
      $trans_code = $id;
    } else {
      $trans_code = Account::trans_code();
    }
    $tData['billing_month'] = $request->billing_month;
    $tData['trans_date'] = $request->trans_date;
    $tData['posting_date'] = $request->trans_date;
    $tData['trans_code'] = $trans_code;
    $tData['status'] = 1;
    $tData['vt'] = 9;
    $tData['Created_By'] = \Auth::user()->id;
    $tData['SID'] = $request['ref_id'];
    $tData['payment_type'] = @$request->payment_type;
    $tData['branch_id'] = $data['branch_id'] ;
    //dr to rider

    $total_amount = 0;
    $count = count($request->amount);


    for ($i = 0; $i < $count; $i++) {
      if ($request['amount'][$i] > 0) {
        $total_amount += $request['amount'][$i];

        $RTAID = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $request['id'][$i]])->value('id');
        //dr to rider
        $tData['trans_acc_id'] = $RTAID;
        $tData['dr_cr'] = 1;
        $tData['amount'] = $request['amount'][$i];
        $tData['narration'] = $request['narration'][$i];
        Transaction::create($tData);
      }
    }



    //cr to compnay
    //$tData['narration'] = $request->sim_narration;
    $tData['trans_acc_id'] = 811; //426;//$request->payment_from;
    $tData['dr_cr'] = 2;
    $tData['amount'] = $total_amount;
    Transaction::create($tData);

    //creating/updating voucher
    $data['amount'] = $total_amount;
    $data['custom_field_values'] = $request->input('custom_field_values', []);
    if ($id) {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->update($data);
    } else {
      $data['trans_code'] = $trans_code;
      $data['Created_By'] = \Auth::user()->id;

      $ret = Vouchers::create($data);
    }
  }

  public function FuelVoucher($request)
  {

    $rules = [
      'trans_date' => 'required',
      /* 'ref_id' => 'required', */
      /*             'CID' => 'required',
       */
      'amount' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /*             'ref_id.required' => 'Please Select Sim',
       */
      'amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type;
    $data['payment_from'] = $request->payment_from;
    $data['billing_month'] = $request->billing_month;
    $data['ref_id'] = @$request->ref_id;
    $id = $request->v_trans_code;
    if ($id) {
      Transaction::where('trans_code', $id)->delete();
      $trans_code = $id;
    } else {
      $trans_code = Account::trans_code();
    }
    $tData['billing_month'] = $request->billing_month;
    $tData['trans_date'] = $request->trans_date;
    $tData['posting_date'] = $request->trans_date;
    $tData['trans_code'] = $trans_code;
    $tData['status'] = 1;
    $tData['vt'] = 11;
    $tData['Created_By'] = \Auth::user()->id;
    $tData['SID'] = $request['ref_id'];
    $tData['payment_type'] = @$request->payment_type;

    //dr to rider

    $total_amount = 0;
    $count = count($request->amount);


    for ($i = 0; $i < $count; $i++) {
      if ($request['amount'][$i] > 0) {
        $total_amount += $request['amount'][$i];

        $RTAID = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $request['id'][$i]])->value('id');
        //dr to rider
        $tData['trans_acc_id'] = $RTAID;
        $tData['dr_cr'] = 1;
        $tData['amount'] = $request['amount'][$i];
        $tData['narration'] = $request['narration'][$i];
        Transaction::create($tData);
      }
    }



    //cr to compnay
    //$tData['narration'] = $request->sim_narration;
    $tData['trans_acc_id'] = $request->payment_from ?? 617;
    $tData['dr_cr'] = 2;
    $tData['amount'] = $total_amount;
    Transaction::create($tData);

    //creating/updating voucher
    $data['amount'] = $total_amount;
    $data['custom_field_values'] = $request->input('custom_field_values', []);
    if ($id) {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->update($data);
    } else {
      $data['trans_code'] = $trans_code;
      $data['Created_By'] = \Auth::user()->id;

      $ret = Vouchers::create($data);
    }
  }

  public function RtaVoucher($request)
  {

    $rules = [
      'trans_date' => 'required',
      /*  'ref_id' => 'required', */
      /*             'CID' => 'required',
       */
      'amount' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /* 'ref_id.required' => 'Please Select Bike', */
      'amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type;
    $data['payment_from'] = $request->payment_from;
    $data['billing_month'] = $request->billing_month;
    $data['direction'] = $request->direction;
    $data['toll_gate'] = $request->toll_gate;
    $data['trip_date'] = $request->trip_date;
    $data['lease_company'] = $request->lease_company;
    $data['ref_id'] = $request->ref_id;

    $id = $request->v_trans_code;
    if ($id) {
      Transaction::where('trans_code', $id)->delete();
      $trans_code = $id;
    } else {
      $trans_code = Account::trans_code();
    }

    $tData['billing_month'] = $request->billing_month;
    $tData['trans_date'] = $request->trans_date;
    $tData['posting_date'] = $request->trans_date;
    $tData['trans_code'] = $trans_code;
    $tData['status'] = 1;
    $tData['vt'] = 8;
    $tData['Created_By'] = \Auth::user()->id;
    $tData['SID'] = $request['ref_id'];
    $tData['payment_type'] = @$request->payment_type;

    //dr to rider

    $total_amount = 0;
    $count = count($request->amount);


    for ($i = 0; $i < $count; $i++) {
      if ($request['amount'][$i] > 0) {
        $total_amount += $request['amount'][$i];

        $RTAID = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $request['id'][$i]])->value('id');
        //dr to rider
        $tData['trans_acc_id'] = $RTAID;
        $tData['dr_cr'] = 1;
        $tData['amount'] = $request['amount'][$i];
        $tData['narration'] = $request['narration'][$i];
        Transaction::create($tData);
      }
    }



    //cr to compnay
    //$tData['narration'] = $request->sim_narration;
    //$tData['trans_acc_id'] = $request->payment_from;
    $tData['trans_acc_id'] = 425; //TransactionAccount::where(['PID' => 22, 'parent_type' => $request->LCID])->value('id');
    $tData['dr_cr'] = 2;
    $tData['amount'] = $total_amount;
    Transaction::create($tData);

    //creating/updating voucher
    $data['amount'] = $total_amount;
    $data['custom_field_values'] = $request->input('custom_field_values', []);
    if ($id) {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->update($data);
    } else {
      $data['trans_code'] = $trans_code;
      $data['Created_By'] = \Auth::user()->id;

      $ret = Vouchers::create($data);
    }
  }
  function loanvoucher($request)
  {
    $rules = [
      'trans_date' => 'required',
      /*  'ref_id' => 'required', */
      /*             'CID' => 'required',
       */
      'amount' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /* 'ref_id.required' => 'Please Select Bike', */
      'amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
  }
  public function RentVoucher($request)
  {

    $rules = [
      'trans_date' => 'required',
      'ref_id' => 'required',
      /*             'CID' => 'required',
       */
      'amount' => 'required',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      'ref_id.required' => 'Please Select Sim',
      'amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type;
    $data['payment_from'] = $request->payment_from;
    $data['billing_month'] = $request->billing_month;
    $data['direction'] = $request->direction;
    $data['toll_gate'] = $request->toll_gate;
    $data['trip_date'] = $request->trip_date;
    $data['lease_company'] = $request->lease_company;
    $data['vendor_id'] = $request->vendor_id;
    $data['ref_id'] = $request->ref_id;

    $id = $request->v_trans_code;
    if ($id) {
      Transaction::where('trans_code', $id)->delete();
      $trans_code = $id;
    } else {
      $trans_code = Account::trans_code();
    }

    $tData['billing_month'] = $request->billing_month;
    $tData['trans_date'] = $request->trans_date;
    $tData['posting_date'] = $request->trans_date;
    $tData['trans_code'] = $trans_code;
    $tData['status'] = 1;
    $tData['vt'] = 10;
    $tData['Created_By'] = \Auth::user()->id;
    $tData['SID'] = $request['ref_id'];
    $tData['payment_type'] = @$request->payment_type;

    //dr to rider

    $total_amount = 0;
    $count = count($request->amount);


    for ($i = 0; $i < $count; $i++) {
      if ($request['amount'][$i] > 0) {
        $total_amount += $request['amount'][$i];

        $RTAID = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $request['id'][$i]])->value('id');
        //dr to rider
        $tData['trans_acc_id'] = $RTAID;
        $tData['dr_cr'] = 1;
        $tData['amount'] = $request['amount'][$i];
        $tData['narration'] = $request['narration'][$i];
        Transaction::create($tData);
      }
    }



    //cr to compnay
    //$tData['narration'] = $request->sim_narration;
    //$tData['trans_acc_id'] = $request->payment_from;
    $tData['trans_acc_id'] = 441; //TransactionAccount::where(['PID' => 22, 'parent_type' => $request->LCID])->value('id');
    $tData['dr_cr'] = 2;
    $tData['amount'] = $total_amount;
    Transaction::create($tData);

    //creating/updating voucher
    $data['amount'] = $total_amount;
    $data['reference_number'] = $request->reference_number;
    $data['custom_field_values'] = $request->input('custom_field_values', []);
    if ($id) {
      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->update($data);
    } else {
      $data['Created_By'] = \Auth::user()->id;

      $data['trans_code'] = $trans_code;

      $ret = Vouchers::create($data);
    }
  }

  public function DefaultVoucher($request, $dr_cr)
  {
    //1 for debit 2 for credit
    if ($dr_cr == 'debit') {
      $rider_dr_cr = 'debit';
      $payment_dr_cr = 'credit';
    } else {
      $rider_dr_cr = 'credit';
      $payment_dr_cr = 'debit';
    }
    $rules = [
      'trans_date' => 'required',
      /* 'ref_id' => 'required', */
      /*             'CID' => 'required',
       */
      'dr_amount' => 'required',
      'reference_number' => 'required|string|max:255',
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /*             'ref_id.required' => 'Please Select Sim',
       */
      'dr_amount.required' => 'Amount should be greater than 0',
      'reference_number.required' => 'Reference Number is required',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $accountIds = array_values($request->account_id ?? []);
    $drAmounts = array_values($request->dr_amount ?? []);
    $crAmounts = array_values($request->cr_amount ?? []);
    $narrations = array_values($request->narration ?? []);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type ?? 0;
    $data['billing_month'] = $request->billing_month;
    $data['reference_number'] = $request->reference_number;
    $data['branch_id'] = $request->branch_id ;
    $data['remarks'] = General::VoucherType($request->voucher_type) . ' Month of ' . date('M-Y', strtotime($data['billing_month']));
    $data['amount'] = array_sum($drAmounts);
    $data['custom_field_values'] = $request->input('custom_field_values', []);
    $id = $request->v_trans_code;

    $typedReferenceTypes = ['AL', 'COD', 'PN', 'INC', 'PAY', 'VC'];
    $referenceType = in_array($request->voucher_type, $typedReferenceTypes, true)
      ? $request->voucher_type
      : 'Voucher';

    $paymentFrom = $request->payment_from ?? null;
    if (empty($paymentFrom)) {
      foreach ($accountIds as $key => $val) {
        if (!empty($val) && !empty($crAmounts[$key]) && $crAmounts[$key] > 0) {
          $paymentFrom = $val;
          break;
        }
      }
    }
    $data['payment_from'] = $paymentFrom ?: ($accountIds[0] ?? 0);

    // ALLOW duplicate debit/credit for these voucher types:
    $skipDupAccountTypes = ['AL', 'COD', 'PN', 'INC', 'PAY', 'VC'];
    if (!in_array($request->voucher_type, $skipDupAccountTypes, true)) {
      $head_account_id = $request->payment_from;
      $party_accounts = array_filter($accountIds, fn($accountId) => !empty($accountId) && $accountId != 0);
      if (!empty($head_account_id) && $head_account_id != 0 && in_array($head_account_id, $party_accounts)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
          'account_id' => ['Credit/Head Account must not be the same as any Debit/Party Account.']
        ]);
      }
    }

    if ($id) {
      $this->TransactionService->deleteTransaction($id);
      $trans_code = $id;

      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->first();
      if (!$ret) {
        throw \Illuminate\Validation\ValidationException::withMessages([
          'v_trans_code' => ['Voucher not found for update.']
        ]);
      }
      $ret->update($data);
    } else {
      $trans_code = Account::trans_code();
      $data['trans_code'] = $trans_code;
      $data['Created_By'] = \Auth::user()->id;

      $ret = Vouchers::create($data);
    }

    //dr/cr logic for each party/account: ALLOW both a debit and a credit per account in the same voucher
    foreach ($accountIds as $key => $val) {
      if (!empty($val) && $val != 0) {
        if (!empty($drAmounts[$key]) && $drAmounts[$key] > 0) {
          $transactionData = [
            'account_id' => $val,
            'reference_id' => $ret->id,
            'reference_type' => $referenceType,
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $narrations[$key] ?? '',
            'debit' => $drAmounts[$key],
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $data['branch_id'],
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
        if (!empty($crAmounts[$key]) && $crAmounts[$key] > 0) {
          $transactionData = [
            'account_id' => $val,
            'reference_id' => $ret->id,
            'reference_type' => $referenceType,
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $narrations[$key] ?? '',
            'credit' => $crAmounts[$key],
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
            'branch_id' => $data['branch_id'],
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
      }
    }

    return $ret;
  }
}
