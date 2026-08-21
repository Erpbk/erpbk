<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Models\Sims;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SimExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use BindsTextColumns;

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Sims::with(['assignee', 'vendor','branch', 'telecomCompany'])->get();
    }

    public function map($sim): array
    {
        return [
            $sim->id,
            $sim->number,
            $sim->branch?->name ?? 'All',
            $sim->telecomCompany?->name ?? 'All',
            $sim->emi . " ",
            $sim->vendors?->name??'',
            $sim->assignee?->employee_id ?? $sim->assignee?->rider_id ?? '',
            $sim->assignee?->name ?? '',
            $sim->status?'Active':'inactive',
            
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Number',
            'Branch',
            'Company',
            'EMI',
            'Vendor',
            'Assigned To',
            'Name',
            'Status'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
        ];
    }

    protected function textColumns(): array
    {
        return ['G'];
    }
}
