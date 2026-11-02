<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Models\FuelCards;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FuelCardExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use BindsTextColumns;

    public function collection()
    {
        return FuelCards::with(['rider', 'fuelCompany', 'branch'])->orderBy('id')->get();
    }

    public function map($card): array
    {
        return [
            $card->id,
            $card->card_number . ' ',
            $card->fuelCompany?->name ?? '',
            $card->branch?->name ?? '',
            $card->rider?->rider_id ?? '',
            $card->rider?->name ?? '',
            \App\Models\FuelCards::statusDisplay($card->status)['label'],
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Card Number',
            'Fuel Company',
            'Branch',
            'Rider ID',
            'Assigned To',
            'Status',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }

    protected function textColumns(): array
    {
        return ['E'];
    }
}
