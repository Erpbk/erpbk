<?php

namespace App\Helpers;

use App\Models\Accounts\ServiceProvidor;
use App\Models\Accounts\Transaction;
use App\Models\Item;
use App\Models\Items;
use App\Models\JobStatus;
use App\Models\RiderAttendance;
use App\Models\RiderEmails;
use App\Models\RiderItemPrice;
use App\Models\Riders;
use App\Models\RoomType;
use App\Models\Supervisors;
use App\Models\TransactionAccount;
use App\Models\Transactions;
use App\Models\VendorItemPrice;
use Carbon\Carbon;
use DB;
use Spatie\Permission\Models\Permission;
use Auth;

class General
{

  public static function gender()
  {
    $list = '';
    $array = [1 => 'Male', 2 => 'Female', 3 => 'Other'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@passenger type adult, child, infant
  public static function pax_type()
  {
    $list = '';
    $array = [1 => 'Adult', 2 => 'Child', 3 => 'Infant'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function martial_status()
  {
    $list = '';
    $array = [1 => 'Married', 2 => 'Single'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@emloyment status
  public static function emp_status()
  {
    $list = '';
    $array = [1 => 'Full Time', 2 => 'Part Time', 3 => 'Contrct'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@all service which uotrips offers
  public static function services()
  {
    $list = '';
    $array = [1 => 'Ticket', 2 => 'Hotel', 3 => 'Car', 'Bus', 'Umrah', 'Visa', 'Packages', 'Insurance', 'Hajh'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //get services
  public static function get_services($ser)
  {
    $list = '';
    $array = [
      1 => 'Ticket',
      2 => 'Hotel',
      3 => 'Car',
      4 => 'Bus',
      5 => 'Umrah',
      6 => 'Visa',
      7 => 'Packages',
      8 => 'Insurance',
      9 => 'Hajh'
    ];
    foreach ($array as $key => $val) {
      if (in_array($key, $ser)) {
        $list .= $val . ', ';
      }
    }
    return rtrim($list, ', ');
  }
  //@all sources of query
  public static function query_source()
  {
    $list = '';
    $array = [
      'Personal',
      'Referral',
      'Telephone',
      'Client Meeting',
      'Email Marketing',
      'Waht\'s App',
      'Olx',
      'Other',
      'Agent'
    ];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . ($key + 1) . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function get_query_source($source)
  {
    $array = [
      'Personal',
      'Referral',
      'Telephone',
      'Client Meeting',
      'Email Marketing',
      'Waht\'s App',
      'Olx',
      'Other',
      'Agent'
    ];
    foreach ($array as $key => $val) {
      if ($source == $key && $source != null) {
        return $val;
      } else {
        return 'online';
      }
    }
  }
  //@lead status
  public static function lead_status($status)
  {
    if ($status == 0) {
      return '<span class="badge bg-warning rounded-0">pending</span>';
    } elseif ($status == 1) {
      return '<span class="badge bg-info rounded-0">Taken Over</span>';
    } elseif ($status == 2) {
      return '<span class="badge bg-blue rounded-0">Process</span>';
    } elseif ($status == 3) {
      return '<span class="badge bg-success rounded-0">Successful</span>';
    } elseif ($status == 4) {
      return '<span class="badge bg-danger rounded-0">Unsuccessful</span>';
    }
  }
  //@serial number with 00 dsn=double serial numebr
  public static function dsn($no)
  {
    if ($no < 10) {
      return '00' . $no;
    } else {
      return $no;
    }
  }
  //@room type dropdown
  public static function room_type($id = 0)
  {
    return RoomType::dropdown($id);
  }
  //@visa types dropdown
  public static function visa_type()
  {
    $list = '';
    $array = [1 => 'visit', 2 => 'Hajh', 3 => 'Umrah', 4 => 'Ziyarat', 5 => 'Student', 6 => 'Employment'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function get_visa_type($type)
  {
    $list = '';
    $array = [1 => 'visit', 2 => 'Hajh', 3 => 'Umrah', 4 => 'Ziyarat', 5 => 'Student', 6 => 'Employment'];
    foreach ($array as $key => $val) {
      if ($type == $key) {
        return $val;
      }
    }
  }

  //@vehicle types
  public static function vehicle_types($id = 0)
  {
    $list = '';
    $array = [
      1 => 'Coaster',
      2 => 'Gmc',
      3 => 'H1',
      4 => 'Limousine',
      5 => 'Private Car',
      6 => 'Sedan Car',
      7 => 'Sharing Bus',
      8 => 'SUV Car',
      9 => 'Haramain Train'
    ];
    foreach ($array as $key => $val) {
      $list .= '<option ' . (($key == $id) ? 'selected' : '') . ' value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@sale types
  public static function sale_types()
  {
    $list = '';
    $array = [1 => 'Ticket', 2 => 'Hotel', 3 => 'Visa', 4 => 'Transport', 5 => 'Other', 6 => 'Tour'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //@get sale types form the current list
  public static function get_sale_type($type)
  {
    $list = '';
    $array = [1 => 'Ticket', 2 => 'Hotel', 3 => 'Visa', 4 => 'Transport', 5 => 'Other', 6 => 'Tour'];
    foreach ($array as $key => $val) {
      if ($type == $key) {
        return $val;
      }
    }
  }
  //@document types
  public static function doc_type()
  {
    $list = '';
    $array = [1 => 'passport', 2 => 'Id Card', 3 => 'Picture', 4 => 'Visa', 5 => 'Other'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }

  public static function get_warehouse($id = 0)
  {
    $array = [
      'Active' => 'Active',
      /*  'Impound' => 'Impound',
       'City Garage' => 'City Garage',
       'Clutch Garage' => 'Clutch Garage',
       'Express Garage' => 'Express Garage',
       'Al Sama Garage' => 'Al Sama Garage',
       'Easy Lease Garage' => 'Easy Lease Garage',
       'Theft' => 'Theft',
       'Total Loss' => 'Total Loss',*/
      'Return' => 'Return',
      'Absconded' => 'Absconded',
      'Vacation' => 'Vacation'
    ];
    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function get_sim_status($id = 0)
  {
    $array = ['Active' => 'Active', 'Office' => 'Office'];
    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function get_supervisor($id = 0)
  {
    /* $array = [
        'Kaleem' => 'Kaleem',
        'Waqas Mehmood' => 'Waqas Mehmood',
        'Zuhair Iftikhar' => 'Zuhair Iftikhar',
        'Ali Ahsan' => 'Ali Ahsan'
    ]; */
    $array = Supervisors::all()->pluck('name', 'name');
    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }


  public static function get_passport_handover($id = 0)
  {
    $array = [
      'NON' => 'NON',
      'EFDS' => 'EFDS'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  public static function get_Ethnicity($id = 0)
  {
    $array = [
      'Muslim' => 'Muslim',
      'non-Muslim' => 'non-Muslim'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function EmiratesHub($id = 0)
  {
    $array = [
      'DXB' => 'DXB',
      'AUH' => 'AUH',
      'UAQ' => 'UAQ',
      'RAK' => 'RAK',
      'SHJ' => 'SHJ',
      'FUJ' => 'FUJ',
      'AJM' => 'AJM'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function Designations($id = 0)
  {
    $array = [
      'Rider' => 'Rider',
      'Staff' => 'Staff',
      'Driver' => 'Driver',
      'Mechanic' => 'Mechanic',
      'Learning License' => 'Learning License',
      'Outside UAE' => 'Outside UAE'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  public static function SalaryModel($id = 0)
  {
    $array = [
      '3000 Fix Salary' => '3000 Fix Salary',
      '3200 Fix Salary' => '3200 Fix Salary',
      '3500 Fix Salary' => '3500 Fix Salary',
      'Commission' => 'Commission'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  public static function WPS($id = 0)
  {
    $array = [
      'NON/WPS' => 'NON/WPS',
      'WPS' => 'WPS'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  public static function C3Card($id = 0)
  {
    $array = [
      'Cash' => 'Cash',
      'Active' => 'Active',
      'Block' => 'Block',
      'Pending' => 'Pending'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  public static function VisaStatus($id = 0)
  {
    $array = [
      'Express Fast Visa' => 'Express Fast Visa',
      'Temp Labour Card' => 'Temp Labour Card',
      'Pending Labor Card' => 'Pending Labor Card'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function Insurance($id = 0)
  {
    $array = [
      'Workmen Compensation' => 'Workmen Compensation',
      'Health Insurance' => 'Health Insurance'
    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }

  public static function get_PaymentReason($id = 0)
  {
    $array = [
      'Monthly Salary' => 'Monthly Salary',
      'Advance Salary' => 'Advance Salary'


    ];

    $list = '';
    foreach ($array as $key => $value) {
      $list .= '<option ' . ($id == $key ? 'selected' : '') . ' value="' . $key . '">' . $value . '</option>';
    }
    return $list;
  }
  //@dropdown hotel start
  public static function hotel_star($id = 0)
  {
    $list = '';
    $array = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //title
  public static function pax_title()
  {
    $list = '';
    $array = [1 => 'Mr', 2 => 'Miss', 3 => 'Mrs', 4 => 'Dr', 5 => 'His Excellency', 6 => 'His Royal Highness'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function get_pax_title($title)
  {
    $array = [1 => 'Mr', 2 => 'Miss', 3 => 'Mrs', 4 => 'Dr', 5 => 'His Excellency', 6 => 'His Royal Highness'];
    foreach ($array as $key => $val) {
      if ($title == $key) {
        return $val;
      }
    }
  }
  //get vechile type
  public static function get_veh_types($id = 0)
  {
    $array = [
      1 => 'Coaster',
      2 => 'Gmc',
      3 => 'H1',
      4 => 'Limousine',
      5 => 'Private Car',
      6 => 'Sedan Car',
      7 => 'Sharing Bus',
      8 => 'SUV Car'
    ];
    foreach ($array as $key => $val) {
      if ($id == $key) {
        return $val;
      }
    }
  }
  //@room type dropdown
  public static function getroom_type($type)
  {
    $array = [
      1 => 'Single',
      2 => 'Double',
      3 => 'Triple',
      4 => 'Quad',
      5 => 'Quint',
      6 => '6 Bed',
      7 => '7 Bed',
      8 => '8 Bed',
      9 => '9 Bed',
      10 => '10 Bed',
      11 => 'Sharing',
      12 => 'Twin Bed',
      13 => 'Suit Room'
    ];
    foreach ($array as $key => $val) {
      if ($type == $key) {
        return $val;
      }
    }
  }
  //Mehram Relation
  public static function mehram_relation()
  {
    $list = '';
    $array = [
      1 => 'Son',
      2 => 'Father',
      3 => 'Brother',
      4 => 'Husband',
      5 => 'GrandFather',
      6 => 'Nephew',
      7 => 'Niece',
      8 => 'Women Group',
      9 => 'Grand Son',
      10 => 'Uncle (Mother side)',
      11 => 'Uncle (Father Side)',
      12 => 'Son In Law',
      13 => 'Step Father',
      14 => 'Father In Law',
      15 => 'Safe Companion'
    ];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  //passport type
  public static function passport_type()
  {
    $list = '';
    $array = [1 => 'Normal', 2 => 'Diplomatic', 3 => 'UN Passport', 4 => 'Other'];
    foreach ($array as $key => $val) {
      $list .= '<option value="' . $key . '">' . $val . '</option>';
    }
    return $list;
  }
  public static function get_passport_type($no)
  {
    $array = [1 => 'Normal', 2 => 'Diplomatic', 3 => 'UN Passport', 4 => 'Other'];
    foreach ($array as $key => $val) {
      if ($no == $key) {
        return $val;
      }
    }
  }
  //ground services additional informtion dropdown
  public static function additional_services()
  {
    $array = [
      'Baby Sitter',
      'Female Companion',
      'Male Companion',
      'Umrah Guidnace',
      'Wheel Chair',
      'Wheel Chair with Assistant',
      'Meet & Assist',
      'Tour Guide'
    ];
    $list = '';
    foreach ($array as $val) {
      $list .= '<option value="' . $val . '">' . $val . '</option>';
    }
    return $list;
  }
  //calculate age from date of birth
  public static function age($birth_date)
  {
    $birthDate = date('d-m-Y', strtotime($birth_date));
    $currentDate = date("d-m-Y");
    $diff = date_diff(date_create($birthDate), date_create($currentDate));
    return $diff->format('%y');
  }
  //count minutes from current time
  public static function count_minutes($time)
  {
    $startTime = Carbon::parse(date('Y-m-d h:i:s'));
    $endTime = Carbon::parse($time);

    $totalDuration = $endTime->diffForHumans($startTime);
    return $totalDuration;
  }
  /*
   * fetch visa detils agaist visa number
   */
  public static function get_visa_no($pn = '')
  {
    $visa_no = DB::table('agent_umrah_visitors')->where('passport', $pn)->value('visa');
    return $visa_no;
  }
  //print headers
  public static function print_header($title = '')
  {
    $html = '';
    $html .= '
            <div class="print-header" style="display: none">
                <h1 style="font-size: 16px;text-align: center">HUSSAIN INTERNATIONAL TRAVEL & TOURS</h1>
                <div style="text-align: center">
                    101, 1st Floor Trade Tower Abdullah Haroon Road, Saddar, Karachi<br>
                    Phone: 4298765432</br>
                    Email: sales@uotrips.co
                </div>
                <br>
                <table width="100%" style="font-family: sans-serif;line-height: 0.9">
                    <tr>
                        <td width="33.33%" style="text-align: left;"></td>
                        <td width="33.33%" style="text-align: center;">
                            <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">
                                <span style="border-bottom:double">' . $title . '</span>
                            </h4>
                        </td>
                        <td width="33.33%" style="text-align: right;">
                            <img src="' . url('public/dist/img/hussain-logo.jpeg') . '" width="100" />
                        </td>
                    </tr>
                </table><br>
            </div>
        ';
    return $html;
  }
  //access left sidebar on the base of service providor
  public static function sp_access()
  {
    $result = ServiceProvidor::where('UID', Auth::user()->id)->first();
    return compact('result');
  }
  /*
   * find vendor item price
   */
  public static function vendorItemPrice($VID, $itemID)
  {
    $res = VendorItemPrice::where('VID', $VID)->where('item_id', $itemID)->first();
    if ($res && $res->price > 0) {
      return $res->price;
    } else {
      $res = Item::where('id', $itemID)->first();
      return $res->pirce;
    }
  }
  /*
   * find rider item price
   */
  public static function riderItemPrice($RID, $itemID)
  {
    $res = RiderItemPrice::where('RID', $RID)->where('item_id', $itemID)->first();
    if ($res && $res->price > 0) {
      return $res->price;
    } else {
      return Items::where('id', $itemID)->value('price');
    }
  }
  public static function inv_sch($inv, $date)
  {
    $df = date('ym', strtotime($date));
    $inv = self::dsn($inv);
    $str = 'inv-' . $df . '-' . $inv;
    return $str;
  }

  public static function file_types($id = null)
  {

    $types = [

      1 => 'Emirates ID',
      2 => 'Passport',
      3 => 'Visa',
      4 => 'Rider Contract',
      5 => 'Health Insurance',
      7 => 'RTA Road Permit',
      8 => 'CNIC (Pakistani)',
      9 => 'Driving License',
      10 => 'Labor Card',
      11 => 'NOC (Employer)'
    ];
    if ($id) {
      return $types[$id];
    } else {
      return $types;
    }
  }

  public static function RiderStatus($status = null)
  {
    $result = [
      1 => 'Active',
      3 => 'Inactive',
      4 => 'Vacation',
      5 => 'Absconded'
    ];

    if ($status != null) {
      return $result[$status] ?? 'not-set';
    } else {
      return $result;
    }
  }
  public static function JobStatus($status = null)
  {
    $result = [

      1 => 'Active',
      2 => 'Vacation',
      3 => 'Terminate',
      4 => 'Theft',
      5 => 'Absconded',
      6 => 'Stop Salary',
      7 => 'Coming',
      8 => 'Absent',
      9 => 'Inactive'

    ];

    if ($status != null) {
      return $result[$status] ?? 'not-set';
    } else {
      return $result;
    }
  }


  public static function getBalance($account_id)
  {
    $balance = Transactions::where('account_id', $account_id)
      ->select(
        DB::raw('SUM(debit) as total_debit'),
        DB::raw('SUM(credit) as total_credit')
      )
      ->first();

    $finalBalance = ($balance->total_debit ?? 0) - ($balance->total_credit ?? 0);

    return number_format($finalBalance, 2);
  }

  public static function BillingMonth($month = null)
  {
    for ($i = 0; $i <= 11; $i++) {
      $value = date("M-Y", strtotime(date('Y-m-01') . " -$i months"));
      $key = date("Y-m-01", strtotime(date('Y-m-01') . " -$i months"));
      $months[$key] = $value;
    }
    if ($month) {
      return $months[$month];
    } else {
      return $months;
    }
  }

  public static function VoucherType($type = null)
  {
    $result = [
      'JV' => 'Journal',
      'LV' => 'Visa Expense',
      'RFV' => 'RTA Fine Voucher',
      'VL' => 'Loan Voucher',
      'AL' => 'Advance Loan Voucher',
      'SV' => 'Salik Voucher',
      'COD' => 'COD Voucher',
      'PN' => 'Penalty Voucher',
      'INC' => 'Incentive Voucher',
      'PAY' => 'Payment Voucher',
      'VC' => 'Vendor Charges Voucher',
      'RI' => 'Rider Invoice Voucher',
      'GV' => 'Garage Voucher',
      /* 9 => 'Vendor Voucher',
      10 => 'Bike Rent Voucher',
      11 => 'Fuel Voucher',
      8 => 'RTA Fine Voucher',
      12 => 'Advance/Loan Voucher',
      //13 => 'Advance Repay Voucher',*/
      //14 => 'Expense Voucher',
      /*15 => 'Maintenance Voucher',
      16 => 'COD Voucher', */
    ];

    if ($type) {
      return $result[$type];
    } else {
      return $result;
    }
  }
  public static function ImportVoucherType($type = null)
  {
    $result = [
      /* 3 => 'Journal Voucher',
      5 => 'Invoice Voucher', */
      9 => 'Vendor Voucher',
      /* 10 => 'Bike Rent Voucher', */
      11 => 'Fuel Voucher',
      8 => 'RTA Fine Voucher',
      /*  12 => 'Advance/Loan Voucher', */
      /*             13 => 'Advance Repay Voucher',
       */
      /*  14 => 'Expense Voucher', */
      15 => 'Maintenance Voucher',
    ];

    if ($type) {
      return $result[$type];
    } else {
      return $result;
    }
  }

  public static function LoanTerms($type = null)
  {
    $result = [
      'Monthly' => 'Monthly',
      'Weekly' => 'Weekly'
    ];

    if ($type) {
      return $result[$type];
    } else {
      return $result;
    }
  }

  public static function numToWordsRec($number)
  {
    $words = array(
      0 => 'zero',
      1 => 'one',
      2 => 'two',
      3 => 'three',
      4 => 'four',
      5 => 'five',
      6 => 'six',
      7 => 'seven',
      8 => 'eight',
      9 => 'nine',
      10 => 'ten',
      11 => 'eleven',
      12 => 'twelve',
      13 => 'thirteen',
      14 => 'fourteen',
      15 => 'fifteen',
      16 => 'sixteen',
      17 => 'seventeen',
      18 => 'eighteen',
      19 => 'nineteen',
      20 => 'twenty',
      30 => 'thirty',
      40 => 'forty',
      50 => 'fifty',
      60 => 'sixty',
      70 => 'seventy',
      80 => 'eighty',
      90 => 'ninety'
    );

    if ($number < 20) {
      return $words[$number];
    }

    if ($number < 100) {
      return $words[10 * floor($number / 10)] .
        ' ' . $words[$number % 10];
    }

    if ($number < 1000) {
      return $words[floor($number / 100)] . ' hundred '
        . self::numToWordsRec($number % 100);
    }

    if ($number < 1000000) {
      return self::numToWordsRec(floor($number / 1000)) .
        ' thousand ' . self::numToWordsRec($number % 1000);
    }

    return self::numToWordsRec(floor($number / 1000000)) .
      ' million ' . self::numToWordsRec($number % 1000000);
  }

  public static function DateFormat($date)
  {
    return date('d M Y', strtotime($date));
  }

  public static function DateTimeFormat($date)
  {
    return date('d M Y h:i A', strtotime($date));
  }

  public static function dropdownitems($itemsId = null, $selected = null, $parentId = null, $prefix = '')
  {
    $html = '';
    $select = '';
    $items = \App\Models\Items::all();
    // Get categories grouped by parent_id
    if (isset($items)) {
      foreach ($items as $item) {
        if ($selected) {
          if ($item->id == $selected) {
            $select = 'selected';
          }
        }
        $html .= '<option value="' . $item->id . '" ' . $select . '>' . $item->name . ' - ' . $item->price . '</option>';
      }
    }

    return $html;
  }

  public static function getAttnActivity($rider_id)
  {
    $rider = Riders::find($rider_id);
    $timeline = JobStatus::select('id')->where('RID', $rider_id)->whereDate('created_at', '=', $rider->attendance_date)->first();
    $emails = RiderEmails::select('id')->where('rider_id', $rider_id)->whereDate('created_at', '=', $rider->attendance_date)->first();
    return [
      'timeline' => $timeline,
      'emails' => $emails
    ];
  }
}
