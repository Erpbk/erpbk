<?php
namespace App\Helpers;

use App\Models\Accounts;
use App\Models\Accounts\SubHeadAccount;
use App\Models\Accounts\Transaction;
use App\Models\Accounts\TransactionAccount;
use App\Models\Rider;
use App\Models\RiderInvoice;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\Vouchers;
use DB;
use Session;

class Account
{
  //payment types
  public static function payment_type_list()
  {
    $list = [0 => 'Select', 1 => 'Cash', 2 => 'Cheque', 3 => 'Online', 4 => 'Credit'];

    return $list;
  }
  public static function payment_type()
  {
    $list = '';
    $array = [1 => 'Cash', 2 => 'Cheque', 3 => 'Online', 4 => 'Credit'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function trans_code()
  {
    $tc = Transactions::max('trans_code');
    if ($tc > 0) {
      return $tc + 1;
    } else {
      return 1;
    }
  }
  public static function code()
  {
    $account_code = (int) Accounts::max('id') + 1;
    return str_pad($account_code, 4, "0", STR_PAD_LEFT);
    /* $ac = Accounts::max('account_code');
    if ($ac > 0) {
      return $ac + 1;
    } else {
      return 1;
    } */
  }
  //@dr or cr
  public static function dc()
  {
    $list = '';
    $array = [1 => 'Dr', 2 => 'Cr'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@trnas code format tcf=trans_code_format
  public static function tcf($number)
  {
    if ($number < 10) {
      return '0' . $number;
    } else {
      return $number;
    }
  }
  //@voucher type rv=receipt voucehr pv=payment voucher jv=journal voucher
  public static function vt($type)
  {
    if ($type == 1) {
      return 'RV';
    } elseif ($type == 2) {
      return 'PV';
    } elseif ($type == 3) {
      return 'JV';
    } elseif ($type == 4) {
      return 'Rider Invoice';
    } elseif ($type == 5) {
      return 'Rider PV';
    } elseif ($type == 6) {
      return 'Vendor Invoice';
    } elseif ($type == 7) {
      return 'Vendor PV';
    } elseif ($type == 8) {
      return 'RTA Fine';
    } elseif ($type == 9) {
      return 'Bike Rent & Vendor & Sim Charges';
    } elseif ($type == 10) {
      return 'Bike Rent';
    } elseif ($type == 11) {
      return 'Fuel Charges';
    } elseif ($type == 12) {
      return 'Advance Issue';
    } elseif ($type == 13) {
      return 'Advance Repay';
    } elseif ($type == 14) {
      return 'Expense';
    } elseif ($type == 15) {
      return 'Maintenance';
    } elseif ($type == 16) {
      return 'COD';
    } elseif ($type == 17) {
      return 'Customer Invoice';
    }

  }
  //@opening balance as on date as well while createing account
  public static function ob($date, $tid)
  {
    $df = date('Y-m-d', strtotime('0 day', strtotime('2015-08-10')));
    $dt = date('Y-m-d', strtotime('-1 day', strtotime($date)));
    $ob_res = Accounts::find($tid);
    if ($ob_res->OB_Type == '1') {
      $opening_balance = $ob_res->OB;
    } else {
      $opening_balance = -$ob_res->OB;
    }
    $dr = Transactions::where(['trans_acc_id' => $tid, 'dr_cr' => 1])
      ->where(function ($query) use ($df, $dt) {
        $query->WhereBetween('trans_date', [$df, $dt]);
      })->sum('amount');
    $cr = Transactions::where(['trans_acc_id' => $tid, 'dr_cr' => 2])
      ->where(function ($query) use ($df, $dt) {
        $query->WhereBetween('trans_date', [$df, $dt]);
      })->sum('amount');

    $ob = $opening_balance + ($dr - $cr);
    if ($ob > 0) {
      return $ob;
    } else {
      return $ob;
    }
  }
  public static function Monthly_ob($date, $tid)
  {

    $dr = Transactions::where('account_id', $tid)
      ->whereDate('billing_month', '<', $date)->sum('debit');

    $cr = Transactions::where('account_id', $tid)
      ->whereDate('billing_month', '<', $date)
      ->sum('credit');

    $ob = $dr - $cr;
    if ($ob > 0) {
      return $ob;
    } else {
      return $ob;
    }
  }
  public static function BillingMonth_Balance($date, $tid)
  {

    $dr = Transactions::where(['account_id' => $tid])
      ->whereDate('billing_month', $date)->sum('debit');

    $cr = Transactions::where(['account_id' => $tid])
      ->whereDate('billing_month', $date)->sum('credit');


    $ob = $dr - $cr;
    if ($ob > 0) {
      return $ob;
    } else {
      return $ob;
    }
  }
  //@dr or cr
  public static function show_bal($bal)
  {
    if ($bal > 0) {
      return number_format(abs($bal), 2);
    } elseif ($bal < 0) {
      return '-' . number_format(abs($bal), 2);
    } elseif ($bal == 0) {
      return "0.00";
    }
  }
  public static function show_bal_format($bal)
  {
    if ($bal > 0) {
      return number_format(abs($bal), 2);
    } elseif ($bal < 0) {
      return '-' . number_format(abs($bal), 2);
    } elseif ($bal == 0) {
      return "0.00";
    }
  }
  //show currency in words
  public static function convertNumberToWord($num = false)
  {
    $num = str_replace(array(',', ' '), '', trim($num));
    if (!$num) {
      return false;
    }
    $num = (int) $num;
    $words = array();
    $list1 = array(
      '',
      'one',
      'two',
      'three',
      'four',
      'five',
      'six',
      'seven',
      'eight',
      'nine',
      'ten',
      'eleven',
      'twelve',
      'thirteen',
      'fourteen',
      'fifteen',
      'sixteen',
      'seventeen',
      'eighteen',
      'nineteen'
    );
    $list1 = array_map('strtoupper', $list1);
    $list2 = array('', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred');
    $list2 = array_map('strtoupper', $list2);
    $list3 = array(
      '',
      'thousand',
      'million',
      'billion',
      'trillion',
      'quadrillion',
      'quintillion',
      'sextillion',
      'septillion',
      'octillion',
      'nonillion',
      'decillion',
      'undecillion',
      'duodecillion',
      'tredecillion',
      'quattuordecillion',
      'quindecillion',
      'sexdecillion',
      'septendecillion',
      'octodecillion',
      'novemdecillion',
      'vigintillion'
    );
    $list3 = array_map('strtoupper', $list3);
    $num_length = strlen($num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    for ($i = 0; $i < count($num_levels); $i++) {
      $levels--;
      $hundreds = (int) ($num_levels[$i] / 100);
      $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' HUNDRED' . ' ' : '');
      $tens = (int) ($num_levels[$i] % 100);
      $singles = '';
      if ($tens < 20) {
        $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
      } else {
        $tens = (int) ($tens / 10);
        $tens = ' ' . $list2[$tens] . ' ';
        $singles = (int) ($num_levels[$i] % 10);
        $singles = ' ' . $list1[$singles] . ' ';
      }
      $words[] = $hundreds . $tens . $singles . (($levels && (int) ($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
    } //end for loop
    $commas = count($words);
    if ($commas > 1) {
      $commas = $commas - 1;
    }
    return implode(' ', $words);
  }
  //financial years
  public static function financial_year()
  {
    $fy = Session::get('financial_year');
    $fy = explode('/', $fy);
    return $fy;
  }
  /*
   * code base on current year month etc.
   */
  public static function current_code($str, $id)
  {
    $code = date('my');
    return $str . '-' . $code . $id;
  }

  public static function getRider($id)
  {
    return Riders::find($id);
  }

  public static function getVouchers($rider_id, $billing_month, $vt)
  {
    $RTAID = Accounts::where(['PID' => 21, 'Parent_Type' => $rider_id])->value('id');

    $result = Transactions::select(\DB::raw('SUM(amount) as charges'))
      ->where('trans_acc_id', $RTAID)->where('vt', $vt);
    if ($billing_month) {
      $result = $result->where('billing_month', $billing_month);
    }
    $result = $result->first();
    return $result->charges ?? 0;

  }
  public static function getVouchersTillMonth($rider_id, $billing_month, $vt)
  {
    $RTAID = Accounts::where(['PID' => 21, 'Parent_Type' => $rider_id])->value('id');

    $result = Transactions::select(\DB::raw('SUM(amount) as charges'))
      ->where('trans_acc_id', $RTAID)->where('vt', $vt);
    if ($billing_month) {
      $result = $result->where('billing_month', '<', $billing_month);
    }
    $result = $result->first();
    return $result->charges ?? 0;

  }



  public static function CreatVoucher($data)
  {
    /* SAMPLE DATA */
    /* $data = [
        'billing_month' => $value,
        'trans_date' => $value,
        'voucher_type' => $value,
        'trans_acc_id' => $value,
        'amount' => $value,
        'narration' => $value,
        'payment_from' => $value,
        'payment_type' => $value
    ]; */
    $trans_code = self::trans_code();

    $tData['billing_month'] = $data['billing_month'];
    $tData['trans_date'] = $data['trans_date'];
    $tData['posting_date'] = $data['trans_date'];
    $tData['trans_code'] = $trans_code;
    $tData['status'] = 1;
    $tData['vt'] = $data['voucher_type'];
    $tData['Created_By'] = \Auth::user()->id;

    //dr to rider
    $tData['trans_acc_id'] = $data['trans_acc_id'];
    $tData['dr_cr'] = 1;
    $tData['amount'] = $data['amount'];
    $tData['narration'] = $data['narration'];
    Transactions::create($tData);


    //cr to compnay
    //$tData['narration'] = $request->sim_narration;
    $tData['trans_acc_id'] = $data['payment_from'];
    $tData['dr_cr'] = 2;
    $tData['amount'] = $data['amount'];
    Transactions::create($tData);

    //creating/updating voucher
    $vdata['trans_date'] = $data['trans_date'];
    $vdata['voucher_type'] = $data['voucher_type'];
    $vdata['payment_type'] = $data['payment_type'];
    $vdata['payment_from'] = $data['payment_from'];
    $vdata['billing_month'] = $data['billing_month'];
    $vdata['amount'] = $data['amount'];
    $vdata['trans_code'] = $trans_code;
    $vdata['Created_By'] = \Auth::user()->id;

    Vouchers::create($vdata);



  }
  public static function getSubHead()
  {
    return SubHeadAccount::all();

  }
  public static function getTAbySubHead($id)
  {
    return TransactionAccount::where('PID', $id)->get();

  }

  public static function InvoiceBalance($invoice_id)
  {
    $invoice = RiderInvoice::find($invoice_id);
    $balance = 0;
    if ($invoice) {
      $simandvendor = self::getVouchers($invoice->RID, $invoice->billing_month, 9);
      $fuel = self::getVouchers($invoice->RID, $invoice->billing_month, 11);
      $bikerent = self::getVouchers($invoice->RID, $invoice->billing_month, 10);
      $bikemaintenance = self::getVouchers($invoice->RID, $invoice->billing_month, 15);
      $rta = self::getVouchers($invoice->RID, $invoice->billing_month, 8);
      $payment = self::getVouchers($invoice->RID, $invoice->billing_month, 3);
      $invoice_payment = self::getVouchers($invoice->RID, $invoice->billing_month, 5);
      $loan_advance = self::getVouchers($invoice->RID, $invoice->billing_month, 12);
      $cod = self::getVouchers($invoice->RID, $invoice->billing_month, 16);
      $balance = $invoice->total_amount - ($simandvendor + $fuel + $bikerent + $rta + $payment + $invoice_payment + $loan_advance + $bikemaintenance + $cod);

    }
    return $balance;

  }
}
