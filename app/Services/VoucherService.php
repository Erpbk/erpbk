<?php

namespace App\Services;

use App\Helpers\Account;
use App\Helpers\General;
use App\Models\Accounts\JournalVoucher;
use App\Models\Accounts\Transaction;
use App\Models\Accounts\TransactionAccount;
use App\Models\Sim;
use App\Models\SimCharge;
use App\Models\Transactions;
use App\Models\Vouchers;

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
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      'payment_type.required' => 'Payment Type Required',
    ];
    $request->validate($rules, $message);
    $data = $request->except(['_method', '_token', 'v_trans_code', 'narration', 'dr_amount', 'cr_amount', 'account_id', 'id']);
    //$data = $request->all();
    $id = $request->v_trans_code;


    $data['remarks'] = 'Journal Voucher';
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
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
      }
    }
    return $ret;
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
    ];
    $message = [
      'trans_date.required' => 'Transaction Date Required',
      /*             'ref_id.required' => 'Please Select Sim',
       */
      'dr_amount.required' => 'Amount should be greater than 0',
      /*             'CID.required'=>' Company required',
       */
    ];
    $request->validate($rules, $message);

    $data['trans_date'] = $request->trans_date;
    $data['voucher_type'] = $request->voucher_type;
    $data['payment_type'] = $request->payment_type ?? 0;
    $data['payment_from'] = $request->account_id[0];
    $data['billing_month'] = $request->billing_month;

    $data['remarks'] = General::VoucherType($request->voucher_type) . ' Month of ' . date('M-Y', strtotime($data['billing_month']));
    $data['amount'] = array_sum($request->dr_amount);
    $id = $request->v_trans_code;

    // ALLOW duplicate debit/credit for these voucher types:
    $skipDupAccountTypes = ['AL', 'COD', 'PN', 'PAY', 'VC'];
    if (!in_array($request->voucher_type, $skipDupAccountTypes)) {
      $head_account_id = $request->payment_from;
      $party_accounts = array_filter($request->account_id, fn($id) => !empty($id) && $id != 0);
      if (!empty($head_account_id) && $head_account_id != 0 && in_array($head_account_id, $party_accounts)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
          'account_id' => ['Credit/Head Account must not be the same as any Debit/Party Account.']
        ]);
      }
    }

    if ($id) {
      Transactions::where('trans_code', $id)->delete();
      $trans_code = $id;

      $data['Updated_By'] = \Auth::user()->id;

      $ret = Vouchers::where('trans_code', $id)->first();
      $ret->update($data);
    } else {
      $trans_code = Account::trans_code();
      $data['trans_code'] = $trans_code;
      $data['Created_By'] = \Auth::user()->id;

      $ret = Vouchers::create($data);
    }

    //dr/cr logic for each party/account: ALLOW both a debit and a credit per account in the same voucher
    foreach ($request->account_id as $key => $val) {
      if (!empty($val) && $val != 0) {
        if (!empty($request->dr_amount[$key]) && $request->dr_amount[$key] > 0) {
          $transactionData = [
            'account_id' => $val,
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $request->narration[$key],
            'debit' => $request->dr_amount[$key],
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
        if (!empty($request->cr_amount[$key]) && $request->cr_amount[$key] > 0) {
          $transactionData = [
            'account_id' => $val,
            'reference_id' => $ret->id,
            'reference_type' => 'Voucher',
            'trans_code' => $trans_code,
            'trans_date' => $data['trans_date'],
            'narration' => $request->narration[$key],
            'credit' => $request->cr_amount[$key],
            'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
          ];
          $this->TransactionService->recordTransaction($transactionData);
        }
      }
    }
  }
}
