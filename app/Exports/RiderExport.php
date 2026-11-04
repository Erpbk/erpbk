<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Models\Riders;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RiderExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder, WithEvents
{
  use BindsTextColumns;

  /** @var array<int, string> excel row => absolute show URL */
  protected array $nameLinkByExportRow = [];

  protected int $exportRowCounter = 0;

  /** Name is column D (1-based index 4) */
  protected int $nameColumnIndex = 4;

  /** Pre-filtered riders from the current list query when provided. */
  protected ?Collection $riders = null;

  /**
   * @param  Collection<int, Riders>|iterable<int, Riders>|null  $riders
   */
  public function __construct($riders = null)
  {
    if ($riders !== null) {
      $this->riders = $riders instanceof Collection ? $riders : collect($riders);
    }
  }

  public function collection()
  {
    if ($this->riders !== null) {
      return $this->riders;
    }

    $query = Riders::with(['vendor', 'customer', 'bikes', 'country', 'account', 'branch']);

    if (Schema::hasTable('rider_histories')) {
      $query->select('riders.*')->addSelect(Riders::employmentStatusDaysSelectColumns());
    }

    return $query->get();
  }

  public function map($rider): array
  {
    $this->exportRowCounter++;
    $excelRow = $this->exportRowCounter + 1;

    try {
      $params = ['rider' => $rider->id];
      $companySlug = request()->route('company_slug');
      if ($companySlug) {
        $params['company_slug'] = $companySlug;
      }
      $this->nameLinkByExportRow[$excelRow] = route('riders.show', $params, true);
    } catch (\Throwable $e) {
      // leave without link
    }

    $statusLabel = Riders::currentStatusDisplay($rider)['label'] ?? '-';
    $daysInfo = Riders::resolveEmploymentStatusDays($rider);
    $statusDays = $daysInfo['days'] === null
      ? '-'
      : ((int) $daysInfo['days'] . ' ' . ((int) $daysInfo['days'] === 1 ? 'day' : 'days'));

    return [
      $rider->id,
      $rider->rider_id,
      $rider->courier_id ? ('CI-' . $rider->courier_id) : '',
      $rider->name ?? '',
      $rider->branch?->name ?? '',
      $rider->account_id,
      $rider->account?->name ?? '',
      $statusLabel,
      $statusDays,
      $rider->personal_contact,
      $rider->company_contact,
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
      'Branch',
      'Account ID',
      'Account Name',
      'Status',
      'Status Days',
      'Personal Contact',
      'Company Contact',
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

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        if ($this->nameLinkByExportRow === []) {
          return;
        }

        $sheet = $event->sheet->getDelegate();
        $colLetter = Coordinate::stringFromColumnIndex($this->nameColumnIndex);

        foreach ($this->nameLinkByExportRow as $row => $url) {
          if ($url === '' || $url === null) {
            continue;
          }
          $cell = $sheet->getCell($colLetter . $row);
          $cell->getHyperlink()->setUrl($url);
          $cell->getStyle()->getFont()
            ->setColor(new Color(Color::COLOR_BLUE))
            ->setUnderline(Font::UNDERLINE_SINGLE);
        }
      },
    ];
  }

  public function columnFormats(): array
  {
    return [
      'B' => NumberFormat::FORMAT_TEXT,
      'C' => NumberFormat::FORMAT_TEXT,
    ];
  }

  protected function textColumns(): array
  {
    return ['B'];
  }
}
