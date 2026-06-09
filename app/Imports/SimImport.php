<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Sims;
use App\Models\SimCompany;
use App\Models\Customers;

class SimImport implements ToCollection
{
    protected $results = [];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {

            $stats = [
                'total' => 0,
                'imported' => 0,
                'failed' => 0,
                'duplicate_excel' => 0,
                'duplicate_db' => 0,
                'missing_data' => 0,
            ];

            $failedSims = [];
            $processedSims = [];
            $importedSimIds = [];

            $newSims = $rows->skip(1)->pluck(0)->filter()->toArray();
            $existingSims = Sims::whereIn('number', $newSims)->pluck('number')->toArray();
            $existingSims = array_flip($existingSims);

            foreach ($rows->skip(1) as $row) {

                if($row->every(function ($value) { return empty($value) || $value === '';})) {
                    break;
                }

                try {
                    $stats['total']++;
                    $simNumber  = trim($row[0] ?? '');
                    $company    = trim($row[1] ?? '');
                    $emi        = trim($row[2] ?? '');
                    $vendor     = trim($row[3] ?? '');

                    if (empty($simNumber) || empty($company)) {
                        $stats['missing_data']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $emi, $vendor, 'Missing required fields');
                        continue;
                    }

                    if (in_array($simNumber, $processedSims)) {
                        $stats['duplicate_excel']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $emi, $vendor, 'Duplicate SIM number in Excel file');
                        continue;
                    }

                    if (isset($existingSims[$simNumber])) {
                        $stats['duplicate_db']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company,  $emi, $vendor, 'Sim number already exists in Database');
                        continue;
                    }

                    $compny = SimCompany::whereRaw(
                        'LOWER(name) = ?',
                        [strtolower($company)]
                    )->first();
                    $vendors = Customers::whereRaw(
                        'LOWER(name) = ?',
                        [strtolower($vendor)]
                    )->first();

                    if(!$compny) {
                        $stats['missing_data']++;
                        $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber, $company, $emi, $vendor, 'Company not found');
                        continue;
                    }

                    $sim = Sims::create([
                        'number' => $simNumber,
                        'company' => $compny->name,
                        'company_id' => $compny->id,
                        'vendor' => $vendors?->id ?? null,
                        'emi' => $emi ?? null,
                        'status' => 0,
                        'created_by' => Auth::id(),
                    ]);

                    $processedSims[] = $simNumber;
                    $stats['imported']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $failedSims[] = $this->createFailureEntry($stats['total'], $simNumber ?? null, $company ?? null, $emi ?? null, $vendor ?? null, 'Exception: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
                    \Log::error("Import error: " . $e->getMessage());
                    continue;
                }           
            } 

            $this->results = [
                'stats' => $stats,
                'failed' => $failedSims,
                'processed' => $processedSims,
            ];
            DB::commit();
            \Log::info("SIM Import completed. Stats: " . json_encode($stats));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Transaction error: " . $e->getMessage());
            throw $e;
        }
    }

    private function createFailureEntry($rowIndex, $number, $company,  $emi,  $vendor, $reason, $extra = [])
    {
        return array_merge([
            'excel_row' => $rowIndex + 1, 
            'number' => $number ?? 'Missing',
            'company' => $company ?? 'Missing',
            'emi' => $emi ?? 'Missing',
            'vendor' => $vendor ?? 'Missing',
            'reason' => $reason,
        ], $extra);
    }

    public function getResults()
    {
        return $this->results;
    }
}
