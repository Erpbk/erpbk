<?php

namespace App\Imports;

use App\Models\FuelCards;
use App\Models\FuelCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class FuelCardImport implements ToCollection
{
    protected array $results = [
        'stats' => [
            'total' => 0,
            'imported' => 0,
            'failed' => 0,
        ],
        'failed' => [],
    ];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $seen = [];

            foreach ($rows->skip(1) as $index => $row) {
                if ($row->every(function ($value) {
                    return $value === null || $value === '';
                })) {
                    break;
                }

                $this->results['stats']['total']++;
                $rowNumber = $index + 2;

                $cardNumber = trim((string) ($row[0] ?? ''));
                $companyName = trim((string) ($row[1] ?? ''));
                $status = trim((string) ($row[2] ?? 'Inactive'));

                if ($cardNumber === '' || $companyName === '') {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Card Number and Fuel Company are required');
                    continue;
                }

                if (isset($seen[$cardNumber])) {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Duplicate card number in file');
                    continue;
                }
                $seen[$cardNumber] = true;

                if (FuelCards::where('card_number', $cardNumber)->exists()) {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Card number already exists');
                    continue;
                }

                $company = FuelCompany::whereRaw('LOWER(name) = ?', [strtolower($companyName)])->first();
                if (!$company) {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Fuel company not found');
                    continue;
                }

                if ($status === '' || !in_array($status, ['Active', 'Inactive'], true)) {
                    $status = 'Inactive';
                }

                FuelCards::create([
                    'card_number' => $cardNumber,
                    'fuel_company_id' => $company->id,
                    'status' => $status,
                    'created_by' => Auth::id(),
                ]);

                $this->results['stats']['imported']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function fail(int $rowNumber, string $cardNumber, string $company, string $reason): void
    {
        $this->results['stats']['failed']++;
        $this->results['failed'][] = [
            'row_number' => $rowNumber,
            'card_number' => $cardNumber,
            'company' => $company,
            'reason' => $reason,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
