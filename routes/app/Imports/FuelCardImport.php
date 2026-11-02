<?php

namespace App\Imports;

use App\Models\FuelCards;
use App\Models\FuelCompany;
use App\Support\ExcelDate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

                $serviceCharges = $this->parseAmount($row[2] ?? null);
                if ($serviceCharges === false) {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Service Charges must be a number of 0 or more');
                    continue;
                }

                $issueDate = $this->parseDate($row[3] ?? null);
                if (!$issueDate) {
                    $this->fail($rowNumber, $cardNumber, $companyName, 'Card Issue Date is required and must be a valid date');
                    continue;
                }

                $remarks = trim((string) ($row[4] ?? ''));

                // Imported cards arrive unassigned, so they land in the office
                // until someone assigns them to a rider.
                FuelCards::create([
                    'card_number' => $cardNumber,
                    'fuel_company_id' => $company->id,
                    'service_charges' => $serviceCharges,
                    'card_issue_date' => $issueDate,
                    'remarks' => $remarks !== '' ? Str::limit($remarks, 1000, '') : null,
                    'status' => FuelCards::STATUS_IN_OFFICE,
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

    /**
     * Optional money column. Returns null when blank, or false when unusable so
     * the caller can fail the row rather than silently importing a wrong figure.
     *
     * @return float|null|false
     */
    protected function parseAmount($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $raw = str_replace(',', '', trim((string) $value));
        if (!is_numeric($raw) || (float) $raw < 0) {
            return false;
        }

        return (float) $raw;
    }

    /**
     * Date cells arrive as Excel serial numbers from xlsx and as plain strings
     * from csv, so both are handled.
     */
    protected function parseDate($value): ?string
    {
        return ExcelDate::format($value);
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
