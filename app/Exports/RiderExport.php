<?php

namespace App\Exports;

use App\Helpers\General;
use App\Models\RiderActivities;
use App\Models\Riders;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Carbon\Carbon;

class RiderExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
  protected $month;

  public function __construct()
  {
    /* $this->id = $id;
    $this->month = $month; */
  }

  public function collection()
  {
    return Riders::with(['vendor', 'customer', 'bikes', 'country', 'account'])->get();
  }
  public function map($rider): array
  {
    return [
      $rider->id,
      $rider->rider_id,
      $rider->courier_id ? ('CI-' . $rider->courier_id) : '',
      $rider->name ?? '',
      $rider->account_id,
      $rider->account?->name ?? '',
      General::RiderStatus($rider->status),
      $rider->personal_contact,
      $rider->company_contact,
      $rider->personal_email,
      $rider->email,
      $rider->country?->name ?? '',
      $rider->NFDID,
      $rider->cdm_deposit_id,
      $rider->doj,
      $rider->emirate_hub,
      $rider->emirate_id,
      $rider->emirate_exp,
      $rider->mashreq_id,
      $rider->passport,
      $rider->passport_expiry,
      $rider->PID,
      $rider->DEPT,
      $rider->ethnicity,
      $rider->dob,
      $rider->license_no,
      $rider->license_expiry,
      $rider->visa_status,
      $rider->branded_plate_no,
      $rider->vaccine_status ? 'Yes' : 'No',
      $rider->attach_documents,
      $rider->other_details,
      $rider->created_by,
      $rider->updated_by,
      $rider->VID,
      $rider->vendor?->name ?? '',
      $rider->visa_sponsor,
      $rider->visa_occupation,
      $rider->absconder ? 'Yes' : 'No',
      $rider->flowup ? 'Yes' : 'No',
      $rider->l_license ? 'Yes' : 'No',
      $rider->TAID,
      $rider->fleet_supervisor,
      $rider->passport_handover,
      $rider->noon_no,
      $rider->wps,
      $rider->c3_card,
      $rider->contract,
      $rider->designation,
      $rider->image_name,
      $rider->salary_model,
      $rider->rider_reference,
      $rider->job_status ? 'Active' : 'Inactive',
      $rider->person_code,
      $rider->labor_card_number,
      $rider->labor_card_expiry,
      $rider->insurance,
      $rider->insurance_expiry,
      $rider->policy_no,
      $rider->shift,
      $rider->vat ? 'Yes' : 'No',
      $rider->attendance,
      $rider->customer_id,
      $rider->customer?->name ?? '',
      $rider->attendance_date,
      $rider->recuriter,
      $rider->bikes?->plate ?? '',
      $rider->created_at,
      $rider->updated_at
    ];
  }

  public function headings(): array
  {
    return [
      'ID',
      'Rider ID',
      'Courier ID',
      'Name',
      'Account ID',
      'Account Name',
      'Status',
      'Personal Contact',
      'Company Contact',
      'Personal Email',
      'Email',
      'Nationality',
      'NFDID',
      'CDM Deposit ID',
      'Date of Joining',
      'Emirate Hub',
      'Emirate ID',
      'Emirate Expiry',
      'Mashreq ID',
      'Passport',
      'Passport Expiry',
      'PID',
      'DEPT',
      'Ethnicity',
      'Date of Birth',
      'License No',
      'License Expiry',
      'Visa Status',
      'Branded Plate No',
      'Vaccine Status',
      'Attach Documents',
      'Other Details',
      'Created By',
      'Updated By',
      'Vendor ID',
      'Vendor Name',
      'Visa Sponsor',
      'Visa Occupation',
      'Absconder',
      'Follow Up',
      'Learning License',
      'TAID',
      'Fleet Supervisor',
      'Passport Handover',
      'Noon No',
      'WPS',
      'C3 Card',
      'Contract',
      'Designation',
      'Image Name',
      'Salary Model',
      'Rider Reference',
      'Job Status',
      'Person Code',
      'Labor Card Number',
      'Labor Card Expiry',
      'Insurance',
      'Insurance Expiry',
      'Policy No',
      'Shift',
      'VAT',
      'Attendance',
      'Customer ID',
      'Customer Name',
      'Attendance Date',
      'Recruiter',
      'Bike Plate No',
      'Created At',
      'Updated At'
    ];
  }

  public function columnFormats(): array
  {
    return [
      'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
    ];
  }
}
