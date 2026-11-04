<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Models\EmployeeInvoices;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeInvoiceExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder, WithEvents
{
    use BindsTextColumns;

    public function __construct(
        protected Collection $invoices
    ) {
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function map($invoice): array
    {
        /** @var EmployeeInvoices $invoice */
        $employee = $invoice->employee;
        $employeeLabel = $employee
            ? trim(($employee->employee_id ?? '').'-'.($employee->name ?? ''), '-')
            : '';

        $billingMonth = $invoice->billing_month
            ? Carbon::parse($invoice->billing_month)->format('M Y')
            : '';

        $invDate = $invoice->inv_date
            ? Carbon::parse($invoice->inv_date)->format('d M Y')
            : '';

        return [
            $invoice->invoice_number,
            $invDate,
            $billingMonth,
            $employeeLabel,
            (string) ($invoice->zone ?? ''),
            (string) ($invoice->descriptions ?? ''),
            number_format((float) $invoice->total_amount, 2, '.', ''),
            number_format((float) $invoice->final_amount, 2, '.', ''),
            number_format((float) $invoice->paid_amount, 2, '.', ''),
            number_format((float) $invoice->balance, 2, '.', ''),
            match ($invoice->paymentStatusKey()) {
                'paid' => 'Paid',
                'partial' => 'Partially Paid',
                default => 'Unpaid',
            },
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Inv Date',
            'Billing Month',
            'Employee',
            'Zone',
            'Descriptions',
            'Invoice Amount',
            'Payable Salary',
            'Paid Amount',
            'Balance',
            'Status',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }

    protected function textColumns(): array
    {
        return ['A', 'D'];
    }
}
