<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Helpers\General;
use App\Helpers\HeadAccount;
use App\Models\Items;
use App\Models\RiderInvoiceItem;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ImportRiderInvoice implements ToCollection
{
  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function collection(Collection $rows)
  {
    //dd($rows);
    $items = [
      $rows[0][3],
      $rows[0][4],
      $rows[0][5],
      $rows[0][6],
      $rows[0][7],
      $rows[0][8],
      $rows[0][9],
      $rows[0][10],
      $rows[0][11],
      $rows[0][12],
      $rows[0][13],
      $rows[0][14],
      $rows[0][15],
      $rows[0][16],
      $rows[0][17],
      $rows[0][18],
      $rows[0][19],
      $rows[0][20]
    ];
    $i = 1;
    $importedInvoiceIds = [];
    foreach ($rows as $row) {
      $i++;
      try {
        DB::beginTransaction();
        if ($row[1] != 'ID') {
          if ($row[1] != '') {
            $dateTimeObject = Date::excelToDateTimeObject($row[0]);
            $invoice_date = Carbon::instance($dateTimeObject)->format('Y-m-d');
            /*  if (!$invoice_date) {
                 $invoice_date = date('Y-m-01', strtotime($row[0]));
             } */
            /* $Billingdate = Date::excelToDateTimeObject($row[28]);
            $billing_month = Carbon::instance($Billingdate)->format('Y-m-01'); */
            $billing_month = date('Y-m-01', strtotime($row[28]));
            if ($billing_month == '1970-01-01') {
              $Billingdate = Date::excelToDateTimeObject($row[28]);
              $billing_month = Carbon::instance($Billingdate)->format('Y-m-01');
            }
            $rider = Riders::where('rider_id', $row[1])->first();
            if (!$rider) {
              throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - Rider ID ' . $row[1] . ' do not match.']);
            }
            $RID = $rider->id;
            $VID = $rider->VID;
            //$VID = AssignVendorRider::where('RID', $RID)->value('VID');
            if (isset($row[21])) {
              // Check for duplicate invoice for same rider and billing month
              $existingInvoice = RiderInvoices::where('rider_id', $RID)
                ->where('billing_month', $billing_month)
                ->first();

              if ($existingInvoice) {
                throw ValidationException::withMessages(['file' => 'Row(' . $i . ') - An invoice for rider ' . $row[1] . ' has already been generated for the selected billing month.']);
              }

              // Map status from Excel: allow 'unpaid'/'paid' or 0/1
              $excelStatus = strtolower(trim($row[30] ?? ''));
              if ($excelStatus === 'paid' || $excelStatus === '1') {
                $status = 1; // Paid
              } else {
                $status = 0; // Unpaid (default)
              }
              $ret = RiderInvoices::create([
                'inv_date' => $invoice_date,
                'rider_id' => $RID,
                'vendor_id' => $VID,
                'zone' => $row[23],
                'login_hours' => $row[22],
                'working_days' => $row[24],
                'perfect_attendance' => $row[25],
                'rejection' => $row[21],
                'performance' => $row[27],
                'billing_month' => $billing_month,
                'off' => $row[26],
                'descriptions' => $row[29],
                /*  'gaurantee' => $row[30], */
                'notes' => $row[31],
                'status' => $status,
              ]);
              $j = 3;
              foreach ($items as $item) {
                $itemId = Items::where('name', $item)->value('id');
                if ($itemId) {
                  $riderPrice = General::riderItemPrice($RID, $itemId);
                  $qtyRaw = $row[$j] ?? 0;
                  $qty = is_numeric($qtyRaw) ? (float)$qtyRaw : 0.0;
                  $rate = is_numeric($riderPrice) ? (float)$riderPrice : 0.0;
                  $dta['item_id'] = $itemId;
                  $dta['qty'] = $qty;
                  $dta['rate'] = $rate;
                  $dta['amount'] = $rate * $qty;
                  $dta['inv_id'] = $ret->id;
                  RiderInvoiceItem::create($dta);
                }
                $j++;
              }
              /* $k = 2;
              foreach ($items as $itemm) {
                  $itemIdd = Item::where('item_name', $itemm)->value('id');
                  if($itemIdd) {
                      $vendorPrice=CommonHelper::vendorItemPrice($VID, $itemIdd);
                      $dtaa['item_id'] = $itemIdd;
                      $dtaa['qty'] = $row[$k]??0;
                      $dtaa['rate'] = $vendorPrice;
                      $dtaa['amount'] = ($vendorPrice) * ($row[$k]);
                      $dtaa['inv_id'] = $ret->id;
                      VendorInvoiceItem::create($dtaa);
                  }
                  $k++;
              } */
              //$vendor_amount=VendorInvoiceItem::where('inv_id',$ret->id)->sum('amount');
              //$profit=$vendor_amount-$rider_amount;
              $total = RiderInvoiceItem::where('inv_id', $ret->id)->sum('amount');
              $vat = 0;
              if ($rider->vat == 1) {
                $vat = $total * (Common::getSetting('vat_percentage') / 100);
                $total = $total + $vat;
              }
              RiderInvoices::where('id', $ret->id)->update(['total_amount' => $total, 'vat' => $vat]);
              if ($status === 0) {
                $rider_amount = RiderInvoiceItem::where('inv_id', $ret->id)->sum('amount');
                $trans_code = Account::trans_code();
                $transactionService = new TransactionService();
                if ($rider->vat == 1) {
                  $transactionData = [
                    'account_id' => HeadAccount::TAX_ACCOUNT,
                    'reference_id' => $ret->id,
                    'reference_type' => 'Invoice',
                    'trans_code' => $trans_code,
                    'trans_date' => $invoice_date,
                    'narration' => "Rider Invoice #" . $ret->id . ' - ' . $row[29],
                    'debit' => $vat ?? 0,
                    'billing_month' => date("Y-m-01", strtotime($row[28])),
                  ];
                  $transactionService->recordTransaction($transactionData);
                }
                $transactionData = [
                  'account_id' => $rider->account_id,
                  'reference_id' => $ret->id,
                  'reference_type' => 'Invoice',
                  'trans_code' => $trans_code,
                  'trans_date' => $invoice_date,
                  'narration' => "Rider Invoice #" . $ret->id . ' - ' . $row[29],
                  'debit' => 0,
                  'credit' => $total ?? 0,
                  'billing_month' => date("Y-m-01", strtotime($row[28])),
                ];
                $transactionService->recordTransaction($transactionData);
                $ret->total_amount = $total;
                $ret->subtotal = $rider_amount;
                $ret->vat = $vat;
                $ret->save();
                $importedInvoiceIds[] = $ret->id;
              }
              // creating Vendor Voucher for Bike rent and Sim charges
              /* if ($row[31]) {
                $Vdata['billing_month'] = $data['billing_month'];
                $Vdata['trans_acc_id'] = $data['trans_acc_id'];
                $Vdata['trans_date'] = $data['trans_date'];
                $Vdata['amount'] = $row[31];
                $Vdata['narration'] = "Bike & Sim Charges";
                $Vdata['voucher_type'] = 9;
                $Vdata['payment_from'] = 811; //Account ID
                $Vdata['payment_type'] = 1; //dr/cr
                Account::CreatVoucher($Vdata);
              }
              //creating Fuel Voucher
              if ($row[32]) {
                $Vdata['billing_month'] = $data['billing_month'];
                $Vdata['trans_acc_id'] = $data['trans_acc_id'];
                $Vdata['trans_date'] = $data['trans_date'];
                $Vdata['amount'] = $row[32];
                $Vdata['narration'] = $row[33];
                $Vdata['voucher_type'] = 11;
                $Vdata['payment_from'] = 617; //Account ID
                $Vdata['payment_type'] = 1; //dr/cr
                Account::CreatVoucher($Vdata);
              }
              // creating RTA Voucher
              if ($row[34]) {
                $Vdata['billing_month'] = $data['billing_month'];
                $Vdata['trans_acc_id'] = $data['trans_acc_id'];
                $Vdata['trans_date'] = $data['trans_date'];
                $Vdata['amount'] = $row[34];
                $Vdata['narration'] = $row[35];
                $Vdata['voucher_type'] = 8;
                $Vdata['payment_from'] = 425; //Account ID
                $Vdata['payment_type'] = 1; //dr/cr
                Account::CreatVoucher($Vdata);

              } */
            }
          }
        }
        DB::commit();
      } catch (QueryException $e) {
        DB::rollBack();
        throw $e;
      }
    }
    if (!empty($importedInvoiceIds)) {
      $transactionService = new TransactionService();
      $salary_account = DB::table('accounts')->where('id', 1103)->first();
      foreach ($importedInvoiceIds as $invoiceId) {
        $invoice = RiderInvoices::find($invoiceId);
        if (!$invoice || !$salary_account) {
          continue;
        }
        // Skip if a salary debit already exists for this invoice to avoid duplicates
        $alreadyExists = DB::table('transactions')
          ->where('reference_type', 'Invoice')
          ->where('reference_id', $invoice->id)
          ->where('account_id', $salary_account->id)
          ->exists();
        if ($alreadyExists) {
          continue;
        }
        $transactionDataDebit = [
          'account_id' => $salary_account->id,
          'reference_id' => $invoice->id,
          'reference_type' => 'Invoice',
          'trans_code' => Account::trans_code(),
          'trans_date' => $invoice->inv_date,
          'narration' => 'Rider Invoice #' . $invoice->id . ' Salary Debit',
          'debit' => $invoice->total_amount,
          'credit' => 0,
          'billing_month' => $invoice->billing_month,
        ];
        $transactionService->recordTransaction($transactionDataDebit);
      }
    }
  }
}
