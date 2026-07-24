<?php

namespace App\Exports;

use App\Models\FuelCards;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FuelCardExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
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
            $card->status ?? 'Inactive',
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
}
